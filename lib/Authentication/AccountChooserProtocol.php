<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;
use FeideConnect\Utils\URL;
use FeideConnect\Exceptions\Exception;

class AccountChooserProtocol {

    protected $selfURL;
    protected $baseURL;
    protected $response = null;
    protected $login_hint = null;

    public function __construct() {

        $this->selfURL = URL::selfURL();
        $this->baseURL = URL::getBaseURL() . 'accountchooser';

        $this->clientid = null;

        if (isset($_REQUEST['acresponse'])) {
            $r = json_decode($_REQUEST['acresponse'], true);
            if (is_array($r)) {
                $this->response = $r;
            }
        }

    }

    public static function getResponseFromHint($login_hint) {
        if (preg_match('/^feide\|all$/', $login_hint, $matches)) {
            return [
                "type" => "saml",
                "id"  => Config::getValue('feideIdP'),
            ];
        } else if (preg_match('/^feide\|realm\|([^|]+)$/', $login_hint, $matches)) {
            return [
                "type" => "saml",
                "id"  => Config::getValue('feideIdP'),
                "subid"   => $matches[1],
            ];
        } else if (preg_match('/^feide\|realm\|([^|]+)\|([^|]+)$/', $login_hint, $matches)) {
            return [
                "type" => "saml",
                "id"  => Config::getValue('feideIdP'),
                "subid"   => $matches[1],
                "userids" => ['feide:' . $matches[2]],
            ];
        } else if (preg_match('/^idporten$/', $login_hint, $matches)) {
            return [
                "type" => "saml",
                "id" => "idporten.difi.no-v3"
            ];
        }

        return null;

    }

    public function getResponse() {
        return $this->response;
    }

    public function setClientID($clientid) {
        $this->clientid = $clientid;
    }

    public function setLoginHint($login_hint) {
        $this->login_hint = $login_hint;

        $resp = self::getResponseFromHint($login_hint);

        // echo "Set loginhint <pre>"; echo $login_hint . " "; echo(var_export($resp)); exit;
        if ($resp !== null) {
            $this->response = $resp;
        }
    }

    public function getAuthConfig() {
        $ac = [
            "type" => "saml"
        ];
        if (isset($this->response["type"])) {
            $ac["type"] = $this->response["type"];
        }
        if (isset($this->response["id"])) {
            $ac["idp"] = $this->response["id"];
        }
        if (isset($this->response["subid"])) {
            $ac["subid"] = $this->response["subid"];
        }
        return $ac;
    }

    public function hasResponse() {
        return ($this->response !== null);
    }

    public function getRequest() {
        $ro = [
            "return" => $this->selfURL,
        ];
        if ($this->clientid) {
            $ro['clientid'] = $this->clientid;
        }
        return $this->baseURL . '?request=' . rawurlencode(json_encode($ro));
    }

    public function debug() {

        echo '<pre>' . "accountchooser: \n";
        echo "Base URL " . $this->baseURL . "\n";
        echo "Self URL " . $this->selfURL . "\n";
        print_r($this->response);

        echo "\n-----\n Request url \n   " . $this->getRequest();
        exit;

    }


}
