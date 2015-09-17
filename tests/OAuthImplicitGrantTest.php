<?php
namespace tests;
require_once(__DIR__ . '/ssp_mock_helper.php');
use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Models;

putenv("AEENV=test");
if (getenv('AEENV') !== "test") {
	throw new \Exception("Not able to set environmentvariable for test environment.");
}

define('TESTUSER', 'testuser@example.org');
define('TESTUSER_SEC', 'feide:' . TESTUSER);

class OAuthImplicitGrantTest extends \PHPUnit_Framework_TestCase {

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
		$_REQUEST['response_type'] = 'token';
		$_REQUEST['state'] = '06dad165-7d22-4dcf-bda9-38f4048b9e3d';
		$_REQUEST['redirect_uri'] = 'http://example.org';


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
		$_REQUEST['client_id'] = $this->client->id;

		$this->db->saveClient($client);

		$user = $this->db->getUserByUserIDsec(TESTUSER_SEC);
		while ($user !== null) {
			$this->db->deleteUser($user);
			$user = $this->db->getUserByUserIDsec(TESTUSER_SEC);
		}
		$userid = Models\User::genUUID();
		$user = new Models\User($this->db);
		$user->userid = $userid;
		$user->userid_sec = array(TESTUSER_SEC);
		$user->selectedsource = 'feide:example.org';

		$this->db->saveUser($user);
		$this->user = $user;
	}

    public function testAuthorizationToAccountChooser() {
		$this->setExpectedExceptionRegExp(
		    'FeideConnect\Exceptions\RedirectException', '/^http:\/\/localhost\/accountchooser\?/'
		);
		$router = new Router();

		$response = $router->dispatchCustom('GET', '/oauth/authorization');

    }

    public function testAuthorizationToConsent() {
		$router = new Router();

		$_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

		$response = $router->dispatchCustom('GET', '/oauth/authorization');
		$this->assertInstanceOf('FeideConnect\HTTP\LocalizedTemplatedHTMLResponse', $response, 'Expected /oauth/authorization endpoint to return html');

		$data = $response->getData();
		$this->assertArrayHasKey('posturl', $data);
		$this->assertEquals($data['posturl'], 'http://localhost/oauth/authorization');
		$this->assertArrayHasKey('needsAuthorization', $data);
		$this->assertEquals($data['needsAuthorization'], true);
    }

    public function testAuthorizationToToken() {
		$router = new Router();

		$_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
		$_REQUEST['verifier'] = $this->user->getVerifier();
		$_REQUEST['bruksvilkar'] = 'yes';

		$response = $router->dispatchCustom('GET', '/oauth/authorization');
		$this->assertInstanceOf('FeideConnect\HTTP\Redirect', $response, 'Expected /oauth/authorization endpoint to redirect');

		$url = $response->getURL();
		$this->assertEquals(parse_url($url, PHP_URL_SCHEME), "http");
		$this->assertEquals(parse_url($url, PHP_URL_HOST), "example.org");
		$fragment = parse_url($url, PHP_URL_FRAGMENT);
		parse_str($fragment, $params);
		$this->assertArrayHasKey('access_token', $params);
		$this->assertArrayHasKey('token_type', $params);
		$this->assertArrayHasKey('expires_in', $params);
		$this->assertArrayHasKey('scope', $params);
		$this->assertArrayHasKey('state', $params);
		$this->assertEquals($params['state'], $_REQUEST['state']);
		$this->assertEquals($params['token_type'], 'Bearer');
    }

    function tearDown() {

    	$this->db->removeClient($this->client);

    }
}
