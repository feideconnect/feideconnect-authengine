<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\Redirect;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\Config;

class Disco {


    public static function process() {

        $data = array();
        $data["disco"] = Config::readJSONfile("disco.json");
        $data["return"] = $_REQUEST["return"];
        $data["returnIDParam"] = $_REQUEST["returnIDParam"];

        return (new TemplatedHTMLResponse('disco'))->setData($data);

    }

}
