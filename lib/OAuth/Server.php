<?php

namespace FeideConnect\OAuth;

use FeideConnect\OAuth\Exceptions\OAuthException;
use FeideConnect\OAuth\Exceptions\UserCannotAuthorizeException;

use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\OpenIDConnect;

use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\HTTP\JSONResponse;

use FeideConnect\Logger;
use FeideConnect\Data\Models;
use FeideConnect\Data\StorageProvider;
use FeideConnect\OAuth\Exceptions;
use FeideConnect\Utils;
use FeideConnect\Utils\Validator;
use FeideConnect\Config;

/**
 * Implementation of an OAuth Server
 */
class Server {

    protected $storage;
    protected $server;
    protected $auth;

    public function __construct() {

        $this->storage = StorageProvider::getStorage();

    }


    /**
     * OAuth Authorization Endpoint
     *
     * handles incoming authorization request
     * authenticates the user
     * loads client and user info
     * check if user has authorized the client with the scopes
     * if not, run UI with asking for authorization
     * process response from UI
     * issue access token and send response.
     *
     * Handle different OAuth flows
     *
     * Perform authorization (authentication and check authorization)
     * In contrast to So_Server, this implementation of the authorization endpoint also
     * handles authentication, which is not implemented in the gneric So_Server.
     *
     * @return [type] [description]
     */
    public function authorizationEndpoint() {


        try {
            /**
             * --- We've now dealted with all error responses that is returned from other systems..
             * Lets move on to processing the OAuth authorization request.
             *
             */

            // Parse the incoming Authorization Request.
            $request = new Messages\AuthorizationRequest($_REQUEST);
            $openidConnect = false;
            if (in_array('openid', $request->getScopeList())) {
                $request = new OpenIDConnect\Messages\AuthorizationRequest($_REQUEST);
                $openidConnect = true;
            }
            $pAuthorization = new OAuthAuthorization($request, $openidConnect);

            return $pAuthorization->process();

        } catch (UserCannotAuthorizeException $e) {
            $data = array();
            $response = (new TemplatedHTMLResponse('agelimit'))->setData($data);
            return $response;

        } catch (OAuthException $e) {
            $msg = array(
                'error' => $e->code,
                'error_description' => $e->getMessage(),
                'error_uri' => 'https://docs.dataporten.no',
            );
            Logger::error('OAuth Error Response at Authorization endpoint.', $msg);


            // echo "errror<pre>"; print_r($e); exit;

            $response = new Messages\ErrorResponse($msg);
            if ($e->state !== null) {
                $response->state = $e->state;
            }
            if ($e->redirectURI !== null) {
                return $response->getRedirectResponse($e->redirectURI, $e->useHashFragment);
            }

            return $response->getJSONResponse($e->httpcode);

        }



    }


    protected function validateClientCredentials($clientid, $password) {
        if (!Validator::validateUUID($clientid)) {
            throw new OAuthException('invalid_client', 'Invalid client_id parameter');
        }

        $client = $this->storage->getClient($clientid);
        if ($client === null) {
            throw new OAuthException('invalid_client', 'Request was on behalf of a nonexisting client');
        }

        if ($client->client_secret !== $password) {
            throw new OAuthException('invalid_client', 'Wrong client credentials. Incorrect client_secret.');
        }

        return $client;

    }

