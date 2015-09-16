<?php


namespace tests;

use FeideConnect\Config;
use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Models;

putenv("AEENV=test");
if (getenv('AEENV') !== "test") { 
	throw new \Exception("Not able to set environmentvariable for test environment."); 
}

class OAuthTest extends \PHPUnit_Framework_TestCase {


	protected $db, $client;

	function __construct() {


		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['REQUEST_URI'] = '/foo';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['SERVER_PROTOCOL'] = 'https';

		// $config = json_decode(file_get_contents(__DIR__ . '/../etc/ci/config.json'), true);
		$this->db = StorageProvider::getStorage();
	}

	public function setUp() {
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

		$this->client = $client;

		$this->db->saveClient($client);
	}

	public function testOAuthConfig() {
		$router = new Router();

		$response = $router->dispatchCustom('GET', '/oauth/config');
		$this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/config endpoint to return json');

		$data = $response->getData();

		$this->assertArrayHasKey('authorization', $data);
		$this->assertArrayHasKey('token', $data);

	}

    public function testAuthorizationRequestImplicitGrant() {
		$this->setExpectedExceptionRegExp(
		    'FeideConnect\Exceptions\RedirectException', '/^http:\/\/localhost\/accountchooser\?/'
		);
		$router = new Router();
		
		$_REQUEST['response_type'] = 'token';
		$_REQUEST['state'] = '06dad165-7d22-4dcf-bda9-38f4048b9e3d';
		$_REQUEST['redirect_uri'] = 'http://example.org';
		$_REQUEST['client_id'] = $this->client->id;

		$response = $router->dispatchCustom('GET', '/oauth/authorization');

    }

    function tearDown() {

    	$this->db->removeClient($this->client);

    }
}
