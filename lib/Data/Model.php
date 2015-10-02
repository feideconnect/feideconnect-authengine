<?php

namespace FeideConnect\Data;

use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Types\Timestamp;
use FeideConnect\Utils;

abstract class Model implements Utils\Loggable {

    protected $_repo;
    protected static $_properties = [];
    protected static $_types = [];


    function __construct($props = array()) {
        $this->_repo = StorageProvider::getStorage();

        foreach (static::$_properties AS $k) {
            $this->{$k} = null;
        }

        foreach($props AS $k => $v) {
            if (!in_array($k, static::$_properties)) {
                error_log(get_class($this) . ": Trying to set a property [" . $k . "] that is not legal.");
                continue;
            }

            // Force specified typed attributes to be of correct type class.
            if (isset(static::$_types[$k])) {
                if (static::$_types[$k] === 'timestamp') {
                    if (!($v instanceof Timestamp)) {
                        error_log(get_class($this) . ": Trying to set property [" . $k . "] with an invalid timestamp type");
                        continue;
                    }
                }
            }

            $this->{$k} = $v;
        }

    }

    public static function fromDB($key, $value) {

        if (isset(static::$_types[$key])) {
            if (static::$_types[$key] === 'timestamp') {
                return Timestamp::fromCassandraTimestamp($value);
            } else if (static::$_types[$key] === 'blob') {
                return substr($value, 4);
            }
        }
        return $value;

    }


    public function has($attrname) {
        return (!empty($this->{$attrname}));
    }

    public function getAsArray() {

        $a = array();
        foreach(static::$_properties AS $k) {
            if (isset($this->{$k})) {

                if (isset(static::$_types[$k])) {
                    $a[$k] = $this->{$k}->format();
                    continue;
                }

                $a[$k] = $this->{$k};
            }
        }
        return $a;
    }

    public function getAsArrayLimited($includes) {
        $list = $this->getAsArray();
        $res = array_intersect_key($list, array_flip($includes));
        return $res;
    }

    public function getStorableArray() {

        $a = array();
        foreach(static::$_properties AS $k) {
            if (isset($this->{$k})) {

                if (isset(static::$_types[$k])) {
                    $a[$k] = $this->{$k}->getDBobject();
                } else {
                    $a[$k] = $this->{$k};
                }
            }
        }
        return $a;


    }



    public function debug() {

        echo "Debug object " . get_class($this) . "\n";
        // print_r($this->getAsArray());
        echo json_encode($this->getAsArray(), JSON_PRETTY_PRINT) . "\n";

    }

    public static function genUUID() {
        return Utils\Misc::genUUID();
    }

    public function toLog() {
        return $this->getAsArray();
    }
}
