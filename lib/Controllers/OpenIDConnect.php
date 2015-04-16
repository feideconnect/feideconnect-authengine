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

}

