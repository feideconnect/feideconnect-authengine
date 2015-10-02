<?php

namespace FeideConnect;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Utils\Misc;

class Localization {

    protected static $langCache = [];


    protected static $aliases = [
        "no" => "nb"
    ];

    function __construct() {

    }


    static function getDictionary() {

        if (Config::getValue('enableLocalization', false)) {
            $availableLanguages = Config::getValue('availableLanguages', ['en']);
            $lang = Misc::getBrowserLanguage($availableLanguages);
            $dictionaryFile = Config::filepath('dictionaries/build/dictionary.' . $lang . '.json');
        } else {
            $dictionaryFile = Config::filepath('dictionaries/dictionary.en.json');
        }




        if (!file_exists($dictionaryFile)) {
            throw new \Exception('Cannot locate dictionary file: ' . $dictionaryFile);
        }
        $dictionaryContent = file_get_contents($dictionaryFile);
        $dict = json_decode($dictionaryContent, true);
        if (empty($dict)) {
            throw new \Exception('Dictionary file was empty or not properly formatted JSON');
        }

        return $dict;
    }


    static function localizeEntry($entry) {

        if (is_string($entry)) {
            return $entry;
        } else if (is_array($entry)) {
            $keys = array_keys($entry);
            $sl = Misc::getBrowserLanguage($keys);
            return $entry[$sl];
        }

        return $entry;
    }

    static function localizeList($list, $attrs) {

        $res = [];
        foreach($list as $item) {
            foreach ($item as $key => $val) {

                if (in_array($key, $attrs)) {
                    $item[$key] = self::localizeEntry($item[$key]);
                }

            }
            $res[] = $item;
        }
        return $res;

    }





}
