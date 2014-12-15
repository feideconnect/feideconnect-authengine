<?php 

namespace FeideConnect;

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

	public function get($key, $default = null, $required = false) {
		if (isset($this->properties[$key])) return $this->properties[$key];
		if ($required === true) throw new Exception('Missing required global configuration property [' . $key . ']');
		return $default;
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

		// $BASE = dirname(dirname(__DIR__));
		// $configFilename = $BASE . '/etc/config.json';
		$configFilename = self::dir('etc/', 'config.json');
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




	
}
