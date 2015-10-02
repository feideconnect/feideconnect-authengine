<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Config;
use FeideConnect\GeoLocation;

class WhereAmI {


    static function process() {

        $data = array();
        // $data["disco"] = Config::readJSONfile("disco.json");
        // $data["return"] = $_REQUEST["return"];
        // $data["returnIDParam"] = $_REQUEST["returnIDParam"];

        $l = new GeoLocation();

        return new JSONResponse($l->getLocation());




        // if (isset($_REQUEST["isPassive"]) && $_REQUEST["isPassive"] === "true") {
        //     // The correct behaviour of the IdP Discovery Protocol will be to return 
        //     // without the "returnIDParam" parameter set to anything.
        //     // If the disco stores preferences, we might return that instead.
        //     return new Redirect($data["return"]);
        // }
        
        // return (new TemplatedHTMLResponse('disco'))->setData($data);

    }

}