<?php


namespace FeideConnect;

// use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\ErrorLogHandler;
use \Monolog\Handler\SyslogHandler;
use \Monolog\Formatter\LineFormatter;

use FeideConnect\Utils;

/**
* Logger
*/
class Logger {

    protected $log;
    protected static $instance = null;
    protected static $requestId;


    private function __construct() {
        $this->log = new \Monolog\Logger('feideconnect');
        $filename = Config::getValue('logging.filename', '/var/log/feideconnect-authengine.log');
        if (Config::getValue('logging.file', false) && file_exists($filename) && is_writable($filename)) {
            $this->log->pushHandler(new StreamHandler($filename, \Monolog\Logger::DEBUG));
        }
        $syslog_ident = Config::getValue('logging.syslog.ident', null);
        $syslog_facility = Config::getValue('logging.syslog.facility', 'local0');
        if ($syslog_ident && $syslog_facility) {
            $syslog = new SyslogHandler($syslog_ident, $syslog_facility);
            $syslog->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s.u'));
            $this->log->pushHandler($syslog);
        }
        if (Config::getValue('logging.errorlog', true)) {
            $this->log->pushHandler(new ErrorLogHandler());
        }
    }

    protected static function requestId() {
        if (self::$requestId === null) {
            self::$requestId = Utils\Misc::genUUID();
        }
        return self::$requestId;
    }

    public function log($level, $str, $data = array()) {

        // Fix easier parsing by the monolog laas parser.
        $str = str_replace(['{', '}'], ['[', ']'], $str);

        $path = Utils\URL::selfPathNoQuery();
        if (!empty($path)) {
            $data['path'] = $path;
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $data['src_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        $data['request'] = self::requestId();

        foreach ($data as $key => &$value) {
            if ($value instanceof Utils\Loggable) {
                $value = $value->toLog();
            }
        }
        unset($value);

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
