<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\HTTP\Redirect;
use FeideConnect\Authentication;
use FeideConnect\OAuth\APIProtector;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Utils\URL;

class Auth {

    public static function logout() {

        // echo '<pre>'; print_r($_SESSION); exit;

        // Authentication\Authenticator::logoutAll();


        $auth = new Authentication\Authenticator($authconfig);
        $auth->logout();

        return new Redirect('/loggedout');

        // $auth = new Auth
        // entication\Authenticator();
        // $auth->logout();

    }

    public static function userdebug() {


        $storage = StorageProvider::getStorage();

        // $accountchooser = new Authentication\AccountChooserProtocol();
        // // $accountchooser->debug();

        // if (!$accountchooser->hasResponse()) {
        //     $requestURL = $accountchooser->getRequest();
        //     URL::redirect($requestURL);
        // }

        // $authconfig = [
        //     "type" => "twitter"
        // ];
        // $authconfig = $accountchooser->getAuthConfig();
        $authconfig = [];
        // echo '<pre>Auth config is '; print_r($authconfig); exit;
//
        $auth = new Authentication\Authenticator($authconfig);

        if (isset($_REQUEST['logout']) && $_REQUEST['logout'] === '1') {
            $auth->logout();
            $res = ['logout' => "ok"];
            $res = new JSONResponse($response);
            $res->setCORS(false);
            return $res;
        }



        $auth->requireAuthentication(false, true); // require($isPassive = false, $allowRedirect = false, $return = null

        $account = $auth->getAccount();

        // $res = $auth->storeUser();
        //
        // $response = array('account' => $account->getAccountID());
        $response = [
            "account" => [
                "userids" => $account->getUserIDs(),
                "sourceID" => $account->getSourceID(),
                "name" => $account->getName(),
                "mail" => $account->getMail()
            ]
        ];
        // echo '<pre>';
        // print_r($response); exit;


        $usermapper = new Authentication\UserMapper($storage);



        $user = $usermapper->getUser($account, true, true, false);

        // header('Content-type: text/plain');
        // print_r($user); exit;
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

        // echo '<pre>'; print_r($response); exit;

        $res = new JSONResponse($response);
        $res->setCORS(false);
        return $res;

    }

    public static function userinfo() {



        $apiprotector = new APIProtector(getallheaders());
        $user = $apiprotector
            ->requireClient()->requireUser()->requireScopes(['userinfo'])
            ->getUser();
        $client = $apiprotector->getClient();


        $hasScopes = $apiprotector->getScopes();


        $allowseckeys = ['userid'];
        $includeEmail = false;

        foreach ($hasScopes as $scope) {
            $data[$scope] = true;
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


        $data = [
            'user' => $userinfo,
            'audience' => $client->id,
        ];


        return new JSONResponse($data);

    }

    public static function authinfo() {

        $storage = StorageProvider::getStorage();

        $apiprotector = new OAuth\APIProtector(getallheaders());
        $user = $apiprotector
            ->requireClient()->requireUser()->requireScopes(['userinfo'])
            ->getUser();
        // $client = $apiprotector->getClient();

        $allowseckeys = ['userid'];
        $allowseckeys[] = 'p';
        $allowseckeys[] = 'feide';

        // $includeEmail = true;

        $authorizations = $storage->getAuthorizationsByUser($user);

        $data = [
            "authorizations" => [],
            "tokens" => [],
        ];
        foreach ($authorizations as $auth) {
            $data["authorizations"][] = $auth->getAsArray();
        }

        return new JSONResponse($data);

    }


}
