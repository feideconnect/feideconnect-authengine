<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;

class APIGK extends \FeideConnect\Data\Model {

    public $id, $name, $descr, $owner, $organization, $scopes, $scopes_requested, $endpoints, $expose, $httpscertpinned, $requireuser, $scopedef, $trust, $logo, $status, $created, $updated;

    protected static $_properties = array(
        "id", "name", "descr",
        "owner", "organization",  "endpoints", "expose", "httpscertpinned", "requireuser", "scopedef", "trust", "logo",
        "scopes", "scopes_requested",
        "privacypolicyurl", "systemdescr",
        "status", "created", "updated"
    );
    protected static $_types = [
        "created" => "timestamp",
        "updated" => "timestamp"
    ];


    public function __construct($props) {

        parent::__construct($props);

        if (isset($props["scopedef"])) {
            $this->scopedef = json_decode($props["scopedef"], true);
            unset($props["scopedef"]);
        }

    }

    public function getScopeList() {
        if (empty($this->scopes)) {
            return [];
        }
        return $this->scopes;
    }

    public function getStorableArray() {
        $data = parent::getStorableArray();
        if (isset($data['scopedef'])) {
            $data['scopedef'] = json_encode($data['scopedef']);
        }
        if (isset($this->owner)) {
            $data["owner"] =  new Uuid($this->owner);
        }
        if (isset($this->scopes)) {
            $data["scopes"] =  new CollectionSet($this->scopes, Base::ASCII);
        }
        if (isset($this->scopes_requested)) {
            $data["scopes_requested"] =  new CollectionSet($this->scopes_requested, Base::ASCII);
        }
        return $data;
    }

    public function getScopeDef($scope) {
        if (!isset($this->scopedef)) {
            throw new \Exception("APIGK does not have scopedef set" . $this->id);
        }
        $scopedef = $this->scopedef;
        if (!preg_match('/^gk_([a-z0-9\-]+)(_([a-z0-9\-]+))?$/', $scope, $matches)) {
            throw new \Exception("Incorrect APIGK (" . $this->id . ") for scope: " . $scope);
        }
        if (isset($matches[3])) {
            $subscope = $matches[3];
            if (isset($scopedef["subscopes"]) && isset($this->scopedef["subscopes"][$subscope])) {
                return $this->scopedef["subscopes"][$subscope];
            }
            throw new \Exception("APIGK (" . $this->id . ") does not define scope " . $scope);
        }
        $output = $scopedef;
        unset($output['subscopes']);
        return $output;
    }


    public function isOrgModerated($scope) {
        $scopedef = $this->getScopeDef($scope);
        if (!isset($scopedef['policy'])) {
            return false;
        }
        if (!isset($scopedef['policy']['orgadmin'])) {
            return false;
        }
        $orgadminpolicy = $scopedef['policy']['orgadmin'];
        if (!isset($orgadminpolicy['moderate'])) {
            return false;
        }
        return $orgadminpolicy['moderate'] === true;
    }

    public function getBasicView() {
        $attrs = [
            "id", "name", "descr",
            "owner", "organization", "scopedef", 
            "status", "created", "updated",
            "privacypolicyurl",
            "scopes"
        ];
        $info = $this->getAsArrayLimited($attrs);

        $info['APIownerOrg'] = isset($info['organization']);
        return $info;
    }

    public function getBasicScopeView() {
        $sd = [
            "title" => "Basic access",
            "descr" => "Basic access to this API."
        ];
        if (isset($this->scopedef) && isset($this->scopedef->title)) {
            $sd["title"] = $this->scopedef->title;
        }
        if (isset($this->scopedef) && isset($this->scopedef->descr)) {
            $sd["descr"] = $this->scopedef->descr;
        }
        return $sd;
    }

    public function getSubScopeView($subscope) {
        $sd = [
            "title" => "Unknown subscope [" . $subscope . "]",
            "descr" => "Unknown subscope [" . $subscope . "]"
        ];
        if (isset($this->scopedef) && isset($this->scopedef["subscopes"]) && isset($this->scopedef["subscopes"][$subscope])  && isset($this->scopedef["subscopes"][$subscope])) {
            $sd = $this->scopedef["subscopes"][$subscope];
        }

        return $sd;
    }


}
