<?php 

namespace FeideConnect;


class ArrIndexNotFoundException extends \Exception {

}


/**
 * Config
 */

class Config {

	protected $properties;

	protected static $instance = null;

	public function __construct(array $properties) {
		$this->properties = $properties;
	}

	// ------ ------ ------ ------ Object methods


	protected static function arrayPick($arr, $pick) {

		// echo "about to pick "; print_r($arr); print_r($pick);
		$ref =& $arr;
		for($i = 0; $i < count($pick); $i++) {
			if (array_key_exists($pick[$i], $ref)) {
				// echo "picking " . $pick[$i] . "\n"; print_r($ref); print_r($ref[$pick[$i]]);
				$ref =& $ref[$pick[$i]];
			} else {
				throw new ArrIndexNotFoundException();
			}
		}
		return $ref;
	}

	public function get($key, $default = null, $required = false) {

		try {

			return self::arrayPick($this->properties, explode('.',$key));

		} catch (ArrIndexNotFoundException $e) {

			if ($required === true) throw new \Exception('Missing required global configuration property [' . $key . ']');
			return $default;

		}


	}

	public function getConfig() {
		return $this->properties;
	}


	// ------ ------ ------ ------ Class methods

	// public static function getBaseURL($app = 'api') {
	// 	return self::getValue('scheme', 'https') . '://' . $app . '.' . GlobalConfig::hostname() . '/';
	// }

	public static function getValue($key, $default = null, $required = false) {
		$config = self::getInstance();
		return $config->get($key, $default, $required);
	}

	/**
	 * The way to load a global config object.
	 * 
	 * @return [type] [description]
	 */
	public static function getInstance() {

		if (!is_null(self::$instance)) {
			return self::$instance;
		}


		$file = 'config.json';
		if (getenv('CI') || 
				(isset($_SERVER['user']) && $_SERVER['user'] === 'travis')
			) {
			// echo "RUNNING CI "; exit;
			$file = 'ci/config.json';
		}
		if (getenv('AEENV') === 'test') {
			$file = 'test/config.json';	
		} else if (getenv('AEENV') === 'CI') {
			$file = 'ci/config.json';	
		}


		$configFilename = self::dir('etc/', $file);
		// echo "Looking for " . $configFilename;
		if (!file_exists($configFilename)) {
			self::makeEmptyInstance();
			throw new \Exception('Could not find config file ' . $configFilename);
		}
		$configRaw = file_get_contents($configFilename);
		if (empty($configRaw)) {
			self::makeEmptyInstance();
			throw new \Exception('Config file was empty');
		}
		$config = json_decode($configRaw, true);
		if ($config === null || !is_array($config)) {
			self::makeEmptyInstance();
			throw new \Exception('Config file was not properly encoded JSON');
		}

		self::$instance = new Config($config);
		return self::$instance;
	}

	public static function makeEmptyInstance() {
		self::$instance = new Config([]);
		return self::$instance;
	}


	/**
	 * Will return the base directory for the installation, such as 
	 * in example /var/www/feideconnect
	 * @return [type] [description]
	 */
	public static function baseDir() {
		return dirname(__DIR__) . '/';
	}

	/**
	 * Returns a subfolder, relative to the base directory:
	 * In example dir('templates/') may return 
	 * /var/www/feideconnect/templates/
	 *
	 * Filename, if present, is added to the end.
	 * 
	 * @param  string $path [description]
	 * @return [type]       [description]
	 */
	public static function dir($path = '', $file = '', $component = null) {
		if ($component === null) {
			return self::baseDir() . $path . $file;	
		}

		$endpoints = self::getValue("endpoints", []);
		if (!isset($endpoints[$component])) {
			throw new \Exception('Missing endpoint definition for  ' . $component . ' in config.json');
		}

		$base = $endpoints[$component];
		return $base . '/' . $path . $file;
	}


	public static function filepath($path = '') {

		$filepath = $path;

		if (empty($path)) { return self::baseDir(); }

		if ($path[0] === '/') {
			return $filepath;
		}

		return self::baseDir() . $path;

	}


	/**
	 * A helper function to read a JSON syntax file in the etc directory.
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	public static function readJSONfile($file) {

		$configFilename = self::dir('etc/', $file);
		if (!file_exists($configFilename)) {
			throw new \Exception('Could not find JSON file ' . $configFilename);
		}
		$data = file_get_contents($configFilename);
		if ($data === false) {
			throw new \Exception('Error reading JSON file ' . $configFilename);
		}

		$dataParsed = json_decode($data, true);
		if ($dataParsed === false) {
			throw new \Exception('Error parsing JSON file ' . $configFilename);
		}

		return $dataParsed;
	}


	
}
