<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Exceptions\AuthProviderNotAccepted;

class Account {

    public $userids;
    public $realm, $name, $mail, $org, $yob, $sourceID;
    public $photo = null;

    public $attributes;
    protected $accountMapRules;

    function __construct($attributes, $accountMapRules) {

        $this->attributes = $attributes;
        $this->accountMapRules = $accountMapRules;


        if (empty($attributes)) {
            throw new Exception("Loading an account with an empty set of attributes");
        }
        if (empty($accountMapRules)) {
            throw new Exception("Loading an account with an empty attribute map ruleset");
        }




        $this->userids = $this->obtainUserIDs();

        $this->idp    = $this->attributes["idp"];
        $this->realm  = $this->get("realm");
        $this->org    = $this->get("org");
        $this->name   = $this->get("name", '');
        $this->mail   = $this->get("mail", '');
        $this->yob    = $this->get("yob");
        $this->sourceID = $this->get("sourceID", null, true);

        // echo '<pre>'; print_r($this); exit;

        $this->photo  = $this->obtainPhoto();




        // echo '<pre>We got this account map rules: ' . "\n";
        // // print_r($accountMapRules);
        // // print_r($this->attributes);
        // echo var_dump($this);
        // echo "Age: " . $this->aboveAgeLimit(35) . ".";
        // exit;


    }

    private function maskNin() {

        $res = [];
        foreach ($this->userids as $uid) {
            if (strpos($uid, 'nin:') === 0) {
                if (preg_match('/^nin:(\d{4})\d{7}/', $uid, $matches)) {
                    $res[] = 'nin:' . $matches[1] . '.......';
                } else {
                    $res[] = 'nin:...........';
                }
            } else {
                $res[] = $uid;
            }
        }
        return $res;

    }

    public function allowAll($authproviders) {

        foreach ($authproviders as $ap) {
            if (count($ap) === 1 && $ap[0] === 'all') {
                return true;
            }
        }
        return false;
    }

    public function compareType($candidate, $match) {

        // echo '<p>Compare candidate'; var_dump($candidate);
        // echo '<p>with match'; var_dump($match);

        for ($i = 0; $i < count($match); $i++) {
            if ($match[$i] === 'all') {
                return true;
            }
            if ($i > (count($candidate)- 1)) {
                return false;
            }
            if ($match[$i] !== $candidate[$i]) {
                return false;
            }
        }
        return true;
    }

    public function getDef() {

        $def = [];

        $tag = $this->getVisualTag();
        return $tag["def"];

        // var_dump($tag);
        // if (!empty($tag["def"])) {
        //     foreach($tag["def"] as $d) {
        //         // var_dump("TAG"); var_dump($d);
        //         // $s =
        //         // $def[] = explode(':', $d);
        //         echo 'fooo'; var_dump($d);
        //         array_push($def, explode(':', $d));
        //         $def[] = explode(':', $d);
        //         var_dump(explode(':', $d));
        //     }


        // }
        // var_dump($def);
        // return $def;


    }

    public function validateAuthProvider($authproviders) {


        // echo '<h1>validateAuthProvider()</h1>';
        // var_dump($authproviders);

        $def = $this->getDef();

        if (empty($authproviders)) {
            return true;
        }

        if ($this->allowAll($authproviders)) {
            return true;
        }

        if (empty($def)) {
            throw new \Exception('Unable to detect where this user can login.');
        }

        foreach ($def as $d) {
            foreach ($authproviders as $ap) {
                if ($this->compareType($d, $ap)) {
                    // echo "<h2>Did match</h2>"; var_dump($d); var_dump($ap);
                    return true;
                }
            }

        }

        throw new AuthProviderNotAccepted('Authentication provider is not accepted by requesting client. Return to application and try to login again selecting another provider.');



    }


