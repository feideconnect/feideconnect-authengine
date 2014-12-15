<?php


/**
 * Feide Connect Auth Engine - main endpoint
 *
 * This file includes routing for the Feide Connect Auth Engine.
 */

namespace FeideConnect;
require_once(dirname(dirname(__FILE__)) . '/lib/_autoload.php');

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Access-Control-Allow-Origin: *"); // CORS

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: HEAD, GET, OPTIONS, POST");
header("Access-Control-Allow-Headers: Authorization, X-Requested-With, Origin, Accept, Content-Type");
header("Access-Control-Expose-Headers: Authorization, X-Requested-With, Origin, Accept, Content-Type");


use FeideConnect\Utils\Router;


try {

	// $config = \FeideConnect\Config::getInstance();
	// print_r($config->getValue('storage'));
	// exit;

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


		$oauth = new OAuth();

		if (Router::route('post','^/oauth/authorization$', $parameters)) {
			$oauth->processAuthorizationResponse();
			// $oauth->authorization();

		} else if (Router::route('get', '^/oauth/authorization$', $parameters)) {
			$oauth->authorization();

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







	} else if  (Router::route('get', '^/cassandra$', $parameters)) {

		header('Content-Type: text/plain; charset=utf-8');


		$c = new \FeideConnect\Data\Repositories\Cassandra();

		// $data = $c->getAccessToken();


		$client = $c->getClient('a5b9491e-372d-49d9-943c-63d40dcb67f4');
		if ($client !== null) {
			$client->debug();
		}





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
	} else if  (Router::route('get', '^/auth$', $parameters)) {

		$auth = new Authentication\Authenticator();
		$auth->req(false, true); // require($isPassive = false, $allowRedirect = false, $return = null

		$account = $auth->getAccount();

		// $res = $auth->storeUser();
		// 
		// $response = array('account' => $account->getAccountID());
		$response = [
			"userids" => $account->getUserIDs(),
			"sourceID" => $account->getSourceID(),
			"name" => $account->getName(),
			"mail" => $account->getMail()
		];

		$c = new Data\Repositories\Cassandra();
		$usermapper = new Authentication\UserMapper($c);

		$user = $usermapper->getUser($account, false, true, false);

		$response['user'] = $user;




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


} catch(\Exception $e) {

	// TODO: Catch OAuth token expiration etc.! return correct error code.


	header("HTTP/1.0 500 Internal Server Error");
	header('Content-Type: text/html; charset: utf-8');


	$data = array();
	$data['message'] = $e->getMessage();
	// if ($globalconfig->getValue('debug', false)) {
	// 	$data['error'] = array(
	// 		'trace' => $e->getTraceAsString(),
	// 		'line' => $e->getLine(),
	// 		'file' => $e->getFile()
	// 	);
	// }

	
	Logger::error($e->getMessage(), array(
			'trace' => $e->getTraceAsString(),
			'line' => $e->getLine(),
			'file' => $e->getFile()
	));



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


