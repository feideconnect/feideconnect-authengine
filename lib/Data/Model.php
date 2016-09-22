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
        case 'json':
            return json_decode($value, true);
        case 'map<text,json>':
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = json_decode($v, true);
            }
            return $ret;
        case 'timestamp':
            return Timestamp::fromCassandraTimestamp($value);
        default:
            return $value;
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

    public function getStorableArray() {

        $a = array();
        foreach (static::$_properties as $k => $type) {
            if (!isset($this->{$k})) {
                continue;
            }
            $value = $this->{$k};
            switch ($type) {
            case 'blob':
                $value = new \Cassandra\Type\Blob($value);
                break;
            case 'boolean':
                break; // Basic type -- nothing to change
            case 'json':
                $value = json_encode($value);
                break;
            case 'list<text>':
                $value = new \Cassandra\Type\CollectionList($value, \Cassandra\Type\Base::ASCII);
                break;
            case 'map<text,blob>':
                $value = new \Cassandra\Type\CollectionMap($value, \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::BLOB);
                break;
            case 'map<text,json>':
                $value = array_map('json_encode', $value);
                $value = new \Cassandra\Type\CollectionMap($value, \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII);
                break;
            case 'map<text,set<text>>':
                $value = new \Cassandra\Type\CollectionMap($value, \Cassandra\Type\Base::ASCII, ['type' => \Cassandra\Type\Base::COLLECTION_SET, 'value' => \Cassandra\Type\Base::ASCII]);
                break;
            case 'map<text,text>':
                $value = new \Cassandra\Type\CollectionMap($value, \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII);
                break;
            case 'map<text,timestamp>':
                $value = new \Cassandra\Type\CollectionMap($value, \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::TIMESTAMP);
                break;
            case 'map<text,uuid>':
                $value = new \Cassandra\Type\CollectionMap($value, \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::UUID);
                break;
            case 'set<text>':
                $value = new \Cassandra\Type\CollectionSet($value, \Cassandra\Type\Base::ASCII);
                break;
            case 'text':
                break; // Basic type -- nothing to change
            case 'timestamp':
                $value = $value->getDBobject();
                break;
            case 'uuid':
                $value = new \Cassandra\Type\Uuid($value);
                break;
            default:
                break;
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
