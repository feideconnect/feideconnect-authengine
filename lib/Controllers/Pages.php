<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
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



}