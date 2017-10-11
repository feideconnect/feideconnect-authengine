<?php

namespace FeideConnect;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Utils\Misc;

class Localization {

    protected static $dict = null;


    public static function load() {

        if (self::$dict !== null) {
            return self::$dict;
        }

        if (Config::getValue('enableLocalization', false)) {
            $availableLanguages = Config::getValue('availableLanguages', ['en']);
            $lang = Misc::getBrowserLanguage($availableLanguages);
            $dictionaryFile = Config::filepath('dictionaries/dictionary.' . $lang . '.json');
        } else {
            $lang = 'en';
            $dictionaryFile = Config::filepath('dictionaries/dictionary.en.json');
        }

        if (!file_exists($dictionaryFile)) {
            throw new \Exception('Cannot locate dictionary file: ' . $dictionaryFile);
        }
        $dictionaryContent = file_get_contents($dictionaryFile);
        self::$dict = json_decode($dictionaryContent, true);
        if (empty(self::$dict)) {
            throw new \Exception('Dictionary file was empty or not properly formatted JSON');
        }
        self::$dict['_lang'] = $lang;
        return self::$dict;
    }


    public static function getDictionary() {
        self::load();
        return self::$dict;
    }


    public static function getTerm($term, $required = false) {
        self::load();

        if (isset(self::$dict[$term])) {
            return self::$dict[$term];
        }
        if ($required === true) {
            throw new \Exception('Missing translation term [' . $term . ']');
        }
        return $term;
    }


    public static function localizeEntry($entry) {

        if (is_string($entry)) {
            return $entry;
        } else if (is_array($entry)) {
            $keys = array_keys($entry);
            $sl = Misc::getBrowserLanguage($keys);
            return $entry[$sl];
        }

        return $entry;
    }

    public static function localizeList($list, $attrs) {

        $res = [];
        foreach ($list as $item) {
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
