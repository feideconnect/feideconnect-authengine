<?php


namespace tests;

use FeideConnect\Config;
use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Models;

class OAuthTest extends \PHPUnit_Framework_TestCase {


	protected $db;

	function __construct() {

		// $config = json_decode(file_get_contents(__DIR__ . '/../etc/ci/config.json'), true);
		$this->db = StorageProvider::getStorage();

		$clientid = Models\Client::genUUID();
	
		$client = new Models\Client($this->db);
		$client->id = $clientid;
		$client->client_secret = Models\Client::genUUID();
		$client->created = time();
		$client->name = 'name';
		$client->descr = 'descr';
		$client->owner = null;
		$client->redirect_uri = ['http://example.org'];
		$client->scopes = ['userinfo', 'groups'];

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

		$router = new Router();
		
		$_REQUEST['response_type'] = 'token';
		$_REQUEST['state'] = '06dad165-7d22-4dcf-bda9-38f4048b9e3d';
		$_REQUEST['redirect_uri'] = 'http://developers.dev.feideconnect.no/index.dev.html';
		$_REQUEST['client_id'] = 'e8160a77-58f8-4006-8ee5-ab64d17a5b1e';

		$response = $router->dispatchCustom('GET', '/oauth/authorization');

		$this->assertInstanceOf('FeideConnect\HTTP\HTTPResponse', $response, 'Expected response to be an httpresponse');

		$data = $response->getData();

		$this->assertArrayHasKey('authorization', $data);
		$this->assertArrayHasKey('token', $data);

    }




}