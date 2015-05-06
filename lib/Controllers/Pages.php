<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TextResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;

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


}