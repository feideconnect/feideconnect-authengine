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




        $userid = '76a7a061-3c55-430d-8ee0-6f82ec42501f'; // Andreas
        $clientid = '610cbba7-3985-45ae-bc9f-0db0e36f71ad'; // Foodle

        $storage = StorageProvider::getStorage();
        $user = $storage->getUserByUserID($userid);
        $client = $storage->getClient($clientid);

        $request = new Messages\AuthorizationRequest([
            'response_type' => 'code',
            'client_id' => $clientid
        ]);

        $attributes = [
            "eduPersonPrincipalName" => ["andreas@uninett.no"],
            "jpegPhoto" => null,
            "mail" => ["andreas.solberg@uninett.no"],
            "displayName" => ["Andreas Åkre Solberg"],
            "o" => ["UNINETT AS"],
            "feideYearOfBirth" => ["1980"],
            "idp" => "https://idp-test.feide.no",
            "authSource" => "default-sp",
            "o" => ["UNINETT AS"]
        ];
        $attributeMapper = new AttributeMapper();
        $account = $attributeMapper->getAccount($attributes);

        $org = $account->getOrg();

        $ae = new AuthorizationEvaluator($storage, $client, $request, $user);
        $aui = new AuthorizationUI($client, $request, $account, $user, $ae);

        $response = $aui
            ->setFixedBypass(false)
            ->setFixedFirstTime(true)
            ->setFixedSimpleView(false)
            ->setFixedMandatory(true)
            ->show();

        return $response;

    }


}