    protected function validateClientAuthorization() {
        if (empty($_SERVER['PHP_AUTH_USER'])) {
            throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing username)');
        }
        if (empty($_SERVER['PHP_AUTH_PW'])) {
            throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing password)');
        }

        $clientid = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        return $this->validateClientCredentials($clientid, $password);

    }

    protected function validateTestUserCredentials($tokenrequest) {
        if (empty($tokenrequest->username)) {
            throw new OAuthException('invalid_grant', 'Unable to authenticate resource owner (missing username)');
        }
        if (empty($tokenrequest->password)) {
            throw new OAuthException('invalid_grant', 'Unable to authenticate resource owner (missing password)');
        }

        $testUsers = Config::getValue("testUsers", []);

        if (!isset($testUsers[$tokenrequest->username])) {
            throw new OAuthException('invalid_grant', 'Unable to authenticate resource owner (invalid user)');
        }
        if (!isset($testUsers[$tokenrequest->username]["password"])) {
            throw new OAuthException('invalid_grant', 'Unable to authenticate resource owner (invalid user configuration)');
        }
        if ($testUsers[$tokenrequest->username]["password"] !== $tokenrequest->password) {
            throw new OAuthException('invalid_grant', 'Unable to authenticate resource owner (invalid password)');
        }

        $user = $this->storage->getUserByUserIDsec($tokenrequest->username);
        if ($user === null) {
            throw new OAuthException('invalid_grant', 'Authenticated user does not have a user record.');
        }

        return $user;
    }

    protected function tokenFromCode($tokenrequest) {

        if (empty($tokenrequest->code)) {
            throw new OAuthException('invalid_request', 'Request was missing the required code parameter');
        }

        if (!empty($_SERVER['PHP_AUTH_PW'])) {
            $client = $this->validateClientAuthorization();
        } elseif (isset($tokenrequest->client_id) && isset($tokenrequest->client_secret)) {
            $password = $tokenrequest->client_secret;
            $client = $this->validateClientCredentials($tokenrequest->client_id, $password);
        } else {
            throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing password/client_secret)');
        }

        if (!Validator::validateID($tokenrequest->code)) {
            throw new OAuthException('invalid_request', 'Invalid code parameter');
        }
        $code = $this->storage->getAuthorizationCode($tokenrequest->code);
        if ($code === null) {
            throw new OAuthException('invalid_grant', 'Provided Authorization Code was not found.');
        }

        if (!$code->stillValid()) {
            throw new OAuthException('invalid_grant', 'Provided Authorization Code is expired.');
        }

        if ($code->clientid !== $client->id) {
            throw new OAuthException('invalid_grant', 'Provided Authorization Code was not issued to this client.');
        }

        if (!empty($code->redirect_uri)) {
            if (empty($tokenrequest->redirect_uri)) {
                throw new OAuthException('invalid_request', 'Request was missing the required redirect_uri parameter');
            }

            if ($tokenrequest->redirect_uri !== $code->redirect_uri) {
                throw new OAuthException('invalid_request', 'Mismatching redirect_uris provided in the token request compared to the authorization request');
            }

        }

        $user = $this->storage->getUserByUserID($code->userid);

        // Now, we consider us completed with this code, and we ensure that it cannot be used again
        $this->storage->removeAuthorizationCode($code);

        $idtoken = null;
        if (isset($code->idtoken)) {
            $idtoken = $code->idtoken;
        }

        $tokenresponse = OAuthUtils::generateTokenResponse($client, $user, $code->scope, $code->apigk_scopes, "authorization code", null, $idtoken, $code->acr);

        return $tokenresponse->getJSONResponse();
    }

    /**
     * Implementation of the OAuth 2.0 Token Endpoint.
     * @return [type] [description]
     */
    public function token() {

        try {
            $tokenrequest = new Messages\TokenRequest($_POST);
            // $tokenrequest->parseServer($_SERVER);

            Logger::debug('OAuth Received incoming AccessTokenRequest.', [
                'tokenrequest' => $tokenrequest,
            ]);

            if ($tokenrequest->grant_type === 'authorization_code') {
                return $this->tokenFromCode($tokenrequest);

            }

            $client = $this->validateClientAuthorization();

            if ($tokenrequest->grant_type === 'client_credentials') {
                $user = null;

            } else if ($tokenrequest->grant_type === 'password') {
                $user = $this->validateTestUserCredentials($tokenrequest);

            } else {
                throw new OAuthException('unsupported_grant_type', 'Invalid [grant_type] provided to token endpoint.');
            }

            $aevaluator = new AuthorizationEvaluator($this->storage, $client, $tokenrequest, $user);

            $requestedScopes = $aevaluator->getScopesInQuestion();
            $tokenresponse = OAuthUtils::generateTokenResponse($client, $user, $requestedScopes, $aevaluator->getAPIGKscopes(), $tokenrequest->grant_type);
            return $tokenresponse->getJSONResponse();

        } catch (OAuthException $e) {
            $msg = array(
                'error' => $e->code,
                'error_description' => $e->getMessage(),
                'error_uri' => 'https://docs.dataporten.no',
            );
            Logger::error('OAuth Error Response at Token endpoint.', $msg);

            $response = new Messages\ErrorResponse($msg);
            return $response->getJSONResponse($e->httpcode);

        }
    }
}
