<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;


class OpenIDConnect {



	static function config() {

		$openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
		$config = $openid->getProviderConfiguration();
		$response = new JSONResponse($config);
		return $response;

	}

	static function getJWKs() {

		$openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
		$jwks = $openid->getJWKs();

		$data = [
			"jwk" => $jwks
		];


		$response = new JSONResponse($data);
		return $response;
	}


}

