<?php

namespace FeideConnect\Utils;

use FeideConnect\Config;
use FeideConnect\Localization;

class Misc {

    protected static $langCache = [];

    public static function reset() {
        self::$langCache = [];
    }


    /*
     * Source:
     * http://codereview.stackexchange.com/questions/9141/language-detection-php-script
     */
    public static function getBrowserLanguage($available_languages, $http_accept_language = 'auto') {

        $cachestr = join('|', $available_languages);
        if (isset(self::$langCache[$cachestr])) {
            return self::$langCache[$cachestr];
        }


        $aliases = [
            "no" => "nb"
        ];
        if (in_array("nb", $available_languages)) {
            $available_languages[] = 'no';
        }

        if (isset($_GET[Localization::LANGUAGE_PARAM_NAME]) &&
            in_array($_GET[Localization::LANGUAGE_PARAM_NAME], $available_languages, true)
        ) {
            // user-selected language
            $lang = $_GET[Localization::LANGUAGE_PARAM_NAME];

            if (isset($aliases[$lang])) {
                $lang = $aliases[$lang];
            }
            self::$langCache[$cachestr] = $lang;

            setcookie(
                'lang',
                $lang,
                time() + 60*60*24*365*10,
                '/',
                Config::getValue('langCookieDomain', '.dataporten.no')
            );
            return $lang;
        }


        // TODO : an accept header of 'no' should aid in preferring 'nb'.
        // Need to support some kind of alias for this

        if ($http_accept_language == 'auto') {
            $http_accept_language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '' );
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
            if (!empty($arr[5])) {
                $qvalue = floatval($arr[5]);
            }

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



        if ($bestqval === 0) {
            foreach (['nb', 'nn', 'en', 'se'] AS $ql) {
                if (in_array($ql, $available_languages)) {
                    $bestlang = $ql;
                    break;
                }
            }
        }

        if (isset($_COOKIE["lang"]) && in_array($_COOKIE["lang"], $available_languages)) {
            $bestlang = $_COOKIE["lang"];
        }


        // echo '<pre>';
        // echo "Available\n";
        // var_dump($available_languages);
        // echo "Hits\n";
        // var_dump($hits);
        // echo "BEst lang\n";
        // var_dump($bestlang);
        // var_dump($bestqval);
        // exit;


        if (isset($aliases[$bestlang])) {
            $bestlang = $aliases[$bestlang];
        }

        self::$langCache[$cachestr] = $bestlang;

        return $bestlang;
    }


    public static function randomShort() {
        return unpack('n', openssl_random_pseudo_bytes(2))[1];
    }

    public static function genUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            self::randomShort(),
            self::randomShort(),
            // 16 bits for "time_mid"
            self::randomShort(),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            (self::randomShort() & 0xfff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (self::randomShort() & 0x3fff) | 0x8000,
            // 48 bits for "node"
            self::randomShort(),
            self::randomShort(),
            self::randomShort()
        );
    }

    public static function ensureArray($data) {
        if ($data === null) {
            return [];
        }
        return $data;
    }

    public static function containsSameElements($a, $b) {
        return count(array_diff($a, $b)) === 0 && count(array_diff($b, $a)) === 0;
    }
}
