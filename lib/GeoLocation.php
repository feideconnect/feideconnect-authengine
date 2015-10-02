<?php

namespace FeideConnect;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;

class GeoLocation {




    protected static $reader = null;

    function __construct() {

        $geoipfile = Config::filepath(Config::getValue('geodb'));

        if (!file_exists($geoipfile)) {
            throw new \Exception('Could not location geo location database');
        }

        if (!class_exists('GeoIp2\Database\Reader')) {
            throw new Exception("Not properly loaded GeoIP library through composer.phar.");
        }


        if (self::$reader === null) {
            self::$reader = new \GeoIp2\Database\Reader($geoipfile); // 'var/GeoIP2-City.mmdb');
        }

    }


    function getLocation() {

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
            // return $record;
            return $geo;
            // print_r($record->country->isoCode); exit;

            // $obj = array();
            // $obj['lat'] = $record->location->latitude;
            // $obj['lon'] = $record->location->longitude;
            // $obj['tz'] = $record->location->timeZone;
            // $tz = $obj['tz'];

        } catch(Exception $e) {
            // $tz = 'Europe/Amsterdam';
            error_log("Error looking up GeoIP for address: " . $ip);
        }
        return null;


        return ["file" => $this->file];
    }




    public function countryFromIP($ip) {

        try {
            $record = self::$reader->city($ip);
            return $record->country->isoCode;
            // print_r($record->country->isoCode); exit;

            // $obj = array();
            // $obj['lat'] = $record->location->latitude;
            // $obj['lon'] = $record->location->longitude;
            // $obj['tz'] = $record->location->timeZone;
            // $tz = $obj['tz'];

        } catch(Exception $e) {
            // $tz = 'Europe/Amsterdam';
            error_log("Error looking up GeoIP for address: " . $ip);
        }

        return null;


    }

    public function geoFromIP($ip) {

        try {
            $record = self::$reader->city($ip);
            $geo = array(
                'lat' => $record->location->latitude,
                'lon' => $record->location->longitude
            );
            return $geo;
            // print_r($record->country->isoCode); exit;

            // $obj = array();
            // $obj['lat'] = $record->location->latitude;
            // $obj['lon'] = $record->location->longitude;
            // $obj['tz'] = $record->location->timeZone;
            // $tz = $obj['tz'];

        } catch(Exception $e) {
            // $tz = 'Europe/Amsterdam';
            error_log("Error looking up GeoIP for address: " . $ip);
        }
        return null;
    }

    function addrGeo($address) {

        // Temorary disabled. Needs to figure out a way to use a API key and add caching.
        return null;

        // if ($this->store->exists('geoaddr', $id, NULL)) {
        //     // SimpleSAML_Logger::debug('IP Geo location (geo): Found ip [' . $ip . '] in cache.');
        //     $stored =  $this->store->getValue('geoaddr', $id, NULL);
        //     if ($stored === NULL) throw new Exception('Got negative cache for this Address');
        //     return $stored;
        // }


        $url = 'http://maps.google.com/maps/geo?' .
            'q=' . urlencode($address) .
            '&key=ABQIAAAASglC3nGToDgiRPCcRfdVShS0II289t7QEnBTuvu6rL3UOFsbQRRRned7x9TQ1oaYxRr6qsA98J-tuA' .
            '&sensor=false' .
            '&output=json' .
            '&oe=utf8';
        $res = file_get_contents($url);
        $result = json_decode($res);
        if ($result->Status->code !== 200) {
            // $this->store->set('geoaddr', $id, NULL, NULL);
            return NULL;
        }
        $location = array('lat' => $result->Placemark[0]->Point->coordinates[1], 'lon' => $result->Placemark[0]->Point->coordinates[0]);
        return $location;
    }





}