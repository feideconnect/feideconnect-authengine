<?php

namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\Redirect;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\HTTP\LocalizedTemplatedHTMLResponse;
use FeideConnect\Authentication\Authenticator;
use FeideConnect\Config;
use FeideConnect\GeoLocation;
use FeideConnect\Localization;
use FeideConnect\Utils\URL;
use FeideConnect\Exceptions\Exception;

class AccountChooser {


    public static function process() {

        $auth = new Authenticator();
        $accounts = $auth->getAllAccountsVisualTags();

        $config = Config::getInstance();
        $discoveryConfig = $config->get('disco');

        $data = array();

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
        foreach ($discoveryConfig as $dentry) {
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
        $loc = $l->getLocation();

        $data["location"] = $loc;
        $data["locationJSON"] = json_encode($loc);
        $data["request"] = $request;
        $data["requestJSON"] = json_encode($request);
        $data["noscriptentries"] = $noscriptdata;
        $data["activeAccounts"] = $accounts;
        $data["activeAccountsJSON"] = json_encode($accounts);

        // echo '<pre>'; print_r($data); exit;

        return (new LocalizedTemplatedHTMLResponse('accountchooser'))->setData($data);

    }

}
