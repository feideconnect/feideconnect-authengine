<?php

namespace FeideConnect\OAuth;


use FeideConnect\OAuth\Exceptions\OAuthException;
use FeideConnect\OAuth\Exceptions\UserCannotAuthorizeException;
use FeideConnect\OAuth\AuthorizationUI;
use FeideConnect\Authentication\Authenticator;
use FeideConnect\Authentication\UserMapper;

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
		$this->auth = new Authenticator();

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


			// If SimpleSAML_Auth_State_exceptionId query parameter is set, then something failed 
			// while performing authentication.
			if (!empty($_REQUEST['SimpleSAML_Auth_State_exceptionId'])) {

				// The most likely error is that we are not able to perform passive authentication.
				throw new OAuthException('access_denied', 'Unable to perform passive authentication [1]');

			} else if (isset($_REQUEST['error']) && $_REQUEST['error'] === '1') {

				// The most likely error is that we are not able to perform passive authentication.
				throw new OAuthException('access_denied', 'Unable to perform passive authentication [2]');
			}

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



			$client = $this->storage->getClient($request->client_id);
			if ($client === null) throw new OAuthException('invalid_client', 'Could not look up the specified client.');
			// $this->authorizationFailed('unauthorized_client', 'https://docs.uwap.org', 'Could not find this client.');

			Logger::info('Client resolved. Next up: authenticate user.', array(
				'client' => $client->getAsArray()
			));



			/**
			 * Ensure that the user is authenticated...
			 */

			$this->auth->req(false, true); // require($isPassive = false, $allowRedirect = false, $return = null
			$account = $this->auth->getAccount();

			
			$organization = $account->getOrg();
			
			$usermapper = new UserMapper($this->storage);
			$user = $usermapper->getUser($account, true, true, false);

			// echo '<pre>'; print_r($user); exit;

			Logger::info('OAuth authorization() User is now authenticationed. Next is authorization.', array(
				'user' => $user->getAsArray()
			));


			// TODO: Do we need to suport passive requests?? 




			$ae = new AuthorizationEvaluator($this->storage, $client, $request, $user);
			$redirect_uri = $ae->getValidatedRedirectURI();
			$scopesInQuestion = $ae->getScopesInQuestion();

			

			$aui = new AuthorizationUI($client, $request, $account, $user, $redirect_uri, $scopesInQuestion, $ae, $organization);



			if ($ae->needsAuthorization() ) {

				if ($passive) {
					throw new OAuthException('access_denied', 'User has not authorized, and were unable to perform passive authorization');
				}
			}

			if (isset($_REQUEST["verifier"])) {

				$verifier = $user->getVerifier();
				if ($verifier !== $_REQUEST["verifier"]) {
					throw new \Exception("Invalid verifier code.");
				}

				// echo '<pre>'; print_r($_REQUEST); exit;

				if (!isset($_REQUEST['bruksvilkar'])) {
					throw new \Exception('Bruksvilkår not accepted.');
				}
				if ($_REQUEST['bruksvilkar'] !== 'yes') {
					throw new \Exception('Bruksvilkår not accepted.');	
				}

				$authorization = $ae->getUpdatedAuthorization();

				// echo "<pre>";
				// print_r($user->getBasicUserInfo());
				// print_r($authorization->getAsArray()); exit;

				$user->usageterms = true;

				$user->updateUserBasics($account);


				$this->storage->saveAuthorization($authorization);


			} else {

				return $aui->show();
			}

		



			$expires_in = 3600*8; // 8 hours
			if (in_array('longterm', $scopesInQuestion)) {
				$expires_in = 3600*24*680; // 680 days
			}
			


			// Handle the various response types. code or token
			if ($request->response_type === 'token') {

				
				$pool = new AccessTokenPool($client, $user);
				$accesstoken = $pool->getToken($scopesInQuestion, false, $expires_in);

				// $accesstoken = Models\AccessToken::generate($client, $user, $scopesInQuestion, false, $expires_in);
				// 
				// TODO Verify that this saveToken was successfull before continuing.


				$tokenresponse = Messages\TokenResponse::generate($request, $accesstoken);

				Logger::info('OAuth Access Token is now issued.', array(
					'user' => $user->getAsArray(),
					'client' => $client->getAsArray(),
					'accesstoken' => $accesstoken->getAsArray(),
					'tokenresponse' => $tokenresponse->getAsArray(),
				));

				return $tokenresponse->sendRedirect($redirect_uri, true);


			} else if ($request->response_type === 'code') {


				$r = null;
				if (!empty($request->redirect_uri)) {
					$r = $request->redirect_uri;
				}

				$code = Models\AuthorizationCode::generate($client, $user, $r, $scopesInQuestion);
				$this->storage->saveAuthorizationCode($code);

				$tokenresponse = Messages\AuthorizationResponse::generate($request, $code);

				Logger::info('OAuth Authorization Code is now stored, and may be fetched via the token endpoint.', array(
					'user' => $user->getAsArrayLimited("userid", "userid_sec", "name"),
					'client' => $client->getAsArrayLimited(["id", "name", "redirect_uri"]),
					'code' => $code->getAsArray(),
					'tokenresponse' => $tokenresponse->getAsArray(),
				));

				return $tokenresponse->sendRedirect($request->redirect_uri);

			} else {
				throw new Exception('Unsupported response_type in request. Only supported code and token.');
			}

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
			Logger::error('OAuth Error Response at Token endpoint.', $msg);


			// header("HTTP/1.1 " . $e->getHTTPcode() );
			// header('Content-Type: application/json; charset: utf-8');


			$response = new Messages\ErrorResponse($msg);
			$response->setStatus($e->httpcode);
			return $response->sendBodyJSON();

			
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
				if ($code === null) 
					throw new OAuthException('invalid_grant', 'Provided Authorization Code was not found.');

				if (!$code->stillValid()) 
					throw new OAuthException('invalid_grant', 'Provided Authorization Code is expired.');

				if ($code->clientid !== $client->id) 
					throw new OAuthException('invalid_grant', 'Provided Authorization Code was not issued to this client.');

				if (!empty($code->redirect_uri)) {

					if (empty($tokenrequest->redirect_uri)) 
						throw new OAuthException('invalid_request', 'Request was missing the required redirect_uri parameter');

					if ($tokenrequest->redirect_uri !== $code->redirect_uri)
						throw new OAuthException('invalid_request', 'Mismatching redirect_uris provided in the token request compared to the authorization request');

				}


				$user = $this->storage->getUserByUserID($code->userid);


				$expires_in = 3600*8; // 8 hours
				if (in_array('longterm', $code->scope)) {
					$expires_in = 3600*24*680; // 680 days
				}

				$pool = new AccessTokenPool($client, $user);
				$accesstoken = $pool->getToken($code->scope, false, $expires_in);
				// TODO Verify that this saveToken was successfull before continuing.

				// Now, we consider us completed with this code, and we ensure that it cannot be used again
				$this->storage->removeAuthorizationCode($code);

				$tokenresponse = Messages\TokenResponse::generateFromAccessToken($accesstoken);

				Logger::info('OAuth Access Token is now issued as part of the authorization code flow at the token endpoint.', array(
					'user' => $user->getAsArray(),
					'client' => $client->getAsArray(),
					'accesstoken' => $accesstoken->getAsArray(),
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
				if (!empty($this->tokenrequest->scope)) {
					// Only consider scopes that the client is authorized to ask for.
					$requestedScopes = array_intersect($this->tokenrequest->scope, $requestedScopes);
				}


				$expires_in = 3600*8; // 8 hours
				if (in_array('longterm', $requestedScopes)) {
					$expires_in = 3600*24*680; // 680 days
				}

				$pool = new AccessTokenPool($client);
				$accesstoken = $pool->getToken($requestedScopes, false, $expires_in);

				$tokenresponse = Messages\TokenResponse::generateFromAccessToken($accesstoken);
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
				if (!empty($this->tokenrequest->scope)) {
					// Only consider scopes that the client is authorized to ask for.
					$requestedScopes = array_intersect($this->tokenrequest->scope, $requestedScopes);
				}


				$expires_in = 3600*8; // 8 hours
				if (in_array('longterm', $requestedScopes)) {
					$expires_in = 3600*24*680; // 680 days
				}

				$pool = new AccessTokenPool($client);
				$accesstoken = $pool->getToken($requestedScopes, false, $expires_in);

				$tokenresponse = Messages\TokenResponse::generateFromAccessToken($accesstoken);
				return $tokenresponse->sendBodyJSON();





			} else {
				throw new OAuthException('invalid_grant', 'Invalid [grant_type] provided to token endpoint.');
			}
			



		} catch (OAuthException $e) {

			$msg = array(
				'error' => $e->code,
				'error_description' => $e->getMessage(),
				'error_uri' => 'https://feideconnect.no',
			);
			Logger::error('OAuth Error Response at Token endpoint.', $msg);


			// header("HTTP/1.1 " . $e->getHTTPcode() );
			// header('Content-Type: application/json; charset: utf-8');


			$e->setHTTPcode();
			$response = new Messages\ErrorResponse($msg);
			$response->setStatus($e->httpcode);
			return $response->sendBodyJSON();

			
		}



	}


	
}



