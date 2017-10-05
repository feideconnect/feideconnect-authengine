<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\ImageResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Config;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Authentication\UserID;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Localization;

class Data {


    public static function getUserProfilephoto($useridstr) {

        $storage = StorageProvider::getStorage();
        $userid = new UserID($useridstr);

        if ($userid->prefix !== 'p') {
            throw new Exception('You may only lookup users by p: keys');
        }

        $user = $storage->getUserByUserIDsec($useridstr);

        $response = new ImageResponse();

        if (!empty($user)) {
            $userinfo = $user->getUserInfo();
            if (!empty($userinfo['profilephoto'])) {
                // $response->setImage(substr($userinfo['profilephoto'], 4), 'jpeg');
                $response->setImage($userinfo['profilephoto'], 'jpeg');
            } else {
                $response->setImageFile('www/static/media/default-profile.jpg', 'jpeg');
            }
        } else {
            $response->setImageFile('www/static/media/default-profile.jpg', 'jpeg');
        }


        return $response;
    }

    public static function getClientLogo($clientid) {

        $storage = StorageProvider::getStorage();
        $client = $storage->getClient($clientid);
        $response = new ImageResponse();

        if (!empty($client->logo)) {
            $response->setImage($client->logo, 'jpeg');
        } else {
            $response->setImageFile('www/static/media/default-client.png', 'png');
        }
        return $response;
    }

    public static function scmp($a, $b) {


        $ax = ($a["distance"] === null ? 9999 : $a["distance"]);
        $bx = ($b["distance"] === null ? 9999 : $b["distance"]);


        return ($ax < $bx) ? -1 : 1;
    }

    public static function getOrgs() {

        $storage = StorageProvider::getStorage();
        $orgs = $storage->getOrgsByService('auth');
        $data = [];

        foreach ($orgs as $org) {
            if (!$org->isHomeOrg()) {
                continue;
            }
            // if (!in_array($org->realm, $subscribers)) { continue; }
            $di = $org->getOrgInfo();
            $data[] = $di;
        }

        usort($data, ["\FeideConnect\Controllers\Data", "scmp"]);


        // echo '<pre>';
        // foreach($data as $d) {
        //     echo join(',', $d["type"]) . "\n";
        // }

        // echo '<pre>Data:'; print_r($data); exit;
        return new JSONResponse($data);
    }


    public static function getDictionary() {

        return new JSONResponse(Localization::getDictionary());

    }

    public static function accountchooserConfig() {

        $config = [];
        $config['feideIdP'] = Config::getValue('feideIdP');
        $config['endpoints'] = Config::getValue('endpoints');
        $config['langCookieDomain'] = Config::getValue('langCookieDomain', '.dataporten.no');

        // $ldata = Localization::localizeList($data, ['title', 'descr']);
        return new JSONResponse($config);
    }


    public static function accountchooserExtra() {


        $data = Config::getValue('disco');
        $ldata = Localization::localizeList($data, ['title', 'descr']);
        return new JSONResponse($ldata);

    }




}
