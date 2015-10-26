<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Config;

class OAuthPasswordTest extends DBHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';

        $_POST['grant_type'] = 'password';
        $users = Config::getValue('testUsers');
        foreach ($users as $userid => $data) {
            $userid_sec = $userid;
            $_POST['username'] = $userid;
            $_POST['password'] = $data['password'];
        }

        $this->client = $this->client();
        $_SERVER['PHP_AUTH_USER'] = $this->client->id;
        $_SERVER['PHP_AUTH_PW'] = $this->client->client_secret;

        $this->user = $this->user($userid_sec);
    }

    public function testGetToken() {
        $router = new Router();

        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $data = $response->getData();
        $this->assertEquals($response->getStatus(), 200);

        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('token_type', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertArrayHasKey('scope', $data);
        $this->assertEquals($data['token_type'], 'Bearer');
    }

    public function testWrongClientPW() {
        $router = new Router();
        $_SERVER['PHP_AUTH_PW'] = 'wrong';

        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $data = $response->getData();
        $this->assertEquals($response->getStatus(), 401);

    }

    public function testWrongClientid() {
        $router = new Router();
        $_SERVER['PHP_AUTH_USER'] = 'wrong';

        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $data = $response->getData();
        $this->assertEquals($response->getStatus(), 401);

    }

    public function testWrongUserPW() {
        $router = new Router();
        $_POST['password'] = 'wrong';

        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $data = $response->getData();
        $this->assertEquals($response->getStatus(), 400);

    }

    public function testWrongUsername() {
        $router = new Router();
        $_POST['username'] = 'wrong';

        $response = $router->dispatchCustom('POST', '/oauth/token');

        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $data = $response->getData();
        $this->assertEquals($response->getStatus(), 400);

    }

    public function tearDown() {

        parent::tearDown();
        $this->db->removeClient($this->client);

    }
}
