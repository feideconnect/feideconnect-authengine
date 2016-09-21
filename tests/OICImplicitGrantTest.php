<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication\AuthSource;

class OICImplicitGrantTest extends AuthorizationFlowHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_REQUEST['response_type'] = 'id_token token';
        $_REQUEST['scope'] = 'openid groups';

        $this->client->scopes[] = 'openid';
        $this->db->saveClient($this->client);
    }

    public function testAuthorizationToToken() {
        $response = $this->doAuthorizationRequest(['approved_scopes' => 'openid groups']);
        $params = $this->assertTokenResponseOK($response);
        $this->assertArrayHasKey('id_token', $params);
    }
}
