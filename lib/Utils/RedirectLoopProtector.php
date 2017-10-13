<?php

namespace FeideConnect\Utils;
use FeideConnect\Logger;

/**
 * A helper class that protects against redirect loop
 */
class RedirectLoopProtector {

    private static $length = 7; // Number of checkpoints.. Must be AT LEAST 2.
    private static $limit = 5; // Minimum number of seconds that are OK since the n-th last checkpoint.

    public static function protect() {

        $trail = [];
        if (!empty($_COOKIE['RLPTRAIL'])) {
            $trail = @json_decode($_COOKIE['RLPTRAIL']);
            if ($trail === null) {
                Logger::warning('Could not properly decode RLPTRAIL cookie as JSON');
                $trail = [];
            } else if (!is_array($trail)) {
                Logger::warning('Could not properly JSON decode RLPTRAIL to an array');
                $trail = [];
            }
        }
        $trail = array_slice($trail, 1-(self::$length));
        array_push($trail, time());
        setcookie("RLPTRAIL", json_encode($trail), time()+180, "", "", true, true);

        // The resetrlp parameter is set to be an timestamp of when user manually accepted redirect warning
        if (isset($_REQUEST['resetrlp'])) {
            if ( intval($trail[0]) <=  intval($_REQUEST['resetrlp']) ) {
                // Consider RESETRLP as valid as long as the first item in the trail is LATER OR EQUAL than the RESETTLP value
                Logger::info('User did provide a valid redirect loop protection RESET ');
                return true;
            } else {
                Logger::warning('The "resetrlp" parameter was not accepted, because it was outdated. One reason may be that a redirect loop includes the resetrlp parameter');
            }
        }

        $timewindow = time() - intval($trail[0]);
        if (count($trail) >= (self::$length )) {
            if ($timewindow <= self::$limit) {
                return false;
            }
        }

        return true;
    }

}
