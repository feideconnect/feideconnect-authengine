<?php

/*

To enable add this route: 
$this->router->get('/oauth/authorizationui', ['FeideConnect\Controllers\GrantDebug', 'debug']);

*/


namespace FeideConnect\Controllers;
use FeideConnect\OAuth\AuthorizationUI;

use FeideConnect\Data\StorageProvider;
use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\AuthorizationEvaluator;
use FeideConnect\Authentication\AttributeMapper;

use FeideConnect\OAuth\ScopesInspector;


class GrantDebug {

    public static function debug() {

        // $test = new ScopesInspector(['gk_foodle']);
        // echo '<pre>';
        // print_r($test->getInfo());
        // exit;
        // http://localhost/

        $userid = '8e2528b2-f8b0-45f7-9f2a-70feb3b4b57e'; // Andreas
        $attributes = [
            "eduPersonPrincipalName" => ["andreas@uninett.no"],
            "jpegPhoto" => null,
            "mail" => ["andreas.solberg@uninett.no"],
            "displayName" => ["Andreas Åkre Solberg"],
            "o" => ["UNINETT"],
            "feideYearOfBirth" => ["1980"],
            "idp" => "https://idp-test.feide.no",
            "authSource" => "default-sp"
        ];



        // $userid = '9d9e77d0-01ca-4b2f-aac0-2a3442d40aaa';
        // $attributes = [
        //     "twitter.id_str" => ["6405972"],
        //     "twitter.name" => ["Andreas Åkre Solberg"],
        //     "twitter.screen_name" => ["erlang"],
        //     "twitter.location" => ["Trondheim, Norway"],
        //     "twitter.description" => ["Identity, Authentication, Authorization, API, OAuth, OpenID Connect and more..."],
        //     "twitter.url" => ["https://t.co/kGigyNc7DR"],
        //     "twitter.created_at" => ["Tue May 29 07:25:20 +0000 2007"],
        //     "twitter_at_screen_name" => ["@erlang"],
        //     "twitter.profile_image_url" => ["http://pbs.twimg.com/profile_images/36002982/1387422937_3dccca0331_m_normal.jpg"],
        //     "authSource" => "twitter"];




        $clientid = '74b8b784-5350-41de-adf8-2b83dcecd34e'; // Foodle





        $storage = StorageProvider::getStorage();
        $user = $storage->getUserByUserID($userid);
        $client = $storage->getClient($clientid);

        $request = new Messages\AuthorizationRequest([
            'response_type' => 'code',
            'client_id' => $clientid
        ]);



        // echo '<pre>Data'; print_r($attributes); exit;

        $attributeMapper = new AttributeMapper();
        $account = $attributeMapper->getAccount($attributes);
        // echo '<pre>'; print_r($account); exit;

        $org = $account->getOrg();

        // echo '<pre>ORG:'; var_dump($org); exit;

        $ae = new AuthorizationEvaluator($storage, $client, $request, $user);
        $aui = new AuthorizationUI($client, $request, $account, $user, $ae);

        $response = $aui
            ->setFixedBypass(false)
            ->setFixedFirstTime(true)
            ->setFixedSimpleView(false)
            ->setFixedMandatory(false)
            ->setFixedFeideUser(false)
            ->show();

        return $response;

    }


}
