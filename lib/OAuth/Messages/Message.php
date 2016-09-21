<?php

namespace FeideConnect\OAuth\Messages;

use FeideConnect\OAuth\Exceptions;
use FeideConnect\HTTP\Redirect;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Utils;

/**
*
*/

class Message implements Utils\Loggable {


    protected function __construct() {

    }

    public function getAsArray() {
        $arr = array();
        foreach ($this as $k => $v) {
            if ($v !== NULL) {
                $arr[$k] = $v;
            }
        }
        return $arr;
    }

    public function debug() {

        echo "Debug object " . get_class($this) . "\n";
        // print_r($this->getAsArray());
        echo json_encode($this->getAsArray(), JSON_PRETTY_PRINT) . "\n";

    }



    public function getRedirectURL($endpoint, $hash = false) {
        $qs = http_build_query($this->getAsArray());
        if ($hash) {
            $redirurl = $endpoint . '#' . $qs;
        } else {
            if (strstr($endpoint, "?")) {
                $redirurl = $endpoint . '&' . $qs;
            } else {
                $redirurl = $endpoint . '?' . $qs;
            }

        }
        return $redirurl;
    }

    public function getRedirectResponse($endpoint, $hash = false) {
        $url = $this->getRedirectURL($endpoint, $hash);
        return new Redirect($url);
    }

    public function getJSONResponse($httpcode = 200) {

        $body = $this->getAsArray();
        $response = new JSONResponse($body);
        $response->setStatus($httpcode);
        return $response;
    }

    // public function sendBodyForm() {
    //     // header('Content-Type: application/json; charset=utf-8');
    //     header('Content-Type: application/x-www-form-urlencoded');

    //     $body = array();
    //     foreach($this as $key => $value) {
    //         if (empty($value)) continue;
    //         $body[$key] = $value;
    //     }

    //     // echo json_encode($body);
    //     echo http_build_query($body);
    //     exit;
    // }

    public static function spacelist($arg) {
        if ($arg === null) {
            return null;
        }
        return explode(' ', $arg);
    }

    public static function optional($message, $key) {
        if (empty($message[$key])) {
            return null;
        }
        return $message[$key];
    }
    public static function prequire($message, $key, $values = null, $multivalued = false) {
        if (empty($message[$key])) {
            throw new Exceptions\OAuthException('invalid_request', 'Message does not include required parameter [' . $key . ']');
        }
        if (!empty($values)) {
            if ($multivalued) {
                $rvs = explode(' ', $message[$key]);
                foreach ($rvs as $v) {
                    if (!in_array($v, $values)) {
                        throw new Exceptions\OAuthException('invalid_request', 'Message parameter [' . $key . '] does include an illegal / unknown value.');
                    }
                }
            }
            if (!in_array($message[$key], $values)) {
                throw new Exceptions\OAuthException('invalid_request', 'Message parameter [' . $key . '] does include an illegal / unknown value.');
            }
        }
        return $message[$key];
    }


    public function toLog() {
        return $this->getAsArray();
    }

}
