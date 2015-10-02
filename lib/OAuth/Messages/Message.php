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


    function __construct() {    

    }


    public function asQS() {
        $qs = array();
        foreach($this AS $key => $value) {
            if (empty($value)) continue;
            $qs[] = urlencode($key) . '=' . urlencode($value);
        }
        return join('&', $qs);
    }

    public function asArray() {
        $qs = array();
        foreach($this AS $key => $value) {
            $qs[$key] = $value;
        }
        return $qs;
    }



    public function getAsArray() {
        $arr = array();
        foreach($this AS $k => $v) {
            if (isset($this->{$k})) {
                $arr[$k] = $this->{$k};
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
        if ($hash) {
            $redirurl = $endpoint . '#' . $this->asQS();
        } else {
            if (strstr($endpoint, "?")) {
                $redirurl = $endpoint . '&' . $this->asQS();
            } else {
                $redirurl = $endpoint . '?' . $this->asQS();
            }
            
        }
        return $redirurl;
    }
    
    public function sendRedirect($endpoint, $hash = false) {
        $url = $this->getRedirectURL($endpoint, $hash);
        return new Redirect($url);
    }

    public function sendBodyJSON($httpcode = 200) {

        $body = array();
        foreach($this AS $key => $value) {
            if (empty($value)) continue;
            $body[$key] = $value;
        }
        $response = new JSONResponse($body);
        $response->setStatus($httpcode);
        return $response;
    }

    // public function sendBodyForm() {
    //     // header('Content-Type: application/json; charset=utf-8');
    //     header('Content-Type: application/x-www-form-urlencoded');

    //     $body = array();
    //     foreach($this AS $key => $value) {
    //         if (empty($value)) continue;
    //         $body[$key] = $value;
    //     }

    //     // echo json_encode($body);
    //     echo http_build_query($body);
    //     exit;
    // }
    

    
    public function post($endpoint) {
        error_log('posting to endpoint: ' . $endpoint);
        $postdata = $this->asQS();
        
        error_log('Sending body: ' . $postdata);
        
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        $result = file_get_contents($endpoint, false, $context);
        $resultobj = json_decode($result, true);
        

        return $resultobj;
    }


    public static function spacelist($arg) {
        if ($arg === null) return null;
        return explode(' ', $arg);
    }

    public static function optional($message, $key) {
        if (empty($message[$key])) return null;
        return $message[$key];
    }
    public static function prequire($message, $key, $values = null, $multivalued = false) {
        if (empty($message[$key])) {
            throw new Exceptions\OAuthException('invalid_request', 'Message does not include required parameter [' . $key . ']');
        }
        if (!empty($values)) {
            if ($multivalued) {
                $rvs = explode(' ', $message[$key]);
                foreach($rvs AS $v) {
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