<?php


namespace FeideConnect\Data;

use FeideConnect\Data\Repositories\Cassandra2;

class StorageProvider {

    public static $storage = null;

    public static function init() {
        if (self::$storage !== null) {
            return;
        }

        $storageType = \FeideConnect\Config::getValue('storage.type');

        if ($storageType === 'cassandra') {
            self::$storage = new Cassandra2();

        } else {
            throw new \Exception('Trying to initialize unsupported storage type [' . $storageType . '] in StorageProvider.');
        }

    }


    public static function getStorage() {

        self::init();
        return self::$storage;

    }


}
