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
     * Will return sp.example.org
     */
    public static function getSelfHost() {
        $url = self::getBaseURL();
        $start = strpos($url, '://') + 3;
        $length = strcspn($url, '/:', $start);
        return substr($url, $start, $length);
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
        $url = self::getBaseURL();
        $start = strpos($url, '://') + 3;
        $length = strcspn($url, '/', $start) + $start;
        return substr($url, 0, $length);
    }

    /**
     * This function checks if we should set a secure cookie.
     *
     * @return TRUE if the cookie should be secure, FALSE otherwise.
     */
    public static function isHTTPS() {
        $url = self::getBaseURL();
        $end = strpos($url, '://');
        $protocol = substr($url, 0, $end);
        if ($protocol === 'https') {
            return true;
        } else {
            return false;
        }
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

    /**
     * Will return https://sp.example.org/universities/ruc/baz/simplesaml/saml2/SSOService.php
     */
    public static function selfURLNoQuery() {

        $selfURLhost = self::selfURLhost();
        $selfURLhost .= self::selfPathNoQuery();
        return $selfURLhost;

    }
    /**
     * Will return sp.example.org/ssp/sp1
     *
     * Please note this function will return the base URL for the current
     * SP, as defined in the global configuration.
     */
    public static function getSelfHostWithPath() {

        $baseurl = explode("/", self::getBaseURL());
        $elements = array_slice($baseurl, 3 - count($baseurl), count($baseurl) - 4);
        $path = implode("/", $elements);
        $selfhostwithpath = self::getSelfHost();
        return $selfhostwithpath . "/" . $path;
    }

    /**
     * Will return foo
     */
    public static function getFirstPathElement($trailingslash = true) {

        if (preg_match('|^/(.*?)/|', $_SERVER['SCRIPT_NAME'], $matches)) {
            return ($trailingslash ? '/' : '') . $matches[1];
        }
        return '';
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

    /*
     * This function returns the selfURL() but filters out an array of query string keys.
     * In example:   selfURLfilterQuery(['strict', 'debug'])
     * will turn   https://acme.org/blah?debug=1&strict=true&error=23 into
     *             https://acme.org/blah?error=23
     * and will turn  https://acme.org/blah?debug=1&strict=true into
     *                https://acme.org/blah
     */
    public static function selfURLfilterQuery(array $filter = []) {
        echo '<pre>';
        $url = self::selfURLNoQuery();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $params = $_GET;
            foreach($params AS $key => $value) {
                if (in_array($key, $filter)) {
                    unset($params[$key]);
                }
            }
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        }
        return $url;
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

    /**
     * Retrieve and return the absolute base URL for the simpleSAMLphp installation.
     *
     * For example: https://idp.example.org/simplesaml/
     *
     * The URL will always end with a '/'.
     *
     * @return string  The absolute base URL for the simpleSAMLphp installation.
     */
    public static function getBaseURL() {
        // $globalConfig = SimpleSAML_Configuration::getInstance();
        $baseURL = '/';

        if (preg_match('#^https?://.*/$#D', $baseURL, $matches)) {
            /* full URL in baseurlpath, override local server values */
            return $baseURL;
        } elseif (
            (preg_match('#^/?([^/]?.*/)$#D', $baseURL, $matches)) ||
            (preg_match('#^\*(.*)/$#D', $baseURL, $matches)) ||
            ($baseURL === '')) {
            /* get server values */
            if (self::getServerHTTPS()) {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }
            $hostname = self::getServerHost();
            $port = self::getServerPort();
            $path = '/';
            return $protocol.$hostname.$port.$path;
        } else {
            throw new Exception('Invalid value of \'baseurl\' in '.
                'config.php. Valid format is in the form: '.
                '[(http|https)://(hostname|fqdn)[:port]]/[path/to/simplesaml/]. '.
                'It must end with a \'/\'.');
        }
    }


}
