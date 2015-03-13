<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Utils\URL;
use FeideConnect\Config;

class OpenIDConnect {



	static function config() {

		$base = URL::getBaseURL() . 'oauth/';
		$config = [
			'issuer' => Config::getValue('connect.issuer'),
			'authorization_endpoint' => $base . 'authorization',
			'token_endpoint' => $base . 'token',
			'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
			'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
			'userinfo_endpoint' =>  $base . 'userinfo',
		];
		$response = new JSONResponse($config);
		return $response;

	}

}

