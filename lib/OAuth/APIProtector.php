<?php

namespace FeideConnect\OAuth;

use FeideConnect\OAuth\Exceptions\APIAuthorizationException;
use FeideConnect\Utils\Validator;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Logger;

class APIProtector {
    public static $instance = null;

    protected $accesstoken = null;
    protected $tokenvalue = null;
    protected $storage = null;

    protected $client = null;
    protected $user = null;

    public function __construct($headers = null) {
        if ($headers===null) {
            $headers = getallheaders();
        }
        $this->tokenvalue = $this->getBearerToken($headers);
        $this->storage = StorageProvider::getStorage();

    }

    public static function get() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Extract the Bearer token string from the Authorization header
     * if present. If not present, return null.
     * @return [type] [description]
     */
    protected function getBearerToken($hdrs) {
        foreach ($hdrs as $h => $v) {
            if (strtolower($h) === 'authorization') {
                if (preg_match('/^Bearer\s(.*?)$/i', $v, $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        // Optionally, but not reccomended access token may be provided as an query string parameter
        if (isset($_REQUEST['access_token']) && !empty($_REQUEST['access_token'])) {
            return trim($_REQUEST['access_token']);
        }

        return null;
    }



    public function requireToken() {

        if ($this->accesstoken !== null) {
            return $this;
        }

        if ($this->tokenvalue === null) {
            throw new APIAuthorizationException('Authorization Bearer Token not present in request');
        }

        if (!Validator::validateUUID($this->tokenvalue)) {
            throw new APIAuthorizationException('Authorization Bearer Token has invalid format', 'invalid_request');
        }

        $this->accesstoken = $this->storage->getAccessToken($this->tokenvalue);
        if ($this->accesstoken === null) {
            throw new APIAuthorizationException('Authorization Bearer Token was not valid', 'invalid_token');
        }

        if (!$this->accesstoken->stillValid()) {
            $this->accesstoken = null;
            throw new APIAuthorizationException('Authorization Bearer Token was expired', 'invalid_token');
        }

        Logger::info('Authenticated request using an Bearer Access Token', array(
            'accesstoken' => $this->accesstoken,
        ));

        return $this;

    }


    public function requireClient() {

        if ($this->client !== null) {
            return $this;
        }
        $this->requireToken();

        if (empty($this->accesstoken->clientid)) {
            throw new APIAuthorizationException('Authorization Bearer Token was not associated with an authenticated client', 'invalid_token');
        }

        $this->client = $this->storage->getClient($this->accesstoken->clientid);
        if ($this->client === null) {
            throw new APIAuthorizationException('Authorization Bearer Token was associated with an client that was not found. May be it has been removed.', 'invalid_token');
        }

        return $this;


    }

    public function checkUser() {

        if ($this->user !== null) {
            return $this;
        }
        $this->requireToken();

        if (empty($this->accesstoken->userid)) {
            return $this;
        }

        $this->user = $this->storage->getUserByUserID($this->accesstoken->userid);
        return $this;
    }

    public function requireUser() {

        if ($this->user !== null) {
            return $this;
        }
        $this->requireToken();

        if (empty($this->accesstoken->userid) || $this->accesstoken->userid === '00000000-0000-0000-0000-000000000000') {
            throw new APIAuthorizationException('Authorization Bearer Token was not associated with an authenticated user', 'invalid_token');
        }

        $this->user = $this->storage->getUserByUserID($this->accesstoken->userid);
        if ($this->user === null) {
            throw new APIAuthorizationException('Authorization Bearer Token was associated with an user that was not found. May be it has been removed.', 'invalid_token');
        }

        return $this;

    }


    public function getClient() {
        $this->requireClient();
        return $this->client;
    }

    public function getUser() {
        $this->checkUser();
        return $this->user;
    }




    public function getScopes() {
        $this->requireToken();
        return $this->accesstoken->scope;
    }

    public function hasScopes($scopes) {
        $this->requireToken();
        return $this->accesstoken->hasScopes($scopes);
    }

    public function requireScopes($scopes) {
        assert('is_array($scopes)');
        $this->requireToken();
        if (!$this->accesstoken->hasScopes($scopes)) {
            Logger::info('Authorization Bearer Token does not have sufficient scope for this operation..', array(
                'scopesRequired' => $scopes,
                'scopesInplace' => $this->accesstoken->scope,
            ));
            throw new APIAuthorizationException('Authorization Bearer Token does not have sufficient scope for this operation.', 'insufficient_scope');
        }
        return $this;
    }




}
