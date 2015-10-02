<?php

namespace FeideConnect\Utils;

class Misc {

    protected static $langCache = [];

    /*
     * Source:
     * http://codereview.stackexchange.com/questions/9141/language-detection-php-script
     */
    static function getBrowserLanguage($available_languages, $http_accept_language = 'auto') {





        $cachestr = join('|', $available_languages);
        if (isset(self::$langCache[$cachestr])) {
            return self::$langCache[$cachestr];
        }

        // TODO : an accept header of 'no' should aid in preferring 'nb'.
        // Need to support some kind of alias for this

        if ($http_accept_language == 'auto') {
            $http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        $pattern = '/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i';

        preg_match_all($pattern, $http_accept_language, $hits, PREG_SET_ORDER);

        $bestlang = $available_languages[0];
        $bestqval = 0;

        foreach ($hits as $arr) {
            $langprefix = strtolower($arr[1]);
            if (!empty($arr[3])) {
                $langrange = strtolower($arr[3]);
                $language = $langprefix . "-" . $langrange;
            } else {
                $language = $langprefix;
            }
            $qvalue = 1.0;
            if (!empty($arr[5])) $qvalue = floatval($arr[5]);

            // find q-maximal language
            if (in_array($language, $available_languages) && ($qvalue > $bestqval)) {
                $bestlang = $language;
                $bestqval = $qvalue;
            } else if (in_array($langprefix, $available_languages) && (($qvalue*0.9) > $bestqval)) {
                // if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
                $bestlang = $langprefix;
                $bestqval = $qvalue*0.9;
            }
        }


        if (isset($_COOKIE["lang"]) && in_array($_COOKIE["lang"], $available_languages)) {
            $bestlang = $_COOKIE["lang"];
        }


        // var_dump($_COOKIE["lang"]);
        // var_dump($available_languages);
        // var_dump($hits);
        // var_dump($bestlang);
        // exit;


        self::$langCache[$cachestr] = $bestlang;
        return $bestlang;
    }



    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    /*::                                                                         :*/
    /*::  This routine calculates the distance between two points (given the     :*/
    /*::  latitude/longitude of those points). It is being used to calculate     :*/
    /*::  the distance between two locations using GeoDataSource(TM) Products    :*/
    /*::                                                                         :*/
    /*::  Definitions:                                                           :*/
    /*::    South latitudes are negative, east longitudes are positive           :*/
    /*::                                                                         :*/
    /*::  Passed to function:                                                    :*/
    /*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
    /*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
    /*::    unit = the unit you desire for results                               :*/
    /*::           where: 'M' is statute miles (default)                         :*/
    /*::                  'K' is kilometers                                      :*/
    /*::                  'N' is nautical miles                                  :*/
    /*::  Worldwide cities and other features databases with latitude longitude  :*/
    /*::  are available at http://www.geodatasource.com                          :*/
    /*::                                                                         :*/
    /*::  For enquiries, please contact sales@geodatasource.com                  :*/
    /*::                                                                         :*/
    /*::  Official Web site: http://www.geodatasource.com                        :*/
    /*::                                                                         :*/
    /*::         GeoDataSource.com (C) All Rights Reserved 2015                        :*/
    /*::                                                                         :*/
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    static function distance($lat1, $lon1, $lat2, $lon2) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.1515 * 1.609344;
        return $kilometers;
    }

    public static function genUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
