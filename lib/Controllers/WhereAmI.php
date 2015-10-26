<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Config;
use FeideConnect\GeoLocation;

class WhereAmI {

    public static function process() {
        $l = new GeoLocation();

        return new JSONResponse($l->getLocation());
    }

}
