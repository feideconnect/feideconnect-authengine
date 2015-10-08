<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;

class TestController {

    public static function test() {

        return new HTTPResponse();

    }

    public static function reject() {

        return new TemplatedHTMLResponse('reject');

    }



}
