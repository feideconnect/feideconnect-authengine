<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;

class AttributeMapper {

    function __construct() {

        // $this->accountmap = Config::getValue('account.useridmap', [
        //     "feide" => "eduPersonPrincipalName",
        //     "mail" => "mail",
        //     "nin" => "norEduPersonNIN"
        // ]);

        // $this->realm = Config::getValue('account.realm', "eduPersonPrincipalName");
        // $this->name = Config::getValue('account.name', ["displayName", "cn"]);
        // $this->mail = Config::getValue('account.mail', "mail");

        // if (isset($this->attributes['jpegPhoto']) && is_array($this->attributes['jpegPhoto'])) {
        //     $this->photo = new AccountPhoto($this->attributes['jpegPhoto'][0]);
        // }



    }


    private static function ruleMatch($authSource, $idp = null, $am) {

        if (isset($am["authSource"])) {
            if ($am["authSource"] !== $authSource) {
                return false;
            }
        }
        if (isset($am["idp"]) && is_array($am["idp"])) {
            if (!in_array($idp, $am["idp"])) {
                return false;
            }
        }
        return true;
    }


    protected static function getAccountMapRules($authSource, $idp = null) {


        $accountMaps = Config::getValue("accountMaps");

        if (empty($accountMaps)) {
            throw new Exception("Configuration [accountMaps] was not set");
        }
        if (!is_array($accountMaps)) {
            throw new Exception("Configuration [accountMaps] was not set to be an array as expected");
        }

        foreach($accountMaps AS $am) {

            if (self::ruleMatch($authSource, $idp, $am)) {
                return $am;
            }
        }
        throw new Exception("Unable to find a matching account map for this authSource [" .
            $authSource . "] and idp [" . ($idp !==  null ? $idp : '...') . "]");




    }


    function getAccount($attributes) {

        // echo '<pre>'; print_r($attributes); exit;

        if (!isset($attributes['authSource'])) {
            throw new Exception("AuthSource was not set on authenticated user.");
        }
        $authSource = $attributes['authSource'];
        $idp = null;
        if (isset($attributes["idp"])) {
            $idp = $attributes["idp"];
        }


        $accountMapRules = self::getAccountMapRules($authSource, $idp);

        $account = new Account($attributes, $accountMapRules);
        return $account;



    }




}
