<?php

namespace FeideConnect\Data\Models;
use FeideConnect\Localization;
class IdProvider {

    public static function uiFromMeta($m) {
        $ui = [];

        $ui['entityID'] = $m['entityid'];

        if (isset($m['uimeta']['UIInfo']) && !empty($m['uimeta']['UIInfo']['DisplayName'])) {
            $ui['title'] = Localization::localizeEntry($m['uimeta']['UIInfo']['DisplayName']);
        } else if (!empty($m['uimeta']['name'])) {
            $ui['title'] = Localization::localizeEntry($m['uimeta']['name']);
        } else {
            $ui['title'] = $m['entityid'];
        }

        if (isset($m['uimeta']['UIInfo']) && !empty($m['uimeta']['UIInfo']['Description'])) {
            $ui['descr'] = Localization::localizeEntry($m['uimeta']['UIInfo']['Description']);
        } else if (!empty($m['uimeta']['description'])) {
            $ui['descr'] = Localization::localizeEntry($m['uimeta']['description']);
        }

        $ui['keywords'] = [];
        if (isset($m['uimeta']['UIInfo']) && !empty($m['uimeta']['UIInfo']['Keywords'])) {
            $ui['keywords'] = Localization::localizeEntry($m['uimeta']['UIInfo']['Keywords']);
        }
        if (!empty($m['uimeta']['scope'])) {
            $ui['keywords'] = array_merge($ui['keywords'], $m['uimeta']['scope']);
        }

        if (isset($m['uimeta']['DiscoHints']) && isset($m['uimeta']['DiscoHints']['GeolocationHint'])) {

            $geoList = [];

            if (
                is_array($m['uimeta']['DiscoHints']['GeolocationHint']) &&
                !empty($m['uimeta']['DiscoHints']['GeolocationHint'])
            ) {
                foreach($m['uimeta']['DiscoHints']['GeolocationHint'] AS $geohint) {
                    if (preg_match('^geo:(.+),(.*)^', $geohint)) {
                        $geostr = explode(',', substr($geohint, 4));
                        $geoList[] = [
                            'lat' => floatval($geostr[0]),
                            'lon' => floatval($geostr[1]),
                        ];
                    }
                }
                $ui['geo'] = $geoList;
            } else {
                // error_log(json_encode($m['uimeta']['DiscoHints']['GeolocationHint']));
            }

        }

        if (isset($ui['descr']) && $ui['descr'] === $ui['title']) {
            unset($ui['descr']);
        }

        $m['_'] = $ui;
        if (isset($m['logo_etag'])) {
            $ui['logo'] = true;
        } else {
            $ui['logo'] = false;
        }
        return $ui;

    }


}
