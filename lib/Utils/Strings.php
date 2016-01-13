<?php

namespace FeideConnect\Utils;

class Strings {
    public static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
}
