<?php

namespace FeideConnect\Utils;

/**
* 
*/
class Router {
	
	public static function route($method = false, $match, &$parameters) {
		if (empty($_SERVER['PATH_INFO'])) return false;		

		$path = $_SERVER['PATH_INFO'];
		if (empty($_SERVER['PATH_INFO'])) $path = '/';
		
		$realmethod = strtolower($_SERVER['REQUEST_METHOD']);

		if ($method !== false) {
			if (strtolower($method) !== $realmethod) return false;
		}
		if (!preg_match('|^' . $match . '|', $path, $parameters)) return false;
		return true;
	}


	public static function getBodyRaw() {
		return file_get_contents("php://input");
	}

	public static function getBodyJSON() {
		$inputraw = self::getBodyRaw();
		if ($inputraw) {
			$object = json_decode($inputraw, true);
			return $object;
		}
		return null;
	}

	public static function getBodyFormURL() {
		$inputraw = self::getBodyRaw();
		
	}

	public static function getBody() {



	}

	
	
}