<?php

namespace FeideConnect\Data\Models;

class APIGK extends \FeideConnect\Data\Model {

    public $id, $name, $descr, $owner, $organization, $scopes, $scopes_requested, $endpoints, $expose, $httpscertpinned, $requireuser, $scopedef, $trust, $logo, $status, $created, $updated;

    protected static $_properties = [
        'id' => 'text',
        'name' => 'text',
        'descr' => 'text',
        'owner' => 'uuid',
        'organization' => 'text',
        'endpoints' => 'list<text>',
        'expose' => 'text',
        'httpscertpinned' => 'text',
        'requireuser' => 'boolean',
        'scopedef' => 'json',
        'trust' => 'text',
        'logo' => 'default',
        'scopes' => 'set<text>',
        'scopes_requested' => 'set<text>',
        'privacypolicyurl' => 'text',
        'systemdescr' => 'text',
        'status' => 'set<text>',
        'created' => 'timestamp',
        'updated' => 'timestamp',
    ];
    private static $apiScopeRE = '/^gk_([a-z0-9\-]+)(_([a-z0-9\-]+))?$/D';

    public static function isApiScope($scope) {
        return preg_match(self::$apiScopeRE, $scope, $matches);
    }

    public static function parseScope($scope) {
        if (!preg_match(self::$apiScopeRE, $scope, $matches)) {
            throw new UnexpectedValueException("Not a api scope");
        }
        $apigkid = $matches[1];
        $subscope = null;
        if (isset($matches[3])) {
            $subscope = $matches[3];
        }
        return [$apigkid, $subscope];
    }

    public function getScopeList() {
        if (empty($this->scopes)) {
            return [];
        }
        return $this->scopes;
    }

    public function getScopeDef($scope) {
        if (!isset($this->scopedef)) {
            throw new \Exception("APIGK does not have scopedef set" . $this->id);
        }
        $scopedef = $this->scopedef;
        list($apigkid, $subscope) = self::parseScope($scope);
        if ($apigkid != $this->id) {
            throw new \Exception("Incorrect APIGK (" . $this->id . ") for scope: " . $scope);
        }
        if (isset($subscope)) {
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
