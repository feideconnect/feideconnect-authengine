<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication\AuthSource;

class AuthorizationEndpointErrorsTest extends AuthorizationFlowHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_REQUEST['response_type'] = 'code';
    }

    public function testBadRedirectURI() {
        $response = $this->doAuthorizationRequest(['redirect_uri' => 'http://evilhackers.com']);
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('invalid_request', $response->getData()['error']);
    }
    
    public function testBadClientID() {
        $response = $this->doAuthorizationRequest(['client_id' => 'Ugle!']);
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('invalid_request', $response->getData()['error']);
    }

    public function testUnknownClientID() {
        $response = $this->doAuthorizationRequest(['client_id' => '7ab5f7a2-0a20-42d6-9dcb-6f48e38c9f4e']);
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('invalid_request', $response->getData()['error']);
    }

    public function testOAuthUnsupportedResponseType() {
        $response = $this->doAuthorizationRequest(['response_type' => 'id_token token']);
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('invalid_request', $response->getData()['error']);
    }

    public function testOICUnsupportedResponseType() {
        $response = $this->doAuthorizationRequest(['scope' => 'openid', 'response_type' => 'token']);
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('invalid_request', $response->getData()['error']);
    }

}
