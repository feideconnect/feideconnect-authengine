<?php
namespace tests;

use FeideConnect\Data\StorageProvider;

use FeideConnect\OAuth\AuthorizationUI;
use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\AuthorizationEvaluator;
use FeideConnect\OAuth\Messages\AuthorizationRequest;
use FeideConnect\Authentication\AttributeMapper;


class AuthorizationUITest extends DBHelper {
    protected $user, $client;

    public function setUp() {
        parent::setUp();
        $this->user = $this->user('feide:olanormann@uninett.no');
        $this->client = $this->client();
    }

    private function getRequest($redirect_uri = null) {

        $req = [
            "response_type" => "code",
            "scope" => "email userinfo",
            "client_id" => "f1343f3a-79cc-424f-9233-5fe33f8bbd56"
        ];
        if ($redirect_uri !== null) {
            $req['redirect_uri'] = $redirect_uri;
        }
        return new AuthorizationRequest($req);
    }


    public function testBasic() {


        $request = $this->getRequest();
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request);
        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();
        $this->assertEquals($redirect_uri, 'http://example.org', 'When request does not contain any redirect_uri, return first preconfigured one.');

        $storage = StorageProvider::getStorage();

        $userid = $this->user->userid; // Andreas
        $clientid = $this->client->id;


        $request = new Messages\AuthorizationRequest([
            'response_type' => 'code',
            'client_id' => $clientid
        ]);

        $attributes = [
            "eduPersonPrincipalName" => ["olanormann@uninett.no"],
            "jpegPhoto" => null,
            "mail" => ["ola.normann@uninett.no"],
            "displayName" => ["Ola Normann"],
            "o" => ["UNINETT AS"],
            "feideYearOfBirth" => ["1980"],
            "idp" => "https://idp-test.feide.no",
            "authSource" => "default-sp",
            "o" => ["UNINETT AS"]
        ];
        $attributeMapper = new AttributeMapper();
        $account = $attributeMapper->getAccount($attributes);

        $org = $account->getOrg();

        $this->assertEquals($org, 'UNINETT AS', 'Organization is UNINETT');


        $ae = new AuthorizationEvaluator($storage, $this->client, $request, $this->user);


        # $client, $request, $account, $user, $redirect_uri, $scopesInQuestion, $ae, $organization) {
        $aui = new AuthorizationUI($this->client, $request, $account, $this->user, $ae);

        $data = $aui
            // ->setDisallowBypass(true)
            ->process();


        $this->assertEquals($data['firsttime'], true, 'Evaluated to be first time');
        $this->assertEquals($data['needsAuthorization'], true, 'Evaluated to need Authorization');
        $this->assertEquals($data['client']['host'], 'example.org', 'Client host provided');
        $this->assertEquals($data['rememberme'], false, 'Rememberme is false');

        $this->assertEquals($data['simpleView'], false, 'simpleView is false');
        $this->assertEquals($data['validated'], false, 'validated is false');

        $this->assertEquals(strpos($data['bodyclass'], 'bypass') !== false, false, 'bodyclass does not contain bypass');

        $data2 = $aui
            ->setFixedBypass(true)
            ->process();

        $this->assertEquals(strpos($data2['bodyclass'], 'bypass') !== false, true, 'bodyclass does  contain bypass');

        // print_r($data2);


    }



}
