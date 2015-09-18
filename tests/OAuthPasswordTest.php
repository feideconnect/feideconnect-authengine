<?php
namespace tests;
use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Models;
use FeideConnect\Config;

class OAuthPasswordTest extends \PHPUnit_Framework_TestCase {

	protected $db, $client;

	function __construct() {
		$this->db = StorageProvider::getStorage();
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


		$clientid = Models\Client::genUUID();

		$client = new Models\Client($this->db);
		$client->id = $clientid;
		$client->client_secret = Models\Client::genUUID();
		$client->created = new \FeideConnect\Data\Types\Timestamp();
		$client->name = 'name';
		$client->descr = 'descr';
		$client->owner = null;
		$client->redirect_uri = ['http://example.org'];
		$client->scopes = ['userinfo', 'groups'];
		$client->client_secret = Models\Client::genUUID();

		$this->client = $client;
		$_SERVER['PHP_AUTH_USER'] = $this->client->id;
		$_SERVER['PHP_AUTH_PW'] = $this->client->client_secret;

		$this->db->saveClient($client);

		$user = $this->db->getUserByUserIDsec($userid_sec);
		while ($user !== null) {
			$this->db->deleteUser($user);
			$user = $this->db->getUserByUserIDsec($userid_sec);
		}
		$userid = Models\User::genUUID();
		$user = new Models\User($this->db);
		$user->userid = $userid;
		$user->userid_sec = array($userid_sec);
		$user->selectedsource = 'feide:feide.no';

		$this->db->saveUser($user);
		$this->user = $user;
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
