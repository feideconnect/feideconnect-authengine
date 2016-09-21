<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication\AuthSource;

class OAuthAuthorizationCodeTest extends AuthorizationFlowHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_REQUEST['response_type'] = 'code';
    }

    public function testTokenBasicAuthOK() {
        $router = new Router();
        $code = $this->getAuthorizationCode();

        $_SERVER['PHP_AUTH_USER'] = $this->client->id;
        $_SERVER['PHP_AUTH_PW'] = $this->client->client_secret;
        $_POST['grant_type'] = 'authorization_code';
        $_POST['code'] = $code;
        $response = $router->dispatchCustom('POST', '/oauth/token');
        $this->assertTokenEndpointResponseOK($response);
    }

    public function testTokenBasicAuthWrongClientId() {
        $router = new Router();
        $code = $this->getAuthorizationCode();

        $_SERVER['PHP_AUTH_USER'] = "wrong";
        $_SERVER['PHP_AUTH_PW'] = $this->client->client_secret;
        $_POST['grant_type'] = 'authorization_code';
        $_POST['code'] = $code;
        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertTokenAccessDenied($response);
    }

    public function testTokenBasicAuthWrongClientSecret() {
        $router = new Router();
        $code = $this->getAuthorizationCode();

        $_SERVER['PHP_AUTH_USER'] = $this->client->id;
        $_SERVER['PHP_AUTH_PW'] = "wrong";
        $_POST['grant_type'] = 'authorization_code';
        $_POST['code'] = $code;
        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertTokenAccessDenied($response);
    }

    public function testTokenPOSTOK() {
        $router = new Router();
        $code = $this->getAuthorizationCode();

        $_POST['client_id'] = $this->client->id;
        $_POST['client_secret'] = $this->client->client_secret;
        $_POST['grant_type'] = 'authorization_code';
        $_POST['code'] = $code;
        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertTokenEndpointResponseOK($response);
    }

    public function testTokenPOSTWrongClientId() {
        $router = new Router();
        $code = $this->getAuthorizationCode();

        $_POST['client_id'] = "wrong";
        $_POST['client_secret'] = $this->client->client_secret;
        $_POST['grant_type'] = 'authorization_code';
        $_POST['code'] = $code;
        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertTokenAccessDenied($response);
    }

    public function testTokenPOSTWrongClientSecret() {
        $router = new Router();
        $code = $this->getAuthorizationCode();

        $_POST['client_id'] = $this->client->id;
        $_POST['client_secret'] = "wrong";
        $_POST['grant_type'] = 'authorization_code';
        $_POST['code'] = $code;
        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertTokenAccessDenied($response);
    }

}
