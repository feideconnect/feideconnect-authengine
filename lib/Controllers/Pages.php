<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TextResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;

use FeideConnect\Utils\URL;

class Pages {

	static function reject() {

		return (new TemplatedHTMLResponse('reject'))->setData([
			"head" => "You rejected the authorization request for an application"
		]);
	}

	static function loggedout() {

		return (new TemplatedHTMLResponse('loggedout'))->setData([
			"head" => "You are now logged out"
		]);
	}


	static function robot() {
		$txt = "User-agent: *\nDisallow: /\n";
		return new TextResponse($txt);
	}


	static function debug() {



		$data = [];
		$data['endpoints'] = [
			"oauth.base" => URL::getBaseURL() . 'oauth/',
			"this" => URL::selfURL(),
			"this.noquery" => URL::selfURLNoQuery(),
			"this.host" =>  URL::selfURLhost(),
		];
		$data['client'] = $_SERVER['REMOTE_ADDR'];

		$res = new JSONResponse($data);
		$res->setCORS(false);
		return $res;

	}


}