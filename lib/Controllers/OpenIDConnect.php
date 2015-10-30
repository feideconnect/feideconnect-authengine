<?php

namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\OAuth\APIProtector;

use FeideConnect\Config;

class OpenIDConnect {



    public static function config() {

        $openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
        $config = $openid->getProviderConfiguration();
        $response = new JSONResponse($config);
        return $response;

    }

    public static function getJWKs() {

        $openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
        $jwks = $openid->getJWKs();

        $data = [
            "keys" => $jwks
        ];


        $response = new JSONResponse($data);
        return $response;
    }

    public static function userinfo() {



        $apiprotector = APIProtector::get();
        $user = $apiprotector
            ->requireClient()->requireUser()->requireScopes(['openid'])
            ->getUser();
        // $client = $apiprotector->getClient();


        $hasScopes = $apiprotector->getScopes();


        $allowseckeys = ['userid'];
        $includeEmail = false;

        if ($apiprotector->hasScopes(['userinfo-feide'])) {
            $allowseckeys[] = 'feide';
        }
        if ($apiprotector->hasScopes(['userinfo-photo'])) {
            $allowseckeys[] = 'p';
        }
        if ($apiprotector->hasScopes(['userinfo-mail'])) {
            $includeEmail = true;
        }

        $userinfo = $user->getBasicUserInfo($includeEmail, $allowseckeys);

        $response = [];
        if (isset($userinfo["userid"])) {
            $response["sub"] = $userinfo["userid"];
        }
        if (isset($userinfo["userid_sec"])) {
            $response["connect-userid_sec"] = $userinfo["userid_sec"];
        }
        if (isset($userinfo["name"])) {
            $response["name"] = $userinfo["name"];
        }
        if (isset($userinfo["email"])) {
            $response["email"] = $userinfo["email"];
            $response["email_verified"] = true;
        }
        if (isset($userinfo["profilephoto"])) {
            $response["picture"] = Config::getValue("endpoints.core") . '/userinfo/v1/user/media/' . $userinfo["profilephoto"];
        }

        // $response["userinfo"] = $userinfo;




        // $data = [
        //     'user' => $userinfo,
        //     'audience' => $client->id,
        // ];


        return new JSONResponse($response);

    }


}
