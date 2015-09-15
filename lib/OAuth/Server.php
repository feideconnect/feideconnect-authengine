<?php

namespace FeideConnect\OAuth;


use FeideConnect\OAuth\Exceptions\OAuthException;
use FeideConnect\OAuth\Exceptions\UserCannotAuthorizeException;


use FeideConnect\OAuth\Protocol\OAuthAuthorization;


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

	function __construct() {

		$this->storage = StorageProvider::getStorage();

	}


	function generateTokenResponse($client, $user, $requestedScopes) {
		$expires_in = 3600*8; // 8 hours
		if (in_array('longterm', $requestedScopes)) {
			$expires_in = 3600*24*680; // 680 days
		}

		$pool = new AccessTokenPool($client, $user);
		$accesstoken = $pool->getToken($requestedScopes, false, $expires_in);
		// TODO Verify that this saveToken was successfull before continuing.

		$tokenresponse = Messages\TokenResponse::generateFromAccessToken($accesstoken);
		return $tokenresponse;
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

			// Decide whether to run in passive mode. In passive mode no UI is displayed to the enduser.
			$passive = false;
			if (isset($_REQUEST["passive"]) && $_REQUEST["passive"] === 'true') $passive = true;


			// Parse the incomming Authorization Request.
			$request = new Messages\AuthorizationRequest($_REQUEST);
			Logger::info('Successfully parsed OAuth Authorization Request. Next up: resolve client.', array(
				'request' => $request->asArray(),
				'passive' => $passive
			));

			$pAuthorization = new OAuthAuthorization($request);
			return $pAuthorization->process();



		} catch (UserCannotAuthorizeException $e) {


			$data = array();
			$response = (new TemplatedHTMLResponse('agelimit'))->setData($data);
			return $response;

		} catch (OAuthException $e) {

			$msg = array(
				'error' => $e->code,
				'error_description' => $e->getMessage(),
				'error_uri' => 'https://feideconnect.no',
			);
			Logger::error('OAuth Error Response at Authorization endpoint.', $msg);


			// echo "errror<pre>"; print_r($e); exit;

			$response = new Messages\ErrorResponse($msg);
			if ($e->state !== null) {
				$response->state = $e->state;
			}
			if ($e->redirectURI !== null) {
				return $response->sendRedirect($e->redirectURI, $e->useHashFragment);
			}
			
			return $response->sendBodyJSON($e->httpcode);
			
		}



	}


	



	/**
	 * Implementation of the OAuth 2.0 Token Endpoint.
	 * @return [type] [description]
	 */
	public function token() {

		try {




			$tokenrequest = new Messages\TokenRequest($_REQUEST);
			// $tokenrequest->parseServer($_SERVER);


			Logger::info('OAuth Received incomming AccessTokenRequest.', array(
				'tokenrequest' => $tokenrequest->getAsArray(),
				'rawrequest' => $_REQUEST,
			));

			
			if ($tokenrequest->grant_type === 'authorization_code') {
				

				Logger::info('OAuth Processing an authorization_code request.', array(
					'tokenrequest' => $tokenrequest->getAsArray(),
					'rawrequest' => $_REQUEST,
				));

				if (empty($tokenrequest->code)) {
					throw new OAuthException('invalid_request', 'Request was missing the required code parameter');
				}
				if (empty($tokenrequest->client_id)) {
					throw new OAuthException('invalid_request', 'Request was missing the required client_id parameter');
				}

				if (!Validator::validateID($tokenrequest->client_id)) {
					throw new OAuthException('invalid_request', 'Invalid client_id parameter');	
				}

				$clientid = $tokenrequest->client_id;
				$client = $this->storage->getClient($clientid);
				if ($client === null) {
					throw new OAuthException('invalid_client', 'Request was on behalf of a nonexisting client');
				}

				$password = null;
				if (!empty($_SERVER['PHP_AUTH_PW'])) {
					$password = $_SERVER['PHP_AUTH_PW'];
				} else if (isset($tokenrequest->client_secret)) {
					$password = $tokenrequest->client_secret;
				}

				if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== $clientid) {
					throw new OAuthException('invalid_client', 'Wrong client credentials. Client id does not match the request.');
				}

				Logger::info('OAuth client identified.', [
					'client' => $client->getAsArray(),
					'username' => $clientid,
					'password' => $password,
				]);

				if ($password === null) {
					throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing password/client_secret)');
				}

				if ($client->client_secret !== $password) {
					throw new OAuthException('invalid_client', 'Wrong client credentials. Incorrect client_secret.');
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

					if (empty($tokenrequest->redirect_uri)) 
						throw new OAuthException('invalid_request', 'Request was missing the required redirect_uri parameter');

					if ($tokenrequest->redirect_uri !== $code->redirect_uri)
						throw new OAuthException('invalid_request', 'Mismatching redirect_uris provided in the token request compared to the authorization request');

				}


				$user = $this->storage->getUserByUserID($code->userid);



				// Now, we consider us completed with this code, and we ensure that it cannot be used again
				$this->storage->removeAuthorizationCode($code);

				$tokenresponse = $this->generateTokenResponse($client, $user, $code->scope);

				if (isset($code->idtoken) && $code->idtoken !== null) {
					$tokenresponse->idtoken = $code->idtoken;
				}

				Logger::info('OAuth Access Token is now issued as part of the authorization code flow at the token endpoint.', array(
					'user' => $user->getAsArray(),
					'client' => $client->getAsArray(),
					'accesstoken' => $tokenresponse->access_token->getAsArray(),
					'tokenresponse' => $tokenresponse->getAsArray(),
				));

				return $tokenresponse->sendBodyJSON();


				
			} else if ($tokenrequest->grant_type === 'client_credentials') {






				if (empty($_SERVER['PHP_AUTH_USER'])) 
					throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing username)');
				if (empty($_SERVER['PHP_AUTH_PW'])) 
					throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing password)');

				$clientid = $_SERVER['PHP_AUTH_USER'];


				if (!Validator::validateID($clientid)) {
					throw new OAuthException('invalid_request', 'Invalid client_id parameter');	
				}

				$client = $this->storage->getClient($clientid);
				if ($client === null) {
					throw new OAuthException('invalid_client', 'Request was on behalf of a nonexisting client');
				}

				if ($client->client_secret !== $_SERVER['PHP_AUTH_PW'])
					throw new OAuthException('invalid_client', 'Wrong client credentials. Incorrect client_secret.');


				$requestedScopes = $client->getScopeList();
				if (!empty($tokenrequest->scope)) {
					// Only consider scopes that the client is authorized to ask for.
					$requestedScopes = array_intersect($tokenrequest->scope, $requestedScopes);
				}


				$tokenresponse = $this->generateTokenResponse($client, $user, $requestedScopes);
				return $tokenresponse->sendBodyJSON();

				
			} else if ($tokenrequest->grant_type === 'password') {





				if (empty($_SERVER['PHP_AUTH_USER'])) 
					throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing username)');
				if (empty($_SERVER['PHP_AUTH_PW'])) 
					throw new OAuthException('invalid_client', 'Unable to authenticate the request on behalf of a client (missing password)');

				$clientid = $_SERVER['PHP_AUTH_USER'];


				if (!Validator::validateID($clientid)) {
					throw new OAuthException('invalid_request', 'Invalid client_id parameter');	
				}

				$client = $this->storage->getClient($clientid);
				if ($client === null) {
					throw new OAuthException('invalid_client', 'Request was on behalf of a nonexisting client');
				}

				if ($client->client_secret !== $_SERVER['PHP_AUTH_PW'])
					throw new OAuthException('invalid_client', 'Wrong client credentials. Incorrect client_secret.');


				$requestedScopes = $client->getScopeList();
				if (!empty($tokenrequest->scope)) {
					// Only consider scopes that the client is authorized to ask for.
					$requestedScopes = array_intersect($tokenrequest->scope, $requestedScopes);
				}



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

			} else {
				throw new OAuthException('unsupported_grant_type', 'Invalid [grant_type] provided to token endpoint.');
			}
			

		} catch (OAuthException $e) {

			$msg = array(
				'error' => $e->code,
				'error_description' => $e->getMessage(),
				'error_uri' => 'https://feideconnect.no',
			);
			Logger::error('OAuth Error Response at Token endpoint.', $msg);

			$response = new Messages\ErrorResponse($msg);
			return $response->sendBodyJSON($e->httpcode);

			
		}



	}


	
}



