<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;

class AttributeMapper {

    public function __construct() {

    }


    private static function ruleMatch($authSource, $idp, $am) {

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

        foreach ($accountMaps as $am) {
            if (self::ruleMatch($authSource, $idp, $am)) {
                return $am;
            }
        }

        throw new Exception("Unable to find a matching account map for this authSource [" .
            $authSource . "] and idp [" . ($idp !==  null ? $idp : '...') . "]");

    }


    public function getAccount($attributes) {

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
