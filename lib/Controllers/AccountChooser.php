<?php

namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\Redirect;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\HTTP\LocalizedTemplatedHTMLResponse;
use FeideConnect\Config;
use FeideConnect\GeoLocation;
use FeideConnect\Localization;
use FeideConnect\Utils\URL;
use FeideConnect\Exceptions\Exception;

class AccountChooser {


    static function process() {



        $discoveryConfig = Config::readJSONfile("disco2.json");
        $config = Config::getInstance();

        $data = array();

        // $data["disco"] = Config::readJSONfile("disco.json");
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

        // $countries = Config::readJSONfile("countries.json");


        $baseURL = $request["return"];




        $noscriptdata = [];

        $feideentry = [
            "title" => "Feide"
        ];
        if (isset($baseURL)) {
            $responseURL = \Purl\Url::parse($baseURL);
            $responseURL->query->set("acresponse", json_encode(["type" => "saml", "id" => $config->get("feideIdP")]));
            $feideentry["url"] = $responseURL->getURL();
        }

        $noscriptdata[] = $feideentry;
        foreach($discoveryConfig as $dentry) {

            $noscriptentry["title"] = Localization::localizeEntry($dentry["title"]);

            if (isset($baseURL)) {
                $responseURL = \Purl\Url::parse($baseURL);
                $response = [];
                if (isset($dentry["type"])) {
                    $response["type"] = $dentry["type"];
                }
                if (isset($dentry["id"])) {
                    $response["id"] = $dentry["id"];
                }
                $responseURL->query->set("acresponse", json_encode($response));
                $noscriptentry["url"] = $responseURL->getURL();
            }

            $noscriptdata[] = $noscriptentry;
        }





        $l = new GeoLocation();
        $data["location"] = $l->getLocation();
        $data["extra"] = $discoveryConfig;
        // $data["countries"] = $countries;
        // $data["countriesJSON"] = json_encode($countries);
        $data["request"] = $request;
        $data["requestJSON"] = json_encode($request);

        $data["noscriptentries"] = $noscriptdata;

        // $data["noscriptjson"] = json_encode(Config::getInstance()->get(""), JSON_PRETTY_PRINT);

        if (isset($_REQUEST["isPassive"]) && $_REQUEST["isPassive"] === "true") {
            // The correct behaviour of the IdP Discovery Protocol will be to return
            // without the "returnIDParam" parameter set to anything.
            // If the disco stores preferences, we might return that instead.
            return new Redirect($data["return"]);
        }

        return (new LocalizedTemplatedHTMLResponse('accountchooser'))->setData($data);

    }

}
