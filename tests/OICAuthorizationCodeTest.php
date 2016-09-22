<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication\AuthSource;

class OICAuthorizationCodeTest extends AuthorizationFlowHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_REQUEST['response_type'] = 'code';
        $_REQUEST['scope'] = 'openid groups';

        $this->client->scopes[] = 'openid';
        $this->db->saveClient($this->client);
    }

    public function testNoEffectiveScopes() {
        $this->client->scopes = ['userinfo', 'groups'];
        $this->db->saveClient($this->client);

        $this->doTestNoEffectiveScopes('openid email', 'openid email');
    }

    public function testTokenBasicAuthOK() {
        $code = $this->getAuthorizationCode(['approved_scopes' => 'openid groups']);

        $_SERVER['PHP_AUTH_USER'] = $this->client->id;
        $_SERVER['PHP_AUTH_PW'] = $this->client->client_secret;
        $_POST['grant_type'] = 'authorization_code';
        $_POST['code'] = $code;

        $router = new Router();
        $response = $router->dispatchCustom('POST', '/oauth/token');

        $data = $this->assertTokenEndpointResponseOK($response);
        $this->assertArrayHasKey('id_token', $data);
    }

    public function testOpenidScopeNotApproved() {
        $this->client->scopes = ['groups'];
        $this->db->saveClient($this->client);
        $response = $this->doAuthorizationRequest();
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response,
                                'Expected /oauth/token endpoint to return json');

        $this->assertEquals(400, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals('invalid_scope', $data['error']);
    }
}
