<?php

namespace FeideConnect\Utils;

class URL {


	public static function getURLhostPart($url) {
		$host = parse_url($url, PHP_URL_HOST);
		return $host;
	}

	public static function isSecure($url) {
		$prot = parse_url($url, PHP_URL_SCHEME);
		return ($prot === 'https');
	}

	/**
	 * Will return sp.example.org
	 */
	public static function getSelfHost() {
		$url = self::getBaseURL();
		$start = strpos($url,'://') + 3;
		$length = strcspn($url,'/:',$start);
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
		if(strstr($currenthost, ":")) {
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
		$start = strpos($url,'://') + 3;
		$length = strcspn($url,'/',$start) + $start;
		return substr($url, 0, $length);
	}
	
	/**
	 * This function checks if we should set a secure cookie.
	 *
	 * @return TRUE if the cookie should be secure, FALSE otherwise.
	 */
	public static function isHTTPS() {
		$url = self::getBaseURL();
		$end = strpos($url,'://');
		$protocol = substr($url, 0, $end);
		if ($protocol === 'https') {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	/**
	 * retrieve HTTPS status from $_SERVER environment variables
	 */
	private static function getServerHTTPS() {
		if(!array_key_exists('HTTPS', $_SERVER)) {
			/* Not an https-request. */
			return FALSE;
		}
		if($_SERVER['HTTPS'] === 'off') {
			/* IIS with HTTPS off. */
			return FALSE;
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
			if ($portnumber == '443') $port = '';
		} else {
			if ($portnumber == '80') $port = '';
		}
		return $port;
	}
	/**
	 * Will return https://sp.example.org/universities/ruc/baz/simplesaml/saml2/SSOService.php
	 */
	public static function selfURLNoQuery() {
	
		$selfURLhost = self::selfURLhost();
		$selfURLhost .= $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['PATH_INFO'])) {
			$selfURLhost .= $_SERVER['PATH_INFO'];
		}
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
		
		if ($info1["scheme"] !== $info2["scheme"]) {return false;}
		if ($info1["host"] !== $info2["host"]) {return false;}

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