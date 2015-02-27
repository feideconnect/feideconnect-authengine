<?php


/**
 * Feide Connect Auth Engine - main endpoint
 *
 * This file includes routing for the Feide Connect Auth Engine.
 */

namespace FeideConnect;


use FeideConnect\Utils\Router;
use FeideConnect\OAuth\Exceptions\OAuthException;
use FeideConnect\OAuth\Exceptions\APIAuthorizationException;
use FeideConnect\Authentication\UserID;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Exceptions\NotFoundException;
use FeideConnect\Data\StorageProvider;


require_once(dirname(dirname(__FILE__)) . '/lib/_autoload.php');

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Fri, 10 Oct 1980 04:00:00 GMT"); // Date in the past
header("Access-Control-Allow-Origin: *"); // CORS


// TODO : Verify the CORS header.
// header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: HEAD, GET, OPTIONS, POST");
header("Access-Control-Allow-Headers: Authorization, X-Requested-With, Origin, Accept, Content-Type");
header("Access-Control-Expose-Headers: Authorization, X-Requested-With, Origin, Accept, Content-Type");



try {

	$storage = StorageProvider::getStorage();

	if (Router::route('options', '.*', $parameters)) {
		header('Content-Type: application/json; charset=utf-8');
		exit;
	}

	$response = null;


	/**
	 *  The OAuth endpoints on core, typically the OAuth Server endpoints for communication with clients
	 *  using the API.
	 */
	if (Router::route(false, '^/oauth', $parameters)) {


		$oauth = new OAuth\Server();

		if (Router::route('post','^/oauth/authorization$', $parameters)) {
			// $oauth->processAuthorizationResponse();
			$oauth->authorizationEndpoint();

		} else if (Router::route('get', '^/oauth/authorization$', $parameters)) {
			$oauth->authorizationEndpoint();

		} else if (Router::route(false, '^/oauth/token$', $parameters)) {
			$oauth->token();

		} else {
			throw new Exception('Invalid request');
		}


	/*
	 *	Testing authentication using the auth libs
	 *	Both API auth and 
	 */
	} else if  (Router::route('get', '^/providerconfig$', $parameters)) {

		$base = $globalconfig->getBaseURL('auth') . 'oauth/';
		$providerconfig = array(
			'authorization' => $base . 'authorization',
			'token' => $base . 'token'
		);
		$response = $providerconfig;



	} else if (Router::route('get', '^/logout$', $parameters)) {



		$auth = new Authentication\Authenticator();
		$auth->logout();
		exit;


	} else if (Router::route('get', '^/loggedout$', $parameters)) {


		$data = [
			"head" => "You are now logged out"
		];


		$templateDir = Config::dir('templates');
		$mustache = new \Mustache_Engine(array(
			// 'cache' => '/tmp/uwap-mustache',
			'loader' => new \Mustache_Loader_FilesystemLoader($templateDir),
			// 'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
		));
		$tpl = $mustache->loadTemplate('loggedout');
		echo $tpl->render($data);
		exit;



	} else if  (Router::route('get', '^/favicon.ico$', $parameters)) {

		throw new NotFoundException('Favicon not found');


	} else if  (Router::route('get', '^/poc/([@a-z0-9\-]+)/([@a-z0-9\-]+)$', $parameters)) {


		$user = null;
		$client = null;

		


		if ($parameters[1] === '@me') {

			$auth = new Authentication\Authenticator();
			$auth->req(false, true); // require($isPassive = false, $allowRedirect = false, $return = null
			$account = $auth->getAccount();
			
			$usermapper = new Authentication\UserMapper($storage);
			$user = $usermapper->getUser($account, false, true, false);
			if ($user === null) {
				throw new Exception('User not found');
			}

		} else if ($parameters[1] === '@random') {


			$userlist = $storage->getUsers();
			function pickUser($userlist) {
				if (count($userlist) <1) throw new Exception('Cannot generate before we got a list of users generated.');
				$k = array_rand($userlist);
				return $userlist[$k];
			}
			$user = pickUser($userlist);



		} else if ($parameters[1] === '@none') {


		} else {

			$user = $storage->getUserByUserID($parameters[1]);
			if ($user === null) {
				throw new Exception('User not found');
			}

		}







		if ($parameters[2] === '@random') {

			$clientlist = $storage->getClients();
			function pickClient($clientlist) {
				if (count($clientlist) <1) throw new Exception('Cannot generate before we got a list of client generated.');
				$k = array_rand($clientlist);
				return $clientlist[$k];
			}
			$client = pickClient($clientlist);


		} else {


			$client = $storage->getClient($parameters[2]);


		}




		if ($client === null) {
			throw new \Exception('Client not found');
		}


		$token = new \FeideConnect\Data\Models\AccessToken($storage);
		$token->access_token = \FeideConnect\Data\Model::genUUID();
		$token->clientid = $client->id;
		if ($user !== null) {
			$token->userid = $user->userid;	
		}
		
		$token->scope = $client->scopes;
		$token->token_type = 'bearer';
		$token->validuntil = time() + 3600;
		$token->issued = time();

		$storage->saveToken($token);

		
		header('Content-Type: text/plain; charset=utf-8');

		if ($user !== null) {

			echo "You are about to get a token associated with this user: \n\n";
			// print_r($user);
			print_r($user->getBasicUserInfo());
			echo "\n\n";


		} else {

			echo "You are about to get a token that are not associated with any users. \n\n";

		}

		echo "And you are about to generate a token for the following client \n";

		$cinfo = $client->getAsArray();
		$cinfo['logo'] = '...';

		print_r($cinfo);

		echo "\n\n";
		echo "And finally, here is the token: \n\n";

		print_r($token->getAsArray());

		echo "\n\n";
		echo "you can use this token like this: \n";
		echo 'curl -H "Authorization: Bearer ' . $token->access_token . '" https://api.feideconnect.no/test/user ';
		echo "\n\n";
		exit;




	// OpenID Connect Discovery 1.0
	// http://openid.net/specs/openid-connect-discovery-1_0.html
	} else if  (Router::route('get', '^/\.well-known/openid-configuration$', $parameters)) {

		$base = $globalconfig->getBaseURL('auth') . 'oauth/';
		$providerconfig = array(
			'issuer' => $globalconfig->getValue('connect.issuer'),
			'authorization_endpoint' => $base . 'authorization',
			'token_endpoint' => $base . 'token',
			'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
			'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
			'userinfo_endpoint' =>  $base . 'userinfo',
		);
		$response = $providerconfig;


	/*
	 *	Testing authentication using the auth libs
	 *	Both API auth and 
	 */
	} else if  (Router::route('get', '^/userinfo$', $parameters)) {



		$apiprotector = new OAuth\APIProtector();
		$user = $apiprotector
			->requireClient()->requireUser()->requireScopes(['userinfo'])
			->getUser();
		$client = $apiprotector->getClient();


		$allowseckeys = ['userid'];
		$allowseckeys[] = 'p';
		$allowseckeys[] = 'feide';

		$includeEmail = true;

		$response = [
			'user' => $user->getBasicUserInfo($includeEmail, $allowseckeys),
			'audience' => $client->id,
		];


	/*
	 *	Testing authentication using the auth libs
	 *	Both API auth and 
	 */
	} else if  (Router::route('get', '^/userinfo/authinfo$', $parameters)) {


		$apiprotector = new OAuth\APIProtector();
		$user = $apiprotector
			->requireClient()->requireUser()->requireScopes(['userinfo'])
			->getUser();
		$client = $apiprotector->getClient();

		$allowseckeys = ['userid'];
		$allowseckeys[] = 'p';
		$allowseckeys[] = 'feide';

		$includeEmail = true;

		$authorizations = $storage->getAuthorizationsByUser($user);

		$response = [
			"authorizations" => [],
			"tokens" => [],
		];
		foreach($authorizations AS $auth) {
			$response["authorizations"][] = $auth->getAsArray();
		}


		// $response = [
		// 	'user' => $user->getBasicUserInfo($includeEmail, $allowseckeys),
		// 	'audience' => $client->id,
		// ];
		



	/*
	 *	Testing authentication using the auth libs
	 *	Both API auth and 
	 */
	} else if  (Router::route('get', '^/user/media/([a-zA-Z0-9\-:]+)$', $parameters)) {

		header('Content-Type: image/jpeg');
		$userid = new UserID($parameters[1]);

		if ($userid->prefix !== 'p') {
			throw new Exception('You may only lookup users by primary keys');
		}

		// $user = $storage->getUserByUserID($userid->local);
		// $userinfo = $user->getUserInfo();

		$user = $storage->getUserByUserIDsec($parameters[1]);
		$userinfo = $user->getUserInfo();



		// echo '<pre>';
		// print_r($user); exit;
		if (!empty($userinfo['profilephoto'])) {
			echo $userinfo['profilephoto'];
		} else {
			$f = file_get_contents(dirname(__DIR__) . '/www/static/media/default-profile.jpg');
			echo $f;
		}
	
		exit;


	/*
	 *	Testing authentication using the auth libs
	 *	Both API auth and 
	 */
	} else if  (Router::route('get', '^/client/media/([a-zA-Z0-9\-:]+)$', $parameters)) {

		header('Content-Type: image/jpeg');
		$clientid = $parameters[1];
		$client = $storage->getClient($clientid);

// 		echo '<pre>';
// print_r($client); exit;

		if (!empty($client->logo)) {
			echo $client->logo;
		} else {
			$f = file_get_contents(dirname(__DIR__) . '/www/static/media/default-client.jpg');
			echo $f;
		}
	
		exit;


	/*
	 *	Testing authentication using the auth libs
	 *	Both API auth and 
	 */
	} else if  (Router::route('get', '^/disco$', $parameters)) {



		// echo '<pre>'; print_r($_REQUEST); exit;


		header('Content-Type: text/html; charset: utf-8');

		$data = array();
		$data["disco"] = Config::readJSONfile("disco.json");
		$data["return"] = $_REQUEST["return"];
		$data["returnIDParam"] = $_REQUEST["returnIDParam"];

		$templateDir = Config::dir('templates');
		$mustache = new \Mustache_Engine(array(
			// 'cache' => '/tmp/uwap-mustache',
			'loader' => new \Mustache_Loader_FilesystemLoader($templateDir),
			// 'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
		));
		$tpl = $mustache->loadTemplate('disco');

		echo $tpl->render($data);

		exit;



	/*
	 *	Testing authentication using the auth libs
	 *	Both API auth and 
	 */
	} else if  (Router::route('get', '^/auth$', $parameters)) {

		$auth = new Authentication\Authenticator();
		$auth->req(false, true); // require($isPassive = false, $allowRedirect = false, $return = null

		$account = $auth->getAccount();

		// $res = $auth->storeUser();
		// 
		// $response = array('account' => $account->getAccountID());
		$response = [
			"account" => [
				"userids" => $account->getUserIDs(),
				"sourceID" => $account->getSourceID(),
				"name" => $account->getName(),
				"mail" => $account->getMail()
			]
		];

		
		$usermapper = new Authentication\UserMapper($storage);

		$user = $usermapper->getUser($account, false, true, false);
		// header('Content-type: text/plain');
		// print_r($user); exit;
		if (isset($user)) {
			$response['user'] = $user->getAsArray();	
			$response['userinfo'] = $user->getUserInfo();
		}
		




	} else {

		throw new \Exception('Invalid request');
	}

	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response, JSON_PRETTY_PRINT);




