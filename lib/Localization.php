<?php

namespace FeideConnect;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Utils\Misc;

class Localization {

    const LANGUAGE_PARAM_NAME = 'lang';

    protected static $dict = null;


    public static function load($lang = null)
    {
        if (self::$dict !== null) {
            return self::$dict;
        }

        if (Config::getValue('enableLocalization', false)) {
            if ($lang === null) {
                $availableLanguages = Config::getValue('availableLanguages', ['en']);
                $lang = Misc::getBrowserLanguage($availableLanguages);
            }
            $dictionaryFile = Config::filepath('dictionaries/build/dictionary.' . $lang . '.json');
        } else {
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
        return self::$dict;
    }


    public static function getDictionary($lang = null)
    {
        self::load($lang);
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


    /**
     * This is the translation function used by the Twig internationalization function. It allows you to translate
     * a string in singular, and perform substitutions of placeholders.
     *
     * @param string $original The ID of the string to translate.
     *
     * @return string The translated text.
     */
    public static function translateSingular($original)
    {
        $text = self::localizeEntry(self::getTerm($original));

        if (func_num_args() === 1) {
            return $text;
        }

        $args = array_slice(func_get_args(), 1);

        return strtr($text, is_array($args[0]) ? $args[0] : $args);
    }
}
