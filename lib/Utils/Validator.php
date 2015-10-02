<?php


namespace FeideConnect\Utils;

class Validator {

    public static function validateID($id) {
        if (preg_match('/^([a-zA-Z0-9\-]+)$/', $id, $matches)) {
            return true;
        }
        return false;
    }

    public static function validateUUID($uuid) {
        if (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid, $matches)) {
            return true;
        }
        return false;
    }

}
