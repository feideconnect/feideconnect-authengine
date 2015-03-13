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



  //   	echo "oooooo";
		// echo("........==> " . Config::getValue('test.foo.lo')); 

		// $this->assertTrue(Config::getValue('test.bar', true) === true, 'Config picking not existing prop should return default');
		// $this->assertTrue(Config::getValue('test.foo.lo') === 3, 'Config read test.foo.lo === 3');

		// $this->assertTrue(Config::getValue('test.foo.li', 3) === 3, 'Config read fall back to default param');

		// $this->assertTrue(Config::getValue('test.foo.li', null, true) === 3, 'should throw exceptoin');


    }


}