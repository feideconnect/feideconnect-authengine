<?php 


namespace FeideConnect;

// use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\ErrorLogHandler;

/**
* Logger
*/
class Logger {

	protected $log;
	protected static $instance = null;


	function __construct() {
		$this->log = new \Monolog\Logger('feideconnect');
<<<<<<< HEAD
		$this->log->pushHandler(new StreamHandler('/var/log/feideconnect-authengine.log', \Monolog\Logger::DEBUG));
=======

		$filename = Config::getValue('logging.filename', '/var/log/feideconnect-authengine.log');
		if (Config::getValue('logging.file', false) && file_exists($filename) && is_writable($filename)) {
			$this->log->pushHandler(new StreamHandler($filename, \Monolog\Logger::DEBUG));
		}
>>>>>>> 4cee3c95d8f8b08fc7ba57680ef2c2290d7c5937
		$this->log->pushHandler(new ErrorLogHandler());
		
	}



	public function log($level, $str, $data = array()) {

		// Fix easier parsing by the monolog laas parser.
		$str = str_replace(['{', '}'], ['[', ']'], $str);

		if (isset($_SERVER['REQUEST_URI'])) {
			$data['path'] = $_SERVER['REQUEST_URI'];	
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$data['src_ip'] = $_SERVER['REMOTE_ADDR'];	
		}

		switch ($level) {

			case 'alert':
				$this->log->addAlert($str, $data);
				break;

			case 'error':
				$this->log->addError($str, $data);
				break;

			case 'warning':
				$this->log->addWarning($str, $data);
				break;

			case 'info':
				$this->log->addInfo($str, $data);
				break;

			case 'debug':
			default:
				$this->log->addDebug($str, $data);
				break;
		}


	}



	/* ----- Class methods ----- */

	/**
	 * The way to load a global config object.
	 * 
	 * @return [type] [description]
	 */
	public static function getInstance() {

		if (!is_null(self::$instance)) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;

	}

	public static function alert($str, $data = array()) {
		$l = self::getInstance();
		$l->log('alert', $str, $data);
	}

	public static function error($str, $data = array()) {
		$l = self::getInstance();
		$l->log('error', $str, $data);
	}

	public static function warning($str, $data = array()) {
		$l = self::getInstance();
		$l->log('warning', $str, $data);
	}

	public static function info($str, $data = array()) {
		$l = self::getInstance();
		$l->log('info', $str, $data);
	}

	public static function debug($str, $data = array()) {
		$l = self::getInstance();
		$l->log('debug', $str, $data);
	}


}


