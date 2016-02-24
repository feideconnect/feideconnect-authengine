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

    public function __construct($attributes, $accountMapRules) {

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
        $this->realm  = $this->obtainRealm();
        $this->org    = $this->get("org");
        $this->name   = $this->get("name", '');
        $this->mail   = $this->get("mail", '');
        $this->yob    = $this->get("yob");
        $this->sourceID = $this->obtainSourceID();

        $this->photo  = $this->obtainPhoto();

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


    public static function compareType($candidate, $match) {
        for ($i = 0; $i < count($match); $i++) {
            if ($candidate[$i] === 'idporten' && $match[$i] !== 'idporten') {
                return false;
            }
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

    public function hasAnyOfUserIDs($userids) {

        if (empty($userids)) {
            return false;
        }
        foreach ($userids as $u) {
            $has = $this->hasUserID($u);
            if ($has) {
                return true;
            }
        }
        return false;

    }

    public function hasUserID($userid) {

        if (empty($this->userids)) {
            return false;
        }
        foreach ($this->userids as $u) {
            if ($userid === $u) {
                return true;
            }
        }
        return false;

    }

    public function getDef() {

        $def = [];

        $tag = $this->getVisualTag();
        return $tag["def"];
    }

    public function validateAuthProvider($authproviders) {




        if (empty($authproviders)) {
            return true;
        }


        $def = $this->getDef();

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
                $tag["title"] = 'Feide testbruker';
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

        }
        $sourceData = [
            'idporten' => [
                "type" => "saml",
                "id" => "idporten.difi.no-v3",
                "title" => 'IDporten',
                "def" => [["idporten"]],
            ],
            'openidp' => [
                "type" => "saml",
                "id" => "https://openidp.feide.no",
                "title" => 'Feide OpenIdP guest account',
                "def" => [["other", "openidp"]],
            ],
            'twitter' => [
                "type" => "twitter",
                "title" => 'Twitter',
                "def" => [["social", "twitter"]],
            ],
            'linkedin' => [
                "type" => "linkedin",
                "title" => 'LinkedIn',
                "def" => [["social", "linkedin"]],
            ],
            'facebook' => [
                "type" => "facebook",
                "title" => 'Facebook',
                "def" => [["social", "facebook"]],
            ],
            '' => [
                "title" => "Unknown",
                "def" => [],
            ]
        ];
        $sourceID = $this->sourceID;
        if (!isset($sourceData[$sourceID])) {
            $sourceID = '';
        }
        $tag = [
            "name" => $this->name,
            "userids" => $this->maskNin($this->userids),
        ];
        return array_merge($sourceData[$sourceID], $tag);
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

    protected function getComplexAttrnames($attrnames) {
        foreach ($attrnames as $attr) {
            if (isset($this->attributes[$attr])) {
                return $this->attributes[$attr][0];
            }
        }
        return null;
    }

    protected function getComplexJoinAttrnames($attrnames) {
        $parts = [];
        foreach ($attrnames as $attr) {
            if (isset($this->attributes[$attr])) {
                $parts[] = $this->attributes[$attr][0];
            }
        }
        if (empty($parts)) {
            return null;
        }
        return join(" ", $parts);
    }

    protected function getComplexUrlref($rule) {
        if (!isset($rule["attrname"])) {
            throw new Exception("Missing [attrname] on complex attribute definition");
        }
        $attrname = $rule["attrname"];
        $url = $this->getValue($attrname);

        $prot = parse_url($url, PHP_URL_SCHEME);
        if ($prot === false || $prot === null) {
            return null;
        }
        if (!in_array($prot, ["http", "https"])) {
            return null;
        }

        $value = file_get_contents($url);

        return $value;

    }

    protected function getComplex($rule) {

        if (isset($rule["type"]) && $rule["type"] === "urlref") {
            return $this->getComplexUrlref($rule);
        }
        if (isset($rule["type"]) && $rule["type"] === "fixed") {
            if (!isset($rule["value"])) {
                throw new Exception("Missing [value] on complex attribute definition");
            }
            return $rule["value"];

        }
        if (isset($rule["attrnames"]) && is_array($rule["attrnames"])) {
            $attrnames = $rule["attrnames"];
            return $this->getComplexAttrnames($attrnames);
        }
        if (isset($rule["joinattrnames"]) && is_array($rule["joinattrnames"])) {
            return $this->getComplexJoinAttrnames($rule["joinattrnames"]);
        }

        throw new Exception("Unreckognized complex attribute mapping ruleset");

    }


    protected function getValue($property) {
        if (isset($this->attributes[$property])) {
            return $this->attributes[$property][0];
        }
        return null;
    }

    protected function getRule($property) {
        if (!array_key_exists($property, $this->accountMapRules)) {
            throw new Exception("No defined attribute account map rule for property [" . $property . "]");
        }
        return $this->accountMapRules[$property];
    }

    protected function get($property, $default = null) {
        $value = null;
        $rule = $this->getRule($property);
        if (is_string($rule)) {
            $value = $this->getValue($rule);
        } else if (is_array($rule)) {
            $value = $this->getComplex($rule);
        }

        if ($value !== null) {
            return $value;
        }

        return $default;

    }

    protected function obtainSourceID() {
        $property = "sourceID";
        $rule = $this->getRule($property);

        $value = '';
        if (!is_array($rule) || !isset($rule["prefix"])) {
            throw new Exception("Incorrect sourceID specification in attribute map ruleset");
        }

        $value .= $rule["prefix"];

        if ($rule["realm"]) {
            $value .= ':' . $this->requireRealm();
        }
        return $value;
    }

    protected function obtainRealm() {
        $property = "realm";
        $rule = $this->getRule($property);
        if (!isset($rule)) {
            return null;
        }
        if (!isset($rule["attrname"])) {
            throw new Exception("Missing [attrname] on realm definition");
        }
        return $this->getComplexRealm($rule["attrname"]);
    }

    protected function obtainUserIDs() {
        $property = "userid";

        $useridMap = $this->getRule($property);
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
        $rule = $this->getRule($property);

        if ($rule === null) {
            return null;
        }

        if (is_string($rule)) {
            if (empty($this->attributes[$rule])) {
                return null;
            }
            $value = $this->attributes[$rule][0];
            return new AccountPhoto($value);
        } else {
            $value = $this->getComplex($rule);
            if ($value === null) {
                return null;
            }
            $value = base64_encode($value);
            return new AccountPhoto($value);
        }

    }

    public static function checkAgeLimit($yob, $ageLimit, $ts) {
        $res = true;

        $year = intval($yob);

        if ($year < 1800) {
            return $res;
        }

        $dayofyear = date("z", $ts);
        $thisyear = date("Y", $ts);

        $requiredAge = $ageLimit;
        if ($dayofyear < 175) {
            $requiredAge = $ageLimit + 1;
        }

        $age = $thisyear - $year;

        if ($age >= $requiredAge) {
            $res = true;
        } else {
            $res = false;
        }

        return $res;
    }

    public function aboveAgeLimit($ageLimit = 13) {
        return self::checkAgeLimit($this->yob, $ageLimit, time());
    }

    public function getUserIDs() {
        return $this->userids;
    }
    public function getOrg() {
        return $this->org;
    }

    public function getRealm() {
        return $this->realm;
    }
    public function getPhoto() {
        if ($this->photo === null) {
            return null;
        }
        return $this->photo->getPhoto();
    }

    public function requireRealm() {
        $realm = $this->getRealm();
        if ($realm === null) {
            throw new Exception('Could not obtain the realm part of this authenticated account.');
        }
        return $realm;
    }

    public function getSourceID() {
        return $this->sourceID;
    }

    public function getName() {
        return $this->name;
    }

    public function getMail() {
        return $this->mail;
    }

}
