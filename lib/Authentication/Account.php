<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Exceptions\AuthProviderNotAccepted;
use FeideConnect\Controllers\Metadata;

class Account {

    public $userids;
    public $realm, $country, $name, $mail, $yob, $sourceID;
    public $photo = null;

    public $attributes;
    protected $accountMapRules;
    protected $orgInfo;

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
        $this->country= isset($this->attributes["country"]) ? $this->attributes["country"] : null;
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

    /**
     * Get the account types for the current account.
     *
     * The account types returned by this function matches the account types you can
     * allow access for in the Dataporten dashboard.
     *
     * @return array  List of account types for the current account.
     */
    private function getAccountTypes() {

        // The simple account types. Here we can simply return a static array.
        $accountTypes = [
            'idporten' => [ 'idporten' ], // Note: Not account type "all", since "all" in dashboard does not include idporten.
            'openidp' => [ 'all', 'other|openidp' ],
            'twitter' => [ 'all', 'social|all', 'social|twitter' ],
            'linkedin' => [ 'all', 'social|all', 'social|linkedin' ],
            'facebook' => [ 'all', 'social|all', 'social|facebook' ],
        ];
        if (isset($accountTypes[$this->sourceID])) {
            return $accountTypes[$this->sourceID];
        }

        // Special handling of edugain source:
        if (preg_match('/^edugain:(.+?)$/', $this->sourceID, $matches)) {
            return ['all', 'edugain', 'edugain|' . $matches[1]];
        }

        $org = $this->getRealm();
        if ($org) {
            // A feide account.
            if ($org === 'spusers.feide.no') {
                // The service provider organization in Feide.
                return [ 'all', 'other|feidetest' ];
            }
            // A normal Feide account.
            $ret = [ 'all', 'feide|all', 'feide|realm|' . $org];
            $orginfo = $this->getOrgInfo();
            if ($orginfo !== null) {
                // Adds 'feide|uh', 'feide|vgs' and 'feide|go'.
                foreach ($orginfo->getTypes() as $type) {
                    $ret[] = 'feide|' . $type;
                }
            }
            return $ret;
        }

        throw new \Exception('Unable to detect where this user can log in because the user account source was unknown.');
    }

    public function validateAuthProvider($authproviders) {

        // Intersect the account types allowed by the service with the account types of the current account.
        // If the intersection isn't empty, the account has access to the service.
        $matching_accounts = array_intersect($this->getAccountTypes(), $authproviders);
        if (empty($matching_accounts)) {
            throw new AuthProviderNotAccepted('Authentication provider is not accepted by requesting client. Return to application and try to login again selecting another provider.');
        }

        return true;
    }

    // TODO: Update this code to automatically
    public function getVisualTag() {

        $org = $this->getRealm();
        if ($org) {
            $feideidp = Config::getValue('feideIdP');

            $tag = [
                "name" => $this->name,
                "type" => "saml",
                "id" => $feideidp,
                "subid" => $org,
                "title" => $this->getOrg(),
                "userids" => $this->userids,
                "def" => []
            ];

            if ($org === 'spusers.feide.no') {
                $tag["def"][] = ["other", "feidetest"];
                $tag["title"] = 'Feide testbruker';
            } else {
                $tag["def"][] = ["feide", "realm", $org];

                $orginfo = $this->getOrgInfo();
                if ($orginfo !== null) {
                    $types = $orginfo->getTypes();
                    foreach ($types as $type) {
                        $tag["def"][] = ["feide", $type];
                    }

                }

            }

            return $tag;

        }

        /*
         * Handling eduGain sourceID
         */
        if (preg_match('/^edugain:(.+?)$/', $this->sourceID, $matches)) {
            $countryCode = $matches[1];
            $allLangauges = Config::getCountryCodes();
            $tag = [
                "name" => $this->name,
                "type" => "saml",
                "id" => $this->idp,
                "userids" => $this->userids,
                "def" => [["edugain", $countryCode]],
                "country" => [
                    "code" => $countryCode,
                    "title" => isset($allLangauges[$countryCode]) ? $allLangauges[$countryCode] : 'Unknown',
                ]
            ];
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
        $tag = $sourceData[$sourceID];
        $tag["name"] = $this->name;
        $tag["userids"] = $this->maskNin($this->userids);

        // echo '<pre>'; print_r($tag);
        // print_r($this->sourceID); exit;

        return $tag;
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

        if (isset($rule["realm"])) {
            $value .= ':' . $this->requireRealm();
        }
        if (isset($rule["country"])) {
            $value .= ':' . $this->requireCountry();
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
        $doNormalize = (isset($this->accountMapRules["useridNormalize"]) && $this->accountMapRules["useridNormalize"]);

        foreach ($useridMap as $prefix => $attrname) {
            if (isset($this->attributes[$attrname])) {
                if ($doNormalize) {
                  $userids[] = $prefix . ':' . mb_strtolower($this->attributes[$attrname][0]);
                } else {
                  $userids[] = $prefix . ':' . $this->attributes[$attrname][0];
                }
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
        $orgInfo = $this->getOrgInfo();
        if ($orgInfo) {
            return $orgInfo->getName();
        }
        return null;
    }

    protected function getOrgInfo() {
        if (isset($this->orgInfo)) {
            return $this->orgInfo;
        }
        $realm = $this->getRealm();
        if (!$realm) {
            return null;
        }
        $storage = StorageProvider::getStorage();
        $this->orgInfo = $storage->getOrg('fc:org:' . $realm);
        return $this->orgInfo;
    }

    public function getRealm() {
        return $this->realm;
    }

    public function getCountry() {
        return $this->country;
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

    public function requireCountry() {
        $country = $this->getCountry();
        if ($country === null) {
            throw new Exception('Could not obtain originating country from federated login.');
        }
        return $country;
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

    public function getAcr() {
        return $this->getValue('eduPersonAssurance');
    }
}
