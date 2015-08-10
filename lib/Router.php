<?php

namespace FeideConnect;

use Phroute\Phroute;

use FeideConnect\Controllers;

class Router {

	protected $router, $dispatcher;

	function __construct() {


		$this->router = new Phroute\RouteCollector();



		// Pages
		$this->router->get('/reject', ['FeideConnect\Controllers\Pages', 'reject']);
		$this->router->get('/loggedout', ['FeideConnect\Controllers\Pages', 'loggedout']);


		// POC
		$this->router->get('/poc/{user}/{client}', ['FeideConnect\Controllers\POC', 'process']);
		$this->router->get('/test', ['FeideConnect\Controllers\TestController', 'test']);
		$this->router->get('/poc-grant/{clientid:[a-fA-F0-9\-]+}/{userid:[a-zA-Z0-9_\-\.:]+ }', ['FeideConnect\Controllers\POCgrant', 'run']);


		// Data APIs
		$this->router->get('/user/media/{userid:[a-zA-Z0-9_\-\.:]+ }', ['FeideConnect\Controllers\Data', 'getUserProfilephoto']);
		$this->router->get('/client/media/{clientid:[a-fA-F0-9\-]+ }', ['FeideConnect\Controllers\Data', 'getClientLogo']);
		$this->router->get('/orgs', ['FeideConnect\Controllers\Data', 'getOrgs']);


		// OAuth
		$this->router->get('/oauth/config', ['FeideConnect\Controllers\OAuth', 'providerconfig']);
		$this->router->get('/oauth/authorization', ['FeideConnect\Controllers\OAuth', 'authorization']);
		$this->router->post('/oauth/authorization', ['FeideConnect\Controllers\OAuth', 'authorization']);
		$this->router->any('/oauth/token', ['FeideConnect\Controllers\OAuth', 'token']);


		// Informatio about authentication.
		$this->router->get('/auth', ['FeideConnect\Controllers\Auth', 'userdebug']);
		$this->router->get('/userinfo', ['FeideConnect\Controllers\Auth', 'userinfo']);
		$this->router->get('/userinfo/authinfo', ['FeideConnect\Controllers\Auth', 'authinfo']);
		$this->router->get('/logout', ['FeideConnect\Controllers\Auth', 'logout']);



		// IdP Discovery page
		$this->router->get('/disco', ['FeideConnect\Controllers\Disco', 'process']);
		$this->router->get('/accountchooser', ['FeideConnect\Controllers\AccountChooser', 'process']);
		$this->router->get('/accountchooser/extra', ['FeideConnect\Controllers\Data', 'accountchooserExtra']);
		$this->router->get('/accountchooser/response', ['FeideConnect\Controllers\Pages', 'emptyResponse']);

		$this->router->get('/whereami', ['FeideConnect\Controllers\WhereAmI', 'process']);

		$this->router->get('/debug', ['FeideConnect\Controllers\Pages', 'debug']);


		// OpenID Connect
		$this->router->get('/.well-known/openid-configuration', ['FeideConnect\Controllers\OpenIDConnect', 'config']);
		$this->router->get('/openid/jwks', ['FeideConnect\Controllers\OpenIDConnect', 'getJWKs']);
		$this->router->any('/openid/userinfo', ['FeideConnect\Controllers\OpenIDConnect', 'userinfo']);

		// Robots
		$this->router->get('/robots.txt', ['FeideConnect\Controllers\Pages', 'robot']);


		// NB. You can cache the return value from $router->getData() 
		//  so you don't have to create the routes each request - massive speed gains
		$this->dispatcher = new Phroute\Dispatcher($this->router->getData());



	}


	function dispatch() {
		return $this->dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
	}

	function dispatchCustom($method, $path) {
		return $this->dispatcher->dispatch($method, $path);
	}




}
