<?php

namespace FeideConnect;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;

class GeoLocation {

    protected static $reader = null;

    public function __construct() {

        $geoipfile = Config::filepath(Config::getValue('geodb'));

        if (!file_exists($geoipfile)) {
            throw new \Exception('Could not location geo location database');
        }

        if (!class_exists('GeoIp2\Database\Reader')) {
            throw new Exception("Not properly loaded GeoIP library through composer.phar.");
        }


        if (self::$reader === null) {
            self::$reader = new \GeoIp2\Database\Reader($geoipfile);
        }

    }

    public function getLocation() {

        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_REQUEST['ip'])) {
            $ip = $_REQUEST['ip'];
        }

        try {
            $record = self::$reader->city($ip);

            $code = null;
            $title = '';
            if (isset($record->country)) {

                if (isset($record->country->isoCode)) {
                    $code = strtolower($record->country->isoCode);
                }
                $title = $record->country->names['en'];
                if (isset($record->city->name)) {
                    $title .= ', ' . $record->city->name;
                }

                if (isset($record->mostSpecificSubdivision->name) && $record->mostSpecificSubdivision->name !== $record->city->name) {
                    $title .= ', ' . $record->mostSpecificSubdivision->name;
                }

            }

            $geo = array(
                'lat' => $record->location->latitude,
                'lon' => $record->location->longitude,
                'title' => $title,
                'country' => $code,
            );
            return $geo;

        } catch (\Exception $e) {
            error_log("Error looking up GeoIP for address: " . $ip);
        }
        return null;
    }

}
