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

    protected static $specialScopes = ['longterm'];

    protected $scopes;
    protected $storage;

    protected $apis = [], $owners = [], $orgs = [];

    public function __construct($scopes, $authorizationEvaluator, $organization=null, $user=null) {
        $this->scopes = $scopes;

        $this->storage = StorageProvider::getStorage();

        $this->apis = [];
        $this->authorizationEvaluator = $authorizationEvaluator;
        $this->organization = $organization;
        $this->user = $user;
    }

    public static function sortByScore($a, $b) {

        $x = isset($a["score"]) ? $a["score"] : 0;
        $y = isset($b["score"]) ? $b["score"] : 0;
        if ($x == $y) {
            return 0;
        }
        return ($x < $y) ? 1 : -1;
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


        $nsi = new ScopesInspector($apigk->getScopeList(), $this->authorizationEvaluator);
        $apiInfo['nestedPermissions'] = $nsi->getInfo();

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
        $apisById = [];
        $scopeApis = $this->authorizationEvaluator->getScopeAPIGKs($this->scopes);

        foreach ($scopeApis as $scope => $apigk) {
            list($apigkid, $subscope) = APIGK::parseScope($scope);
            if (array_key_exists($apigkid, $apisById)) {
                $apiInfo = $apisById[$apigkid];
            } else {
               try {
                    $apiInfo = $this->getAPIinfo($apigk);
                } catch (\Exception $e) {
                    continue;
                }
            }
            if (isset($subscope)) {
                $apiInfo['localScopes'][] = $subscope;
            }
            $apisById[$apigkid] = $apiInfo;
            $apis[$scope] = $apiInfo;
        }
        return $apis;
    }


    /*
     * Returns a list of access rather than scopes.
     * And also obtains all neccessary info about scopes to Gatekeepers and subscopes.
     */
    public function getInfo() {

        $apis = [];
        $data = [
            "global" => [],                     // All global scopes without special handling.
            "apis" => [],                       // All APIs behind API GKs that is represented.
            "allScopes" => $this->scopes        // All the requested scopes
        ];

        $scope_apis = $this->getScopeAPIGKs();
        $accesses = self::scopesToAccesses($this->scopes);

        foreach ($accesses as $access) {
            if (isset($scope_apis[$access])) {

                $apiInfo = $scope_apis[$access];
                $apis[$apiInfo['apigk']->id] = $apiInfo;

            } else if (APIGK::isApiScope($access)) {
                $data['unknownAPI'][] = $access;
            } else {
                $data['global'][] = $access;
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


    public function hasScope($scope) {
        foreach($this->scopes AS $s) {
            if ($s === $scope) {
                return true;
            }

        }
        return false;
    }

    public function isLongTerm() {
        return $this->hasScope('longterm');
    }


    /*
     * Generates a complete view (with translations) for permissions.
     */
    public function getView() {
        $info = $this->getInfo();
        list($requestors, $requestorInfo) = $this->getRequestors($info);
        $info['views'] = $this->createViews($requestors);
        $info['requestorInfo'] = $requestorInfo;
        return $info;
    }


    /*
     * create views based on all requestors and their requested permissions
     */
    private function createViews($requestors) {
        $permissionInfo = [
            'userid' => [
                'title' => Localization::getTerm('userid'),
                'icon' => 'key',
                'score' => 100,
            ],
            'userid-feide' => [
                'title' => Localization::getTerm('feideid'),
                'icon' => 'key',
                'score' => 100,
            ],
            'userid-nin' => [
                'title' => Localization::getTerm('nin'),
                'icon' => 'key',
                'score' => 100,
            ],
            'userid-social' => [
                'title' => Localization::getTerm('socialid'),
                'icon' => 'key',
                'score' => 100,
            ],
            'email' => [
                'title' => Localization::getTerm('email'),
                'icon' => 'envelope-o',
                'score' => 90,
            ],
            'name' => [
                'title' => Localization::getTerm('name'),
                'icon' => 'tag',
                'score' => 80,
            ],
            'photo' => [
                'title' => Localization::getTerm('profilephoto'),
                'icon' => 'camera-retro',
                'score' => 70,
            ],
            'groups' => [
                'title' => Localization::getTerm('perm-groups'),
                'descr' => Localization::getTerm('perm-groups-descr'),
                'icon' => 'users',
                'expanded' => true,
                'score' => 60,
            ],
            'orgadmin' => [
                'title' => Localization::getTerm('perm-orgadmin'),
                'descr' => Localization::getTerm('perm-orgadmin-descr'),
                'icon' => 'cog',
            ],
            'groups-orgadmin' => [
                'title' => Localization::getTerm('perm-groups-orgadmin'),
                'icon' => 'users',
                'score' => 59,
            ],
            'peoplesearch' => [
                'title' => Localization::getTerm('perm-peoplesearch'),
                'descr' => Localization::getTerm('perm-peoplesearch-descr'),
                'icon' => 'search',
            ]
        ];


        $views = [];

        foreach ($requestors as $requestor => $scopes) {
            foreach ($scopes as $k => $scope) {
                if (isset($permissionInfo[$scope])) {
                    $views[$requestor][] = $permissionInfo[$scope];
                } else if (!in_array($scope, self::$specialScopes)) {
                    $views[$requestor][] = [
                        'title' => 'Unknown permission [' . htmlspecialchars($scope) . ']',
                        'score' => -1
                    ];
                }
            }
            usort($views[$requestor], ['FeideConnect\OAuth\ScopesInspector', 'sortByScore']);
        }

        return $views;
    }


    /* Add all api requestors */
    private function getApiRequestors($api) {
        $requestors = [];
        $requestorInfo = [];

        // Find key for this org or owner
        // Store org or owner information in info array
        if (array_key_exists('org', $api)) {
            $key = $api['org']['id'];
            $requestorInfo[] = array($key => $api['org']);
        } else if (array_key_exists('owner', $api)) {
            $key = $api['owner']['userid'];
            $requestorInfo[] = array($key => $api['owner']);
        }

        // Store requested permissions for this api
        $permissions = $api['nestedPermissions'];
        $perms = $permissions['global'];
        $requestors[] = array($key => $perms);

        // Find children apis and store permissions and org|owner information
        // about those aswell
        foreach ($permissions['apis'] as $key => $nestedApi) {
            list($apiRequestors, $apiRequestorInfo) = $this->getApiRequestors($nestedApi);
            $requestors = array_merge($requestors, $apiRequestors);
            $requestorInfo = array_merge($requestorInfo, $apiRequestorInfo);
        }

        // Returns the list of all requestors with their permissions,
        // and the information about each requestor
        // Each has id as key
        // $requestors = [{key: [permissions]}, {key: [permissions]}, ...]
        // $requestorInfo = [{key: [permissions]}, ...]
        return [$requestors, $requestorInfo];
    }


    /*
     * Structure info based on organization or owner
     * An org or owner is here called a requestor
     */
    private function getRequestors($data) {
        if ($this->organization) {
            $key = $this->organization->id;
            $orginfo = $this->getOrg($key)->getAsArray();
            $orginfo["logoURL"] = Config::dir("orgs/" . $key . "/logo", "", "core");
            $requestorInfo[] = array($key => $orginfo);
        } else if ($this->user) {
            $ownerinfo = $this->user->getBasicUserInfo(true);
            $key = $ownerinfo['userid'];
            $requestorInfo[] = array($key => $ownerinfo);
        }

        $requestors = [];
        $requestors[] = array($key => $data['global']);

        // Fetch all requestors with their scopes
        // Also collect all the info about the org or owner
        foreach ($data['apis'] as $key => $api) {
            list($apiRequestors, $apiRequestorInfo) = $this->getApiRequestors($api, $requestors, $requestorInfo);
            $requestors = array_merge($requestors, $apiRequestors);
            $requestorInfo = array_merge($requestorInfo, $apiRequestorInfo);
        }

        return [$this->createUnique($requestors), $this->createUnique($requestorInfo)];
    }


    /*
     * Convert [{key1: [values1]}, {key1: [values2]}] to
     * [{key1: unique[values1 + values2]}, {key2: [values]}, ...]
     */
    private function createUnique($someArrayofArray) {
        $toReturn = [];
        foreach ($someArrayofArray as $someArray) {
            foreach ($someArray as $key => $values) {
                if (array_key_exists($key, $toReturn)) {
                    $toReturn[$key] = array_unique(
                        array_merge($toReturn[$key], $values));
                } else {
                    $toReturn[$key] = $values;
                }
            }
        }
        return $toReturn;
    }


    public static function scopesToAccesses($scopes) {
        $lookuptable = [
            'openid' => ['userid'],
            'userid' => ['userid'],
            'email' => ['email'],
            'profile' => ['name', 'photo'],
            'userid-feide' => ['userid-feide'],
            'userid-nin' => ['userid-nin'],
            'userid-social' => ['userid-social'],
        ];
        $accesses = [];
        foreach ($scopes as $scope) {
            if (isset($lookuptable[$scope])) {
                foreach ($lookuptable[$scope] as $access) {
                    $accesses[] = $access;
                }
            } else {
                $accesses[] = $scope;
            }
        }
        return array_unique($accesses);
    }

}
