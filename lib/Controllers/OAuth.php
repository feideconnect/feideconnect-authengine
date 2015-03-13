<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Utils\URL;
use FeideConnect\OAuth\Server;

class OAuth {

	static function providerconfig() {

		$base = URL::getBaseURL() . 'oauth/';
		$providerconfig = [
			'authorization' => $base . 'authorization',
			'token' => $base . 'token'
		];
		$response = new JSONResponse($providerconfig);
		return $response;

	}

	static function authorization() {

		$oauth = new Server();
		return $oauth->authorizationEndpoint();

	}

	static function token() {

		$oauth = new Server();
		return $oauth->token();

	}

}