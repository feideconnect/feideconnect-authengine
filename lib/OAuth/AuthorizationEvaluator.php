<?php


namespace FeideConnect\OAuth;

use FeideConnect\Logger;
use FeideConnect\OAuth\Exceptions;
use FeideConnect\Data\Models\Authorization;

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

        if ($user !== null) {
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
        $this->authorization = null;
        if ($user !== null) {
            $this->authorization = $this->storage->getAuthorization($this->user->userid, $this->client->id);
        }
        $this->evaluateScopes();
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
    public function getUpdatedAuthorization() {
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
        $a->addScopes($q);
        return $a;
    }


    private function evaluateScopes() {

        $this->scopesInQuestion = OAuthUtils::evaluateScopes($this->client, $this->user, $this->request->scope);

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
