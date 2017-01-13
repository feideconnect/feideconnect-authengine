<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\HTTP\Redirect;
use FeideConnect\Authentication;
use FeideConnect\OAuth\APIProtector;
use FeideConnect\OAuth\ScopesInspector;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Utils\URL;

class Auth {

    public static function logout() {

        // echo '<pre>'; print_r($_SESSION); exit;

        // Authentication\Authenticator::logoutAll();


        $auth = new Authentication\Authenticator();
        $auth->logout();

        return new Redirect('/loggedout');

        // $auth = new Auth
        // entication\Authenticator();
        // $auth->logout();

    }

    public static function userdebug() {

        $storage = StorageProvider::getStorage();
        $auth = new Authentication\Authenticator();

        if (isset($_REQUEST['logout']) && $_REQUEST['logout'] === '1') {
            $auth->logout();
            $res = ['logout' => "ok"];
            $res = new JSONResponse($response);
            $res->setCORS(false);
            return $res;
        }

        $auth->requireAuthentication();
        $account = $auth->getAccount();

        $response = [
            "account" => [
                "userids" => $account->getUserIDs(),
                "sourceID" => $account->getSourceID(),
                "name" => $account->getName(),
                "mail" => $account->getMail()
            ]
        ];

        $usermapper = new Authentication\UserMapper($storage);
        $user = $usermapper->getUser($account, true, true, false);

        if (isset($user)) {
            $response['user'] = $user->getAsArray();
            $response['userinfo'] = $user->getUserInfo();
        }

        if (isset($response["user"]["profilephoto"]) && is_array($response["user"]["profilephoto"])) {
            $response["user"]["profilephoto"] = array_map("base64_encode", $response["user"]["profilephoto"]);
        }

        if (isset($response["userinfo"]["profilephoto"]) ) {
            $response["userinfo"]["profilephoto"] = base64_encode($response["userinfo"]["profilephoto"]);
        }

        $response['attributes'] = $auth->getRawAttributes();
        $response['visualTag'] = $account->getVisualTag();

        // echo '<pre>'; print_r($response); exit;

        $res = new JSONResponse($response);
        $res->setCORS(false);
        return $res;

    }

    public static function userinfo() {

        $apiprotector = APIProtector::get();
        $user = $apiprotector
            ->requireClient()->requireUser()
            ->getUser();
        $client = $apiprotector->getClient();


        $accesses = ScopesInspector::scopesToAccesses($apiprotector->getScopes());

        $userinfo = $user->getAccessibleUserInfo($accesses);

        $data = [
            'user' => $userinfo,
            'audience' => $client->id,
        ];


        return new JSONResponse($data);

    }

}
