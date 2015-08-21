<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\Redirect;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\HTTP\LocalizedTemplatedHTMLResponse;
use FeideConnect\Config;
use FeideConnect\GeoLocation;
use FeideConnect\Utils\URL;
use FeideConnect\Exceptions\Exception;

class AccountChooser {


	static function process() {

		$data = array();
		$data["disco"] = Config::readJSONfile("disco.json");
		
		// $data["return"] = $_REQUEST["return"];
		// $data["returnIDParam"] = $_REQUEST["returnIDParam"];

		$request = [];
		if (isset($_REQUEST['request'])) {

			$r = json_decode($_REQUEST['request'], true);
			
 			if (is_array($r) && isset($r["return"])) {

 				if (!URL::compareHost($r['return'])) {
 					throw new Exception("Invalid return address.");
 				}

				$request = $r;
			}
		}

		$countries = Config::readJSONfile("countries.json");

		$l = new GeoLocation();
		$data["location"] = $l->getLocation();
		$data["extra"] = Config::readJSONfile("disco2.json");
		$data["countries"] = $countries;
		$data["countriesJSON"] = json_encode($countries);
		$data["request"] = $request;
		$data["requestJSON"] = json_encode($request);
		
		
		if (isset($_REQUEST["isPassive"]) && $_REQUEST["isPassive"] === "true") {
			// The correct behaviour of the IdP Discovery Protocol will be to return 
			// without the "returnIDParam" parameter set to anything.
			// If the disco stores preferences, we might return that instead.
			return new Redirect($data["return"]);
		}
		
		return (new LocalizedTemplatedHTMLResponse('accountchooser'))->setData($data);

	}

}