    // TODO: Update this code to automatically
    public function getVisualTag() {

        if (isset($this->sourceID) && preg_match("/^feide:(.*?)$/", $this->sourceID, $matches)) {
            $feideidp = Config::getValue('feideIdP');

            $org = $matches[1];
            $tag = [
                "name" => $this->name,
                "type" => "saml",
                "id" => $feideidp,
                "subid" => $org,
                "title" => $this->org,
                "userids" => $this->userids,
                "def" => []
            ];

            if ($org === 'feide.no') {
                $tag["def"][] = ["other", "feidetest"];
            } else {
                $storage = StorageProvider::getStorage();

                $tag["def"][] = ["feide", "realm", $org];

                $orginfo = $storage->getOrg('fc:org:' . $org);
                if ($orginfo !== null) {
                    $types = $orginfo->getTypes();
                    foreach ($types as $type) {
                        $tag["def"][] = ["feide", $type];
                    }

                }

            }

            return $tag;

        } else if (isset($this->sourceID) && $this->sourceID === 'idporten') {
            $tag = [
                "name" => $this->name,
                "type" => "saml",
                "id" => "idporten.difi.no-v2",
                "title" => 'IDporten',
                "userids" => $this->maskNin($this->userids),
                "def" => [["other", "idporten"]],
             ];
             return $tag;

        } else if (isset($this->sourceID) && $this->sourceID === 'openidp') {
            $tag = [
                "name" => $this->name,
                "type" => "saml",
                "id" => "https://openidp.feide.no",
                "title" => 'Feide OpenIdP guest account',
                "userids" => $this->userids,
                "def" => [["other", "openidp"]],
             ];
             return $tag;

        } else if (isset($this->sourceID) && $this->sourceID === 'twitter') {
            $tag = [
                "name" => $this->name,
                "type" => "twitter",
                "title" => 'Twitter',
                "userids" => $this->userids,
                "def" => [["social", "twitter"]],
             ];
             return $tag;

        } else if (isset($this->sourceID) && $this->sourceID === 'linkedin') {
            $tag = [
                "name" => $this->name,
                "type" => "linkedin",
                "title" => 'LinkedIn',
                "userids" => $this->userids,
                "def" => [["social", "linkedin"]],
             ];
             return $tag;

        } else if (isset($this->sourceID) && $this->sourceID === 'facebook') {
            $tag = [
                "name" => $this->name,
                "type" => "facebook",
                "title" => 'Facebook',
                "userids" => $this->userids,
                "def" => [["social", "facebook"]],
             ];
             return $tag;

        }


        return [
            "name" => $this->name,
            "title" => "Unknown",
            "userids" => $this->userids,
            "def" => []
        ];

    }


    public function getAuthInstant() {

        if (isset($this->attributes["AuthnInstant"])) {
            return intval($this->attributes["AuthnInstant"]);
        }
        return time();
    }


