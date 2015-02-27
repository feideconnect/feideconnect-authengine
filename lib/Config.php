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
		$configFilename = self::dir('etc/', $file);
		// echo "Looking for " . $configFilename;
		if (!file_exists($configFilename)) {
			throw new \Exception('Could not find config file ' . $configFilename);
		}
		$config = json_decode(file_get_contents($configFilename), true);

		self::$instance = new Config($config);
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
	public static function dir($path = '', $file = '') {
		return self::baseDir() . $path . $file;
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
