<?php
namespace tests;
use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Config;

class OAuthPasswordTest extends DBHelper {

	protected $client;

	function __construct() {
		parent::__construct();
		$this->_SERVER = $_SERVER;
	}

	public function setUp() {
		$_SERVER = $this->_SERVER;
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['REQUEST_URI'] = '/foo';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['SERVER_PROTOCOL'] = 'https';
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';

		$_REQUEST = array();
		$_REQUEST['grant_type'] = 'password';
		$users = Config::getValue('testUsers');
		foreach ($users AS $userid => $data) {
			$userid_sec = $userid;
			$_REQUEST['username'] = $userid;
			$_REQUEST['password'] = $data['password'];
		}

		$this->client = $this->client();
		$_SERVER['PHP_AUTH_USER'] = $this->client->id;
		$_SERVER['PHP_AUTH_PW'] = $this->client->client_secret;

		$this->user = $this->user($userid_sec);
	}

	public function testGetToken() {
		$router = new Router();

		$response = $router->dispatchCustom('GET', '/oauth/token');

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

		$response = $router->dispatchCustom('GET', '/oauth/token');

		$this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

		$data = $response->getData();
		$this->assertEquals($response->getStatus(), 401);

	}

	public function testWrongClientid() {
		$router = new Router();
		$_SERVER['PHP_AUTH_USER'] = 'wrong';

		$response = $router->dispatchCustom('GET', '/oauth/token');

		$this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

		$data = $response->getData();
		$this->assertEquals($response->getStatus(), 401);

	}

	public function testWrongUserPW() {
		$router = new Router();
		$_REQUEST['password'] = 'wrong';

		$response = $router->dispatchCustom('GET', '/oauth/token');

		$this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

		$data = $response->getData();
		$this->assertEquals($response->getStatus(), 400);

	}

	public function testWrongUsername() {
		$router = new Router();
		$_REQUEST['username'] = 'wrong';

		$response = $router->dispatchCustom('GET', '/oauth/token');

		$this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

		$data = $response->getData();
		$this->assertEquals($response->getStatus(), 400);

	}

	function tearDown() {

		$this->db->removeClient($this->client);

	}
}
