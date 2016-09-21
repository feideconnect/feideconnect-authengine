<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication\AuthSource;

class OAuthImplicitGrantTest extends AuthorizationFlowHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_REQUEST['response_type'] = 'token';
    }

    public function testAuthorizationToToken() {
        $response = $this->doAuthorizationRequest();
        $this->assertTokenResponseOK($response);
    }

    public function testAuthorizationToTokenSubtoken() {
        $apigk = $this->apigk();
        $this->client->scopes = ['groups', 'gk_test'];
        $this->db->saveClient($this->client);

        $overrides = [
            'approved_scopes' => 'gk_test groups',
            'gk_approved_scopes_test' => 'userid name email userid-feide',
        ];

        $response = $this->doAuthorizationRequest($overrides);
        $params = $this->assertTokenResponseOK($response);

        $this->assertArrayNotHasKey('subtokens', $params);
        $token = $this->db->getAccessToken($params['access_token']);
        $this->assertEquals(1, count($token->subtokens));
        $this->assertArrayHasKey('test', $token->subtokens);
        $subtoken = $this->db->getAccessToken($token->subtokens['test']);
        $this->assertEquals(null, $subtoken->subtokens);
        $this->assertEquals('test', $subtoken->apigkid);
        $this->assertTrue($subtoken->hasExactScopes(['userid', 'name', 'email', 'userid-feide']));
    }
}
