<?php

namespace FeideConnect\Utils;

/**
 * A helper class that protects against redirect loop
 */
class RedirectLoopProtector {

    private static $length = 7; // Number of checkpoints..
    private static $limit = 5; // Minimum number of seconds that are OK since the 5th last checkpoint.

    public static function protect() {

        $trail = [];
        if (!empty($_COOKIE['RLPTRAIL'])) {
            $trail = json_decode($_COOKIE['RLPTRAIL'], true);
            if (empty($trail)) {
                $trail = [];
            }
        }
        $trail = array_slice($trail, 1-(self::$length));
        array_push($trail, time());
        $_COOKIE['RLPTRAIL'] = json_encode($trail);
        setcookie("RLPTRAIL", json_encode($trail), time()+180);

        if (isset($_GET['resetrpl']) && $_GET['resetrpl'] === '1') {
            return true;
        }

        $ok = true;
        $timewindow = time() - $trail[0];
        if (count($trail) >= (self::$length )) {
            if ($timewindow <= self::$limit) {
                $ok = false;
            }
        }

        return $ok;
    }

}
