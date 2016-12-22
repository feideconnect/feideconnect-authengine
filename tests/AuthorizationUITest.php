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
        $this->assertEquals('http://example.org', $redirect_uri, 'When request does not contain any redirect_uri, return first preconfigured one.');

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
            "feideYearOfBirth" => ["1980"],
            "idp" => "https://idp-test.feide.no",
            "authSource" => "default-sp",
        ];
        $attributeMapper = new AttributeMapper();
        $account = $attributeMapper->getAccount($attributes);

        $org = $account->getOrg();

        $this->assertEquals('UNINETT', $org, 'Organization is UNINETT');


        $ae = new AuthorizationEvaluator($storage, $this->client, $request, $this->user);


        # $client, $request, $account, $user, $redirect_uri, $scopesInQuestion, $ae, $organization) {
        $aui = new AuthorizationUI($this->client, $request, $account, $this->user, $ae);

        $data = $aui
            // ->setDisallowBypass(true)
            ->process();


        $this->assertTrue($data['firsttime'], 'Evaluated to be first time');
        $this->assertTrue($data['needsAuthorization'], 'Evaluated to need Authorization');
        $this->assertEquals('example.org', $data['client']['host'], 'Client host provided');
        $this->assertFalse($data['rememberme'], 'Rememberme is false');

        $this->assertFalse($data['simpleView'], 'simpleView is false');
        $this->assertFalse($data['validated'], 'validated is false');

        $this->assertFalse(strpos($data['bodyclass'], 'bypass') !== false, 'bodyclass does not contain bypass');

        $data2 = $aui
            ->setFixedBypass(true)
            ->process();

        $this->assertTrue(strpos($data2['bodyclass'], 'bypass') !== false, 'bodyclass does  contain bypass');

        // print_r($data2);


    }



}
