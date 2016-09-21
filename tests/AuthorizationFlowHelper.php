<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication\AuthSource;

class AuthorizationFlowHelper extends DBHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';

        $_REQUEST['state'] = '06dad165-7d22-4dcf-bda9-38f4048b9e3d';
        $_POST['redirect_uri'] = 'http://example.org';
        $_REQUEST['redirect_uri'] = 'http://example.org';

        $this->client = $this->client();
        $_REQUEST['client_id'] = $this->client->id;

        $this->user = $this->user();
        AuthSource::setFactory(['\tests\MockAuthSource', 'create']);
    }

    protected function doAuthorizationRequest($overrides=null) {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org", "userids": ["feide:testuser@example.org"]}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['bruksvilkar'] = 'yes';
        $_REQUEST['approved_scopes'] = 'userinfo groups';

        if (!empty($overrides)) {
            foreach ($overrides as $k => $v) {
                $_REQUEST[$k] = $v;
            }
        }
        $router = new Router();

        return $router->dispatchCustom('GET', '/oauth/authorization');
    }

    public function testAuthorizationToAccountChooser() {
        $this->setExpectedExceptionRegExp(
            'FeideConnect\Exceptions\RedirectException',
            '/^http:\/\/localhost\/accountchooser\?/'
        );
        $response = $this->doAuthorizationRequest(['acresponse' => null]);
        var_export($response);
    }

    public function testAuthorizationToConsent() {
        $router = new Router();


        $response = $this->doAuthorizationRequest(['approved_scopes' => null, 'verifier' => null, 'bruksvilkår' => null]);
        $this->assertInstanceOf('FeideConnect\HTTP\LocalizedTemplatedHTMLResponse', $response, 'Expected /oauth/authorization endpoint to return html');

        $data = $response->getData();
        $this->assertArrayHasKey('posturl', $data);
        $this->assertEquals('http://localhost/oauth/authorization', $data['posturl']);
        $this->assertArrayHasKey('needsAuthorization', $data);
        $this->assertTrue($data['needsAuthorization']);
    }

    protected function getAuthorizationCode($overrides=null) {
        $response = $this->doAuthorizationRequest($overrides);
        $this->assertInstanceOf('FeideConnect\HTTP\Redirect', $response, 'Expected /oauth/authorization endpoint to redirect');

//        var_export($response);
        $url = $response->getURL();
        $this->assertEquals("http", parse_url($url, PHP_URL_SCHEME));
        $this->assertEquals("example.org", parse_url($url, PHP_URL_HOST));
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        $this->assertArrayHasKey('state', $params);
        $this->assertEquals($_REQUEST['state'], $params['state']);
        $this->assertArrayHasKey('code', $params);
        return $params['code'];

    }

    public function doTestNoEffectiveScopes($scope, $approved_scopes) {

        $overrides = [
            'approved_scopes' => $approved_scopes,
            'scope' => $scope,
        ];

        $response = $this->doAuthorizationRequest($overrides);

        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $this->assertEquals(400, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals('invalid_scope', $data['error']);
    }

    protected function assertTokenEndpointResponseOK($response) {
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $data = $response->getData();
        $this->assertEquals(200, $response->getStatus());

        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('token_type', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertArrayHasKey('scope', $data);
        $this->assertEquals('Bearer', $data['token_type']);
        return $data;
    }

    protected function assertTokenAccessDenied($response) {
        $this->assertInstanceOf('FeideConnect\HTTP\JSONResponse', $response, 'Expected /oauth/token endpoint to return json');

        $data = $response->getData();
        $this->assertEquals(401, $response->getStatus());

    }

    protected function assertTokenResponseOK($response) {
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
        return $params;
    }

    public function tearDown() {
        parent::tearDown();
        $this->db->removeClient($this->client);
    }
}
