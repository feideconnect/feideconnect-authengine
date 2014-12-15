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
		$this->log->pushHandler(new StreamHandler('/var/log/feideconnect.log', \Monolog\Logger::DEBUG));
		$this->log->pushHandler(new ErrorLogHandler());
		
	}





	public function log($level, $str, $data = array()) {

		$data['path'] = $_SERVER['REQUEST_URI'];
		$data['client'] = $_SERVER['REMOTE_ADDR'];

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

	public static function alert($str, $data) {
		$l = self::getInstance();
		$l->log('alert', $str, $data);
	}

	public static function error($str, $data) {
		$l = self::getInstance();
		$l->log('error', $str, $data);
	}

	public static function warning($str, $data) {
		$l = self::getInstance();
		$l->log('warning', $str, $data);
	}

	public static function info($str, $data) {
		$l = self::getInstance();
		$l->log('info', $str, $data);
	}

	public static function debug($str, $data) {
		$l = self::getInstance();
		$l->log('debug', $str, $data);
	}


}


