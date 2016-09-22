<?php

namespace FeideConnect\Data;

use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Types\Timestamp;
use FeideConnect\Utils;

abstract class Model implements Utils\Loggable {

    protected $_repo;
    protected static $_properties = [];


    public function __construct($props = array()) {
        $this->_repo = StorageProvider::getStorage();

        foreach (static::$_properties as $k => $type) {
            $this->{$k} = null;
        }

        foreach ($props as $k => $v) {
            if (!array_key_exists($k, static::$_properties)) {
                error_log(get_class($this) . ": Trying to set a property [" . $k . "] that is not legal.");
                continue;
            }

            // Force specified typed attributes to be of correct type class.
            if (static::$_properties[$k] === 'timestamp') {
                if (!($v instanceof Timestamp) && !(is_null($v))) {
                    error_log(get_class($this) . ": Trying to set property [" . $k . "] with an invalid timestamp type");
                    continue;
                }
            }

            $this->{$k} = $v;
        }

    }

    public static function fromDB($key, $value) {
        if (!array_key_exists($key, static::$_properties)) {
            throw new \Exception('Database field ' . var_export($key, true) . ' is not a valid field for the model ' . static::class . '.');
        }
        if ($value === null) {
            return null;
        }
        switch (static::$_properties[$key]) {
        case 'blob':
            return $value->toBinaryString();
        case 'boolean':
            return $value; // Basic type -- nothing to change
        case 'json':
            return json_decode($value, true);
        case 'list<text>':
            return $value->values();
        case 'map<text,blob>':
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = $v->toBinaryString();
            }
            return $ret;
        case 'map<text,json>':
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = json_decode($v, true);
            }
            return $ret;
        case 'map<text,set<text>>':
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = $v->values();
            }
            return $ret;
        case 'map<text,timestamp>':
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = $v->microtime(true) * 1000;
            }
            return $ret;
        case 'map<text,text>':
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = $v;
            }
            return $ret;
        case 'map<text,uuid>':
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = $v->uuid();
            }
            return $ret;
        case 'set<text>':
            return $value->values();
        case 'text':
            return $value; // Basic type -- nothing to change
        case 'timestamp':
            return Timestamp::fromCassandraTimestamp($value);
        case 'uuid':
            return $value->uuid();
        default:
            throw new \Exception('Database field ' . var_export($key, true) . ' has an invalid type for the model ' . static::class . '.');
        }

    }


    public function has($attrname) {
        return (!empty($this->{$attrname}));
    }

    public function getAsArray() {

        $a = array();
        foreach (static::$_properties as $k => $type) {
            if (isset($this->{$k})) {
                if ($type === 'timestamp') {
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

    private static function createFromList(\Cassandra\Type $type, array $values) {
        $ret = $type->create();
        foreach ($values as $value) {
            $ret->add($value);
        }
        return $ret;
    }

    private static function createFromMap(\Cassandra\Type $type, array $map) {
        $ret = $type->create();
        foreach ($map as $key => $value) {
            $ret->set($key, $value);
        }
        return $ret;
    }

    public function getStorableArray() {

        $a = array();
        foreach (static::$_properties as $k => $type) {
            if (!isset($this->{$k})) {
                continue;
            }
            $value = $this->{$k};
            switch ($type) {
            case 'blob':
                $value = new \Cassandra\Blob($value);
                break;
            case 'boolean':
                break; // Basic type -- nothing to change
            case 'json':
                $value = json_encode($value);
                break;
            case 'list<text>':
                $value = self::createFromList(\Cassandra\Type::collection(\Cassandra\Type::text()), $value);
                break;
            case 'map<text,blob>':
                $value = array_map(function ($v) { return new \Cassandra\Blob($v); }, $value);
                $value = self::createFromMap(\Cassandra\Type::map(\Cassandra\Type::text(), \Cassandra\Type::blob()), $value);
                break;
            case 'map<text,json>':
                $value = array_map('json_encode', $value);
                $value = self::createFromMap(\Cassandra\Type::map(\Cassandra\Type::text(), \Cassandra\Type::text()), $value);
                break;
            case 'map<text,set<text>>':
                $value = array_map(function ($v) { return self::createFromList(\Cassandra\Type::set(\Cassandra\Type::text()), $v); }, $value);
                $value = self::createFromMap(\Cassandra\Type::map(\Cassandra\Type::text(), \Cassandra\Type::set(\Cassandra\Type::text())), $value);
                break;
            case 'map<text,text>':
                $value = self::createFromMap(\Cassandra\Type::map(\Cassandra\Type::text(), \Cassandra\Type::text()), $value);
                break;
            case 'map<text,timestamp>':
                $value = array_map(function ($v) { $secs = (int)($v / 1000); $usecs = (int)(($v - $secs*1000)*1000); return new \Cassandra\Timestamp($secs, $usecs); }, $value);
                $value = self::createFromMap(\Cassandra\Type::map(\Cassandra\Type::text(), \Cassandra\Type::timestamp()), $value);
                break;
            case 'map<text,uuid>':
                $value = array_map(function ($v) { return new \Cassandra\Uuid($v); }, $value);
                $value = self::createFromMap(\Cassandra\Type::map(\Cassandra\Type::text(), \Cassandra\Type::uuid()), $value);
                break;
            case 'set<text>':
                $value = self::createFromList(\Cassandra\Type::set(\Cassandra\Type::text()), $value);
                break;
            case 'text':
                break; // Basic type -- nothing to change
            case 'timestamp':
                $value = $value->getDBobject();
                break;
            case 'uuid':
                $value = new \Cassandra\Uuid($value);
                break;
            default:
                throw new \Exception('Database field ' . var_export($key, true) . ' has an invalid type for the model ' . static::class . '.');
            }
            $a[$k] = $value;
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
