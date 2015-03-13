<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;

class TestController {

	static function test() {

		return new HTTPResponse();

	}

	static function reject() {

		return new TemplatedHTMLResponse('reject');

	}



}