// } catch(UWAPObjectNotFoundException $e) {

// 	header("HTTP/1.0 404 Not Found");
// 	header('Content-Type: text/plain; charset: utf-8');
// 	echo "Error stack trace: \n";
// 	print_r($e);

} catch(NotFoundException $e) {

	header("HTTP/1.0 404 Not Found");
	header('Content-Type: text/html; charset: utf-8');

	$data = array();
	$data['code'] = '404';
	$data['head'] = 'Not Found';
	$data['message'] = $e->getMessage();

	$templateDir = Config::dir('templates');
	$mustache = new \Mustache_Engine(array(
		// 'cache' => '/tmp/uwap-mustache',
		'loader' => new \Mustache_Loader_FilesystemLoader($templateDir),
		// 'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
	));
	$tpl = $mustache->loadTemplate('exception');

	echo $tpl->render($data);


} catch(APIAuthorizationException $e) {


	Logger::error('Error processing request: ' . $e->getMessage(), array(
		// 'message' => $e->getMessage(),
		'stacktrace' => $e->getTrace(),
		'errordetails' => $data,
	));
	$e->showJSON();





} catch(Exception $e) {



	$data = $e->prepareErrorMessage();

	Logger::error('Error processing request: ' . $e->getMessage(), array(
		// 'message' => $e->getMessage(),
		'stacktrace' => $e->getTrace(),
		'errordetails' => $data,
	));


	$templateDir = Config::dir('templates');
	$mustache = new \Mustache_Engine(array(
		// 'cache' => '/tmp/uwap-mustache',
		'loader' => new \Mustache_Loader_FilesystemLoader($templateDir),
		// 'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
	));
	$tpl = $mustache->loadTemplate('exception');

	echo $tpl->render($data);




} catch(\Exception $e) {

	header("HTTP/1.0 500 Internal Error");
	header('Content-Type: text/html; charset: utf-8');



	Logger::error('Error processing request: ' . $e->getMessage(), array(
		// 'message' => $e->getMessage(),
		'stacktrace' => $e->getTrace(),
		'e' => var_export($e, true)
	));


	$data = array();
	$data['code'] = '500';
	$data['head'] = 'Internal Error';
	$data['message'] = $e->getMessage();

	$templateDir = Config::dir('templates');
	$mustache = new \Mustache_Engine(array(
		// 'cache' => '/tmp/uwap-mustache',
		'loader' => new \Mustache_Loader_FilesystemLoader($templateDir),
		// 'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
	));
	$tpl = $mustache->loadTemplate('exception');

	echo $tpl->render($data);
}
profiler_status();





