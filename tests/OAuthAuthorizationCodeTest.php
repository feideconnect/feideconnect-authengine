<?php
namespace tests;
require_once(__DIR__ . '/ssp_mock_helper.php');
use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Data\StorageProvider;

class OAuthAuthorizationCodeTest extends DBHelper {

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
		$_REQUEST['response_type'] = 'code';
		$_REQUEST['state'] = '06dad165-7d22-4dcf-bda9-38f4048b9e3d';
		$_REQUEST['redirect_uri'] = 'http://example.org';



		$this->client = $this->client();
		$_REQUEST['client_id'] = $this->client->id;

		$this->user = $this->user();
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

    public function testAuthorizationToCode() {
		$router = new Router();

		$_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
		$_REQUEST['verifier'] = $this->user->getVerifier();
		$_REQUEST['bruksvilkar'] = 'yes';

		$response = $router->dispatchCustom('GET', '/oauth/authorization');
		$this->assertInstanceOf('FeideConnect\HTTP\Redirect', $response, 'Expected /oauth/authorization endpoint to redirect');

//		var_export($response);
		$url = $response->getURL();
		$this->assertEquals(parse_url($url, PHP_URL_SCHEME), "http");
		$this->assertEquals(parse_url($url, PHP_URL_HOST), "example.org");
		$query = parse_url($url, PHP_URL_QUERY);
		parse_str($query, $params);
		$this->assertArrayHasKey('state', $params);
		$this->assertEquals($params['state'], $_REQUEST['state']);
		$this->assertArrayHasKey('code', $params);

		$_SERVER['PHP_AUTH_USER'] = $this->client->id;
		$_SERVER['PHP_AUTH_PW'] = $this->client->client_secret;
		$_REQUEST['grant_type'] = 'authorization_code';
		$_REQUEST['code'] = $params['code'];
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

    function tearDown() {

    	$this->db->removeClient($this->client);

    }
}
