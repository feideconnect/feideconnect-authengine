<?php

namespace FeideConnect;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;

class Localization {

	protected static $langCache = [];


	protected static $aliases = [
		"no" => "nb"
	];

	function __construct() {

	}


	static function debug() {


		$selected = Localization::get_browser_language(['nb', 'nn', 'en', 'de']);
		echo "SELECTED\n"; var_dump($selected); exit;

	}

	static function getDictionary() {

		if (Config::getValue('enableLocalization', false)) {
			$availableLanguages = Config::getValue('availableLanguages', ['en']);
			$lang = self::get_browser_language($availableLanguages);
			$dictionaryFile = Config::filepath('dictionaries/build/dictionary.' . $lang . '.json');
		} else {
			$dictionaryFile = Config::filepath('dictionaries/dictionary.en.json');
		}



		if (!file_exists($dictionaryFile)) {
			throw new \Exception('Cannot locate dictionary file: ' . $dictionaryFile);
		}
		$dictionaryContent = file_get_contents($dictionaryFile);
		$dict = json_decode($dictionaryContent, true);
		if (empty($dict)) {
			throw new \Exception('Dictionary file was empty or not properly formatted JSON');
		}

		return $dict;
	}

	/*
	 * Source:
	 * http://codereview.stackexchange.com/questions/9141/language-detection-php-script
	 */
	static function get_browser_language($available_languages, $http_accept_language = 'auto') {
		
		$cachestr = join('|', $available_languages);
		if (isset(self::$langCache[$cachestr])) {
			return self::$langCache[$cachestr];
		}
		// TODO : an accept header of 'no' should aid in preferring 'nb'. 
		// Need to support some kind of alias for this
		if ($http_accept_language == 'auto') {
			$http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		$pattern = '/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i';
		preg_match_all($pattern, $http_accept_language, $hits, PREG_SET_ORDER);
		$bestlang = $available_languages[0];
		$bestqval = 0;

		// var_dump($hits);

		foreach ($hits as $arr) {
			$langprefix = strtolower ($arr[1]);

			// Use the static aliases mapping to trick a preference of 'no' to be interpreted as 'nb'.
			if (isset(self::$aliases[$langprefix])) {
				$langprefix = self::$aliases[$langprefix];
			}

			if (!empty($arr[3])) {
				$langrange = strtolower ($arr[3]);
				$language = $langprefix . "-" . $langrange;
			} else {
				$language = $langprefix;
			}
			$qvalue = 1.0;
			if (!empty($arr[5])) $qvalue = floatval($arr[5]);
			// find q-maximal language
			if (in_array($language,$available_languages) && ($qvalue > $bestqval)) {
				$bestlang = $language;
				$bestqval = $qvalue;
			}
			// if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
			else if (in_array($langprefix,$available_languages) && (($qvalue*0.9) > $bestqval)) {
				$bestlang = $langprefix;
				$bestqval = $qvalue*0.9;
			}
		}
		// echo '<pre>'; print_r($hits); exit;
		self::$langCache[$cachestr] = $bestlang;
		return $bestlang;
	}



}