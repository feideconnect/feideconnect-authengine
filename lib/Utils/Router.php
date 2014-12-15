<?php

namespace FeideConnect\Utils;

/**
* 
*/
class Router {
	
	public static function route($method = false, $match, &$parameters, &$object = null) {
		if (empty($_SERVER['PATH_INFO'])) return false;

		$inputraw = file_get_contents("php://input");
		if ($inputraw) {
			$object = json_decode($inputraw, true);
		}
		

		$path = $_SERVER['PATH_INFO'];
		if (empty($_SERVER['PATH_INFO'])) $path = '/';
		
		$realmethod = strtolower($_SERVER['REQUEST_METHOD']);

		if ($method !== false) {
			if (strtolower($method) !== $realmethod) return false;
		}
		if (!preg_match('|^' . $match . '|', $path, $parameters)) return false;
		return true;
	}

	
	
}