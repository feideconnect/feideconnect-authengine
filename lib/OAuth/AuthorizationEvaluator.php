<?php


namespace FeideConnect\OAuth;

use FeideConnect\Logger;
use FeideConnect\OAuth\Exceptions;
use FeideConnect\Data\Models\Authorization;
use FeideConnect\Data\Models\APIGK;

/**
*
*/
class AuthorizationEvaluator {

    protected $storage, $client, $request, $user;

    protected $authorization = null;
    protected $scopesInQuestion = null;
    protected $scopesRemaining = null;

    public function __construct($storage, $client, $request, $user = null) {

        $this->storage = $storage;
        $this->client = $client;
        $this->request = $request;
        $this->user = $user;

        $this->authorization = null;
        $this->getAuthorization();
    }

    /**
     * Return list ov valid scopes for the given user, client and list of requested scopes
     *
     * Verifies that the requested scopes are valid for the given
     * client, and that any organization moderated scopes are approved
     * for the relevant organizations of the given user
     *
     * @return array list of valid scopes
     */
    public function getEffectiveScopes() {
        $scopelist = $clientscopes = $this->client->getScopeList();
        $requestedScopes = $this->request->scope;
        if (!empty($requestedScopes)) {
            // Only consider scopes that the client is authorized to ask for.
            $scopelist = array_intersect($requestedScopes, $scopelist);
        }
        $scopes = array();
        foreach ($scopelist as $scope) {
            $scopes[$scope] = true;
        }

        $feideuser = false;
        if ($this->user !== null) {
            $realms = $this->user->getFeideRealms();
            if (count($realms) > 0) {
                $feideuser = true;
            }
        }

        $orgmoderatedscopes = array();

        foreach ($this->getScopeAPIGKs($scopelist) as $scope => $apigk) {
            if ($apigk->isOrgModerated($scope)) {
                $orgmoderatedscopes[] = $scope;
            }
        }
        if (!$feideuser) {
            foreach ($orgmoderatedscopes as $scope) {
                unset($scopes[$scope]);
            }
        } else {
            foreach ($orgmoderatedscopes as $scope) {
                foreach ($realms as $realm) {
                    if (array_search($scope, $this->client->getOrgAuthorization($realm)) === false) {
                        unset($scopes[$scope]);
                    }
                }
            }
        }

        $result = array_keys($scopes);
        $logdata = array(
            'requested_scopes' => $requestedScopes,
            'client_scopes' => $clientscopes,
            'org_moderated_scopes' => $orgmoderatedscopes,
            'feideuser' => $feideuser,
            'result_scopes' => $result,
        );
        if ($feideuser) {
            $logdata['user_realms'] = $realms;
        }
        Logger::debug('Evaluated scopes', $logdata);
        return $result;
    }

    public function getAuthorization() {
        $this->authorization = null;
        if ($this->user !== null) {
            $this->authorization = $this->storage->getAuthorization($this->user->userid, $this->client->id);
        }
        $this->evaluateScopes();
    }

    /**
     * Setting the user at initialization or later is required in order to get information about scopes that is authorized.
     *
     * @param [type] $user [description]
     */
    public function setUser($user) {
        $this->user = $user;
        $this->getAuthorization();
    }

    /**
     * Throw an exception if the user it not set.
     * @return [type] [description]
     */
    protected function requireUser() {
        if ($this->user === null) {
            throw new \Exception("An authenticated user is not set, and we may therefor not obtain information about authorized scopes.");
        }
    }


    /**
     * Will add all remaining scopes from scopesinquestion to the authorization object.
     * @return [type] [description]
     */
    public function getUpdatedAuthorization($scopes, $apigkScopes) {
        $this->requireUser();
        $a = $this->authorization;
        if ($a === null) {
            $a = new Authorization([
                "clientid" => $this->client->id,
                "userid" => $this->user->userid,
                "issued" => new \FeideConnect\Data\Types\Timestamp(),
                "scopes" => [],
            ]);
        }
        $q = $this->getScopesInQuestion();
        $good_scopes = [];
        foreach ($scopes as $scope) {
            if (in_array($scope, $q)) {
                $good_scopes[] = $scope;
            }
        }
        $a->addScopes($good_scopes);

        if (isset($apigkScopes)) {
            $requestedApigkScopes = $this->getAPIGKscopes();
            foreach ($apigkScopes as $apigkid => $gkscopes) {
                if (isset($requestedApigkScopes[$apigkid])) {
                    $apigkScopes[$apigkid] = array_intersect($requestedApigkScopes[$apigkid], $gkscopes);
                }
            }
        }
        $a->apigk_scopes = $apigkScopes;
        return $a;
    }


    private function evaluateScopes() {

        $this->scopesInQuestion = $this->getEffectiveScopes();

        if ($this->user === null) {
            return;
        }

        if ($this->authorization === null) {
            $this->scopesRemaining = $this->scopesInQuestion;
        } else {
            $this->scopesRemaining = $this->authorization->remainingScopes($this->scopesInQuestion);
        }

        Logger::debug('OAuth AuthorizationEvaluator evaluateScopes()', array(
            'clientScopes' => $this->client->getScopeList(),
            'requestedScopes' => $this->request->getScopeList(),
            'requestedScopesStr' => $this->request->scope,
            'authorization' => $this->authorization,
            'scopesInQuestion' => $this->scopesInQuestion,
            'scopesRemaining' => $this->scopesRemaining,
            'needsAuthorization' => $this->needsAuthorization()
        ));

        // echo '<pre>';
        // print_r(array(
        //     'scopeEvaluation' => [
        //         'clientScopes' => $this->client->getScopeList(),
        //         'requestedScopes' => $this->request->getScopeList(),
        //         'requestedScopesStr' => $this->request->scope,
        //         'authorization' => ($this->authorization === null ? null : $this->authorization->getAsArray()),
        //         'scopesInQuestion' => $this->scopesInQuestion,
        //         'scopesRemaining' => $this->scopesRemaining,
        //         'needsAuthorization' => $this->needsAuthorization()
        //     ])); exit;

    }

    public function getScopesInQuestion() {
        return $this->scopesInQuestion;
    }

    public function hasScopeInQuestion($scope) {
        foreach ($this->scopesInQuestion as $sc) {
            if ($scope === $sc) {
                return true;
            }
        }
        return false;
    }

    public function getRemainingScopes() {
        $this->requireUser();
        return $this->scopesRemaining;
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
        $this->apis[$apigkid] = $apigk;
        return $apigk;

    }

    public function getScopeAPIGKs($scopes) {
        $apis = [];
        foreach ($scopes as $scope) {
            if (!APIGK::isApiScope($scope)) {
                continue;
            }
            // echo "Processing " . $matches[1];

            $apigkid = APIGK::parseScope($scope)[0];
            try {
                $apigk = $this->getAPI($apigkid);
            } catch (\Exception $e) {
                continue;
            }
            $apis[$scope] = $apigk;
        }
        return $apis;
    }

    public function getAPIGKscopes() {
        $scopes = $this->scopesInQuestion;
        $apigkScopes = [];
        foreach ($this->getScopeAPIGKs($scopes) as $scope => $apigk) {
            $apigkScopes[$apigk->id] = $apigk->scopes;
        }
        return $apigkScopes;
    }

    /**
     * Does the user needs to perform a new authorization?
     * @return [type] [description]
     */
    public function needsAuthorization() {

        $this->requireUser();

        if ($this->authorization === null) {
            return true;
        }
        if (!empty($this->scopesRemaining)) {
            return true;
        }
        foreach ($this->getAPIGKscopes() as $apigkid => $scopes) {
            if (isset($scopes) && count($scopes) > 0) {
                if (!isset($this->authorization->apigk_scopes) || !isset($this->authorization->apigk_scopes[$apigkid])) {
                    return true;
                }
                if (array_intersect($scopes, $this->authorization->apigk_scopes[$apigkid]) !== $scopes) {
                    return true;
                }
            }
        }

        return false;

    }

    public function getValidatedRedirectURI() {


        $configuredRedirectURI = $this->client->redirect_uri;
        $requestedRedirectURI = $this->request->redirect_uri;

        $uri = null;
        if (empty($this->request->redirect_uri)) {
            // Use the first of the configured redirectURIs if multiple are configured.
            $uri = $configuredRedirectURI[0];
        } else if (in_array($requestedRedirectURI, $configuredRedirectURI)) {
            $uri = $requestedRedirectURI;
        }

        if ($uri !== null) {
            Logger::debug('OAuth AuthorizationEvaluator getValidatedRedirectURI()', array(
                'configuredRedirectURI' => $configuredRedirectURI,
                'requestedRedirectURI' => $requestedRedirectURI,
                'effectiveRedirectURI' => $uri,
            ));
            return $uri;
        }

        Logger::error('OAuth AuthorizationEvaluator not able to resolve a valid redirect_uri for client', array(
            'configuredRedirectURI' => $configuredRedirectURI,
            'requestedRedirectURI' => $requestedRedirectURI,
        ));
        throw new Exceptions\OAuthException('invalid_request', 'Not able to resolve a valid redirect_uri for client');


    }


}
