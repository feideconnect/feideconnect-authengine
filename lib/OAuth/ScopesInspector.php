<?php


namespace FeideConnect\OAuth;

use FeideConnect\Config;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Logger;

/**
 *
 * ScopesInspector
 */
class ScopesInspector {

    protected $scopes;

    protected $globalScopes;

    protected $storage;

    protected $apis = [], $owners = [], $orgs = [];

    public function __construct($scopes) {
        $this->scopes = $scopes;
        $this->globalScopes = Config::readJSONfile('scopedef.json');

        $this->storage = StorageProvider::getStorage();

        $this->apis = [];
    }

    protected function getOwner($ownerid) {
        if (isset($this->owners[$ownerid])) {
            return $this->owners[$ownerid];
        }
        $this->owners[$ownerid] = $this->storage->getUserByUserID($ownerid);
        return $this->owners[$ownerid];
    }

    protected function getOrg($orgid) {
        if (isset($this->orgs[$orgid])) {
            return $this->orgs[$orgid];
        }
        $this->orgs[$orgid] = $this->storage->getOrg($orgid);
        return $this->orgs[$orgid];
    }

    protected function getAPI($apigkid) {

        if (isset($this->apis[$apigkid])) {
            if ($this->apis[$apigkid] === null) {
                throw new \Exception("APIGK not found " . $apigkid);
            }
            return $this->apis[$apigkid];
        }

        $apigk = $this->storage->getAPIGK($apigkid);

        if ($apigk === null) {
            $this->apis[$apigkid] = null;
            throw new \Exception("APIGK not found " . $apigkid);
        }

        $apiInfo = array(
            'apigk' => $apigk,
            'localScopes' => [],
        );
        try {
            if ($apigk->has('organization')) {
                $orgObj = $this->getOrg($apigk->organization);
                if ($orgObj !== null) {
                    $apiInfo["orgInfo"] = $orgObj->getAsArray();
                    $apiInfo["orgInfo"]["logoURL"] = Config::dir("orgs/" . $apigk->organization . "/logo", "", "core");
                }

            } else {
                $ownerObj = $this->getOwner($apigk->owner);
                if ($ownerObj !== null) {
                    $apiInfo["ownerInfo"] = $ownerObj->getBasicUserInfo(true, ["userid", "p"]);
                }
            }
        } catch (\Exception $e) {
            $this->apis[$apigkid] = null;
            throw new \Exception("APIGK not found " . $apigkid);
        }
        $this->apis[$apigkid] = $apiInfo;
        return $apiInfo;
    }

    public function getScopeAPIGKs() {
        $apis = [];
        foreach ($this->scopes as $scope) {
            if (!preg_match('/^gk_([a-z0-9\-]+)(_([a-z0-9\-]+))?$/', $scope, $matches)) {
                continue;
            }

            $apigkid = $matches[1];
            try {
                $apiInfo = $this->getAPI($apigkid);
            } catch (\Exception $e) {
                continue;
            }
            if (isset($matches[3])) {
                $apiInfo['localScopes'][] = $matches[3];
            }
            $apis[$scope] = $apiInfo;
        }
        return $apis;
    }

    public function getInfo() {
        $allaccesses = [ 'userid', 'email', 'name', 'photo', 'userid-feide'];
        $apis = [];
        $data = [
            "userinfo" => [],
            "global" => [],
            "apis" => [],
            "unknown" => [],
            "allScopes" => $this->scopes
        ];

        $scope_apis = $this->getScopeAPIGKs();
        $accesses = self::scopesToAccesses($this->scopes);

        foreach ($accesses as $access) {
            if (isset($scope_apis[$access])) {
                $apiInfo = $scope_apis[$access];
                $apis[$apiInfo['apigk']->id] = $apiInfo;

            } else {
                if (in_array($access, $allaccesses)) {
                    $data['userinfo'][$access] = $access;
                } else if (isset($this->globalScopes[$access])) {
                    $ne = $this->globalScopes[$access];
                    $ne["scope"] = $access;
                    $data['global'][$access] = $ne;
                } else {
                    $data["unknown"][] = $access;
                }
            }
        }

        foreach ($apis as $apigkid => $api) {
            $apiEntry = [
                "info" => $api["apigk"]->getAsArray(),
                "scopes" => []
            ];
            if (isset($api["ownerInfo"])) {
                $apiEntry["owner"] = $api["ownerInfo"];
            }
            if (isset($api["orgInfo"])) {
                $apiEntry["org"] = $api["orgInfo"];
            }

            $apiEntry["scopes"][] = $api["apigk"]->getBasicScopeView();
            foreach ($api["localScopes"] as $ls) {
                $apiEntry["scopes"][] = $api["apigk"]->getSubScopeView($ls);
            }
            $data["apis"][] = $apiEntry;
        }

        $data["hasAPIs"] = (count($data["apis"]) > 0);

        return $data;

    }

    public static function scopesToAccesses($scopes) {
        $lookuptable = [
            'openid' => ['userid'],
            'userid' => ['userid'],
            'email' => ['email'],
            'userinfo-mail' => ['email'],
            'userinfo' => ['userid', 'name'],
            'userinfo-photo' => ['photo'],
            'profile' => ['name', 'photo'],
            'userinfo-feide' => ['userid-feide'],
            'userid-feide' => ['userid-feide'],
        ];
        $accesses = [];
        foreach ($scopes as $scope) {
            if (isset($lookuptable[$scope])) {
                foreach ($lookuptable[$scope] as $access) {
                    $accesses[$access] = true;
                }
            } else {
                $accesses[$scope] = true;
            }
        }
        return array_keys($accesses);
    }
}
