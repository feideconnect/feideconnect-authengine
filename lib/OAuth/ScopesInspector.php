<?php


namespace FeideConnect\OAuth;

use FeideConnect\Config;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Models\APIGK;
use FeideConnect\Logger;
use FeideConnect\Localization;

/**
 * ScopesInspector
 *
 * The scopesinspector takes a list of scope strings as input, typically the ones authorized to a client
 * and returns detailed structued information about the permissions inherited from those scopes.
 * This includes information about the Gatekeeper APIs that is represented by gk_api(_subscope) scopes types. 
 */
class ScopesInspector {

    protected $userinfopermissions = [ 'userid', 'email', 'name', 'photo', 'userid-feide'];

    protected $scopes;
    protected $globalScopes;
    protected $storage;

    protected $apis = [], $owners = [], $orgs = [];

    public function __construct($scopes, $authorizationEvaluator) {
        $this->scopes = $scopes;
        $this->globalScopes = Config::readJSONfile('scopedef.json');

        $this->storage = StorageProvider::getStorage();

        $this->apis = [];
        $this->authorizationEvaluator = $authorizationEvaluator;
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

    protected function getAPIinfo($apigk) {

        if (isset($this->apis[$apigk->id])) {
            if ($this->apis[$apigk->id] === null) {
                throw new \Exception("APIGK not found " . $apigkid);
            }
            return $this->apis[$apigk->id];
        }

        $apiInfo = array(
            'apigk' => $apigk,
            'localScopes' => [],
            'nestedPermissions' => [],
        );

        // echo '<pre>'; print_r($apigk); print_r($apigk->getScopeList()); exit;

        $nsi = new ScopesInspector($apigk->getScopeList(), $this->authorizationEvaluator);
        $apiInfo['nestedPermissions'] = $nsi->getView();

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
            $this->apis[$apigk->id] = null;
            throw new \Exception("APIGK owner/org not found " . $apigkid);
        }
        $this->apis[$apigk->id] = $apiInfo;
        return $apiInfo;
    }

    public function getScopeAPIGKs() {
        $apis = [];
        $scopeApis = $this->authorizationEvaluator->getScopeAPIGKs($this->scopes);
        foreach ($scopeApis as $scope => $apigk) {

            list($apigkid, $subscope) = APIGK::parseScope($scope);
            try {
                $apiInfo = $this->getAPIinfo($apigk);
            } catch (\Exception $e) {
                continue;
            }
            if (isset($subscope)) {
                $apiInfo['localScopes'][] = $subscope;
            }
            $apis[$scope] = $apiInfo;
        }
        return $apis;
    }



    public function getInfo() {

        
        $apis = [];
        $data = [
            "userinfo" => [],                   // Specific handling of userinfo.
            "global" => [],                     // All global scopes without special handling, like userinfo
            "apis" => [],                       // All APIs behind API GKs that is represented.
            "unknown" => [],                    // Unkown scopes
            "allScopes" => $this->scopes        // All the requested scopes
        ];


        $scope_apis = $this->getScopeAPIGKs();
        $accesses = self::scopesToAccesses($this->scopes);

        // echo '<h2>Scope apis</h2><pre>';
        // print_r($scope_apis);
        // echo '<h2>accesses</h2><pre>';
        // print_r($accesses);
        // exit;

        foreach ($accesses as $access) {
            if (isset($scope_apis[$access])) {
                $apiInfo = $scope_apis[$access];
                $apis[$apiInfo['apigk']->id] = $apiInfo;

            } else {
                if (in_array($access, $this->userinfopermissions)) {
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
                "info" => $api["apigk"]->getBasicView(),
                "scopes" => []
            ];
            if (isset($api["ownerInfo"])) {
                $apiEntry["owner"] = $api["ownerInfo"];
            }
            if (isset($api["orgInfo"])) {
                $apiEntry["org"] = $api["orgInfo"];
            }
            if (isset($api["nestedPermissions"])) {
                $apiEntry["nestedPermissions"] = $api["nestedPermissions"];
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



    /*
     * Generates a complete view (with translations) for permissions.
     * Based upon the info array returned by getInfo().
     * 
     * The api list is not handled here.
     */
    public function getView() {


        $info = $this->getInfo();
        $info['view'] = [];

        $permissionInfo = [
            'userid' => [
                'title' => Localization::getTerm('userid'),
                'icon' => 'key'
            ],
            'email' => [
                'title' => Localization::getTerm('email'),
                'icon' => 'envelope-o'
            ],
            'name' => [
                'title' => Localization::getTerm('name'),
                'icon' => 'tag'
            ],
            'photo' => [
                'title' => Localization::getTerm('profilephoto'),
                'icon' => 'camera-retro'
            ],
            'userid-feide' => [
                'title' => Localization::getTerm('feideid'),
                'icon' => 'key'
            ],

            'groups' => [
                'title' => Localization::getTerm('perm-groups'),
                'descr' => Localization::getTerm('perm-groups-descr'),
                'icon' => 'users',
                'expanded' => true
            ],
            'clientadmin' => [
                'title' => Localization::getTerm('perm-clientadmin'),
                'descr' => Localization::getTerm('perm-clientadmin-descr'),
                'icon' => 'cog'
            ],
            'apigkadmin' => [
                'title' => Localization::getTerm('perm-apigkadmin'),
                'descr' => Localization::getTerm('perm-apigkadmin-descr'),
                'icon' => 'cog'
            ],
            'orgadmin' => [
                'title' => Localization::getTerm('perm-orgadmin'),
                'descr' => Localization::getTerm('perm-orgadmin-descr'),
                'icon' => 'cog'
            ],
            'peoplesearch' => [
                'title' => Localization::getTerm('perm-peoplesearch'),
                'descr' => Localization::getTerm('perm-peoplesearch-descr'),
                'icon' => 'search'
            ],
            'longterm' => [
                'title' => Localization::getTerm('perm-longterm'),
                'descr' => Localization::getTerm('perm-longterm-descr'),
                'icon' => 'clock-o'
            ],
        ];

        if (!empty($info['userinfo'])) {
            $item = [
                'title' => Localization::getTerm('accesstouserinfo'),
                // 'descr' => '',
                'icon' => 'user',
                'items' => [],
                'expanded' => true
            ];
            foreach($info['userinfo'] AS $userinfoperm) {
                if (isset($permissionInfo[$userinfoperm])) {
                    $item['items'][] = $permissionInfo[$userinfoperm];
                }
            }
            $info['view'][] = $item;
        }


        foreach($info['global'] AS $globalperm => $globalpermdata) {

            if (isset($permissionInfo[$globalperm])) {
                $info['view'][] = $permissionInfo[$globalperm];
            }
        }


        return $info;

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
