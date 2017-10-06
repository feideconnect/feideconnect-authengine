<?php

namespace FeideConnect\Utils;

class URL {


    public static function getURLhostPart($url) {
        $host = parse_url($url, PHP_URL_HOST);
        return $host;
    }

    public static function isSecure($url) {
        $prot = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if ($prot === 'http') {
            if ($host === '127.0.0.1') {
                return true;
            }
            if ($host === 'localhost') {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Retrieve Host value from $_SERVER environment variables
     */
    private static function getServerHost() {
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $currenthost = $_SERVER['HTTP_HOST'];
        } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
            $currenthost = $_SERVER['SERVER_NAME'];
        } else {
            /* Almost certainly not what you want, but ... */
            $currenthost = 'localhost';
        }
        if (strstr($currenthost, ":")) {
            $currenthostdecomposed = explode(":", $currenthost);
            $port = array_pop($currenthostdecomposed);
            if (!is_numeric($port)) {
                array_push($currenthostdecomposed, $port);
            }
            $currenthost = implode($currenthostdecomposed, ":");
        }
        return $currenthost;
    }

    /**
     * Will return https://sp.example.org[:PORT]
     */
    public static function selfURLhost() {
        if (self::getServerHTTPS()) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $hostname = self::getServerHost();
        $port = self::getServerPort();
        return $protocol.$hostname.$port;
    }

    /**
     * retrieve HTTPS status from $_SERVER environment variables
     */
    private static function getServerHTTPS() {
        if (!array_key_exists('HTTPS', $_SERVER)) {
            /* Not an https-request. */
            return false;
        }
        if ($_SERVER['HTTPS'] === 'off') {
            /* IIS with HTTPS off. */
            return false;
        }
        /* Otherwise, HTTPS will be a non-empty string. */
        return $_SERVER['HTTPS'] !== '';
    }

    /**
     * Retrieve port number from $_SERVER environment variables
     * return it as a string such as ":80" if different from
     * protocol default port, otherwise returns an empty string
     */
    private static function getServerPort() {
        if (isset($_SERVER["SERVER_PORT"])) {
            $portnumber = $_SERVER["SERVER_PORT"];
        } else {
            $portnumber = 80;
        }
        $port = ':' . $portnumber;
        if (self::getServerHTTPS()) {
            if ($portnumber == '443') {
                $port = '';
            }
        } else {
            if ($portnumber == '80') {
                $port = '';
            }
        }
        return $port;
    }

    /**
     * Will return /universities/ruc/baz/simplesaml/saml2/SSOService.php
     */
    public static function selfPathNoQuery() {
        $path = $_SERVER['SCRIPT_NAME'];
        if (isset($_SERVER['PATH_INFO'])) {
            $path .= $_SERVER['PATH_INFO'];
        }
        return $path;
    }

    public static function selfURL() {
        $selfURLhost = self::selfURLhost();
        $requestURI = $_SERVER['REQUEST_URI'];
        if ($requestURI[0] !== '/') {
            /* We probably have a URL of the form: http://server/. */
            if (preg_match('#^https?://[^/]*(/.*)#i', $requestURI, $matches)) {
                $requestURI = $matches[1];
            }
        }
        return $selfURLhost . $requestURI;
    }


    public static function compareHost($url1, $url2 = null) {

        if ( $url2 === null) {
            $url2 = self::selfURL();
        }


        // PHP prior of 5.3.3 emits a warning if the URL parsing failed.
        $info1 = @parse_url($url1);
        if (empty($info1)) {
            return false;
        }

        $info2 = @parse_url($url2);
        if (empty($info2)) {
            return false;
        }

        if ($info1["scheme"] !== $info2["scheme"]) {
            return false;
        }
        if ($info1["host"] !== $info2["host"]) {
            return false;
        }

        return true;
    }

    public static function getBaseURL() {
        $base = self::selfURLhost();
        $path = '/';
        return $base.$path;
    }


}
