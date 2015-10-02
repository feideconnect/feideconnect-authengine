<?php

namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\OAuth\APIProtector;

use FeideConnect\Config;

class OpenIDConnect {



    static function config() {

        $openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
        $config = $openid->getProviderConfiguration();
        $response = new JSONResponse($config);
        return $response;

    }

    static function getJWKs() {

        $openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
        $jwks = $openid->getJWKs();

        $data = [
            "jwk" => $jwks
        ];


        $response = new JSONResponse($data);
        return $response;
    }

    static function userinfo() {



        $apiprotector = new APIProtector();
        $user = $apiprotector
            ->requireClient()->requireUser()->requireScopes(['openid'])
            ->getUser();
        // $client = $apiprotector->getClient();


        $hasScopes = $apiprotector->getScopes();


        $allowseckeys = ['userid'];
        $includeEmail = false;

        foreach ($hasScopes as $scope) {
            // $data[$scope] = true;
            if ($scope === 'userinfo-feide') {
                $allowseckeys[] = 'feide';
            }
            if ($scope === 'userinfo-photo') {
                $allowseckeys[] = 'p';
            }
            if ($scope === 'userinfo-mail') {
                $includeEmail = true;
            }
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
            $response["picture"] = Config::getValue("endpoints.core") . '/userinfo/user/media/' . $userinfo["profilephoto"];
        }

        // $response["userinfo"] = $userinfo;




        // $data = [
        //     'user' => $userinfo,
        //     'audience' => $client->id,
        // ];


        return new JSONResponse($response);

    }


}