    protected function getComplexRealm($attrname) {


        $value = $this->getValue($attrname, "");
        if (strpos($value, '@') === false) {
            return null;
        }
        // echo "abot to get realm from " . $attrname; exit;
        if (preg_match('/^.+?@(.+?)$/', $value, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function getComplexAttrnames($attrnames, $default = null, $required = false) {
        foreach ($attrnames as $attr) {
            if (isset($this->attributes[$attr])) {
                return $this->attributes[$attr][0];
            }
        }
        if ($required) {
            throw new Exception("Missing required attribute [" . join(',', $attrnames) . "]");
        }
        return $default;
    }

    protected function getComplexSourceID($def) {
        $value = '';
        if (!isset($def["prefix"])) {
            throw new Exception("Missing sourceID prefix for attribute map ruleset");
        }

        $value .= $def["prefix"];

        if ($def["realm"]) {
            $value .= ':' . $this->requireRealm();
        }
        return $value;
    }

    protected function getComplex($def, $default = null, $required = false) {

        // If definition contains type = realm.
        if (isset($def["type"]) && $def["type"] === "realm") {
            if (!isset($def["attrname"])) {
                throw new Exception("Missing [attrname] on complex attribute definition");
            }
            $attrname = $def["attrname"];
            return $this->getComplexRealm($attrname);

        // If definition contains type = realm.
        } else if (isset($def["type"]) && $def["type"] === "sourceID") {
            return $this->getComplexSourceID($def);

        } else if (isset($def["type"]) && $def["type"] === "urlref") {
            if (!isset($def["attrname"])) {
                throw new Exception("Missing [attrname] on complex attribute definition");
            }
            $attrname = $def["attrname"];
            $url = $this->getValue($attrname);

            $prot = parse_url($url, PHP_URL_SCHEME);
            if ($prot === false) {
                return null;
            }
            if (!in_array($prot, ["http", "https"])) {
                return null;
            }

            $value = file_get_contents($url);
            // echo $value; exit;

            return $value;

        } else if (isset($def["attrnames"]) && is_array($def["attrnames"])) {
            $attrnames = $def["attrnames"];
            return $this->getComplexAttrnames($attrnames, $default, $required);

        } else if (isset($def["type"]) && $def["type"] === "fixed") {
            if (!isset($def["value"])) {
                throw new Exception("Missing [value] on complex attribute definition");
            }
            return $def["value"];

        }

        // echo '<pre>'; var_dump($def); var_dump($default); var_dump($required); exit;
        throw new Exception("Unreckognized complex attribute mapping ruleset");

    }


    protected function getValue($property, $default = null, $required = false) {
        if (isset($this->attributes[$property])) {
            return $this->attributes[$property][0];
        }
        if ($required) {
            throw new Exception("Missing required attribute [" . $property . "]");
        }
        return $default;
    }

    protected function get($property, $default = null, $required = false) {

        if (!array_key_exists($property, $this->accountMapRules)) {
            throw new Exception("No defined attribute account map rule for property [" . $property . "]");
        }

        if (is_string($this->accountMapRules[$property])) {
            return $this->getValue($this->accountMapRules[$property], $default, $required);
        } else if (is_array($this->accountMapRules[$property])) {
            return $this->getComplex($this->accountMapRules[$property], $default, $required);
        }

        if ($required) {
            throw new Exception("Missing required attribute [" . $property . "]");
        }
        return $default;

    }

    protected function obtainUserIDs() {
        $property = "userid";
        if (!isset($this->accountMapRules[$property])) {
            throw new Exception("No defined attribute account map rule for property [" . $property . "]");
        }

        $useridMap = $this->accountMapRules[$property];
        $userids = [];

        foreach ($useridMap as $prefix => $attrname) {
            if (isset($this->attributes[$attrname])) {
                $userids[] = $prefix . ':' . $this->attributes[$attrname][0];
            }
        }

        return $userids;
    }

    protected function obtainPhoto() {
        $property = "photo";
        if (!array_key_exists($property, $this->accountMapRules)) {
            throw new Exception("No defined attribute account map rule for property [" . $property . "]");
        }
        // echo '<pre>_'; print_r($this->accountMapRules['photo']); exit;

        if ($this->accountMapRules[$property] === null) {
            return null;
        }

        if (is_string($this->accountMapRules[$property])) {
            if (empty($this->attributes[$this->accountMapRules[$property]])) {
                return null;
            }
            $value = $this->attributes[$this->accountMapRules[$property]][0];
            return new AccountPhoto($value);
        } else {
            $value = $this->getComplex($this->accountMapRules[$property]);
            $value = base64_encode($value);
            return new AccountPhoto($value);
        }

    }







    public function aboveAgeLimit($ageLimit = 13) {

        $res = true;
        $year = intval($this->yob);

        if ($year < 1800) {
            return $res;
        }

        $dayofyear = date("z");
        $thisyear = date("Y");

        $requiredAge = $ageLimit;
        if ($dayofyear < 175) {
            $requiredAge = $ageLimit + 1;
        }

        $age = $thisyear - $year;

        // echo "Reqired age is  " . $requiredAge . " ";
        // echo "Age is " . $age;
        if ($age >= $requiredAge) {
            $res = true;
        } else {
            $res = false;
        }

        return $res;

    }

    public function getUserIDs() {
        return $this->userids;
    }
    public function getOrg() {
        return $this->org;
    }

    //     $userids = array();
    //     foreach($this->accountmap as $prefix => $attrname) {
    //         if (isset($this->attributes[$attrname])) {
    //             $userids[] = $prefix . ':' . $this->attributes[$attrname][0];
    //         }
    //     }
    //     if (count($userids) == 0) {
    //         throw new Exception('Could not get any userids from this authenticated account.');
    //     }
    //     return $userids;
    // }

    public function getRealm() {
        return $this->realm;
    }
    public function getPhoto() {
        if ($this->photo === null) return null;
        return $this->photo->getPhoto();
    }

    public function requireRealm() {
        $realm = $this->getRealm();
        if ($realm === null) {
            throw new Exception('Could not obtain the realm part of this authenticated account.');
        }
        return $realm;
    }




    function getSourceID() {
        return $this->sourceID;
    }

    function getName() {
        return $this->name;
    }

    function getMail() {
        return $this->mail;
    }

}
