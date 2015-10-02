<?php


namespace FeideConnect\HTTP;


use FeideConnect\HTTP\HTTPResponse;

class EmptyResponse extends HTTPResponse {


    function __construct() {
        parent::__construct();
    }

    protected function sendBody() {

        exit;

    }


}