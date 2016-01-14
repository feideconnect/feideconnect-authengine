<?php
namespace tests;

use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication\AuthSource;

class OAuthImplicitGrantTest extends DBHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';

        $_REQUEST['response_type'] = 'token';
        $_REQUEST['state'] = '06dad165-7d22-4dcf-bda9-38f4048b9e3d';
        $_REQUEST['redirect_uri'] = 'http://example.org';

        $this->client = $this->client();
        $_REQUEST['client_id'] = $this->client->id;

        $this->user = $this->user();

        AuthSource::setFactory(['\tests\MockAuthSource', 'create']);
    }

    public function testAuthorizationToAccountChooser() {
        $this->setExpectedExceptionRegExp(
            'FeideConnect\Exceptions\RedirectException',
            '/^http:\/\/localhost\/accountchooser\?/'
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
        $_REQUEST['approved_scopes'] = 'userinfo groups';

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

    public function testAuthorizationToTokenSubtoken() {
        $apigk = $this->apigk();
        $this->client->scopes = ['groups', 'gk_test'];
        $this->db->saveClient($this->client);

        $router = new Router();

        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['bruksvilkar'] = 'yes';
        $_REQUEST['approved_scopes'] = 'gk_test groups';
        $_REQUEST['gk_approved_scopes_test'] = 'userid name email userid-feide';

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
        $this->assertArrayNotHasKey('subtokens', $params);
        $token = $this->db->getAccessToken($params['access_token']);
        $this->assertEquals(1, count($token->subtokens));
        $this->assertArrayHasKey('test', $token->subtokens);
        $subtoken = $this->db->getAccessToken($token->subtokens['test']);
        $this->assertEquals(null, $subtoken->subtokens);
        $this->assertEquals('test', $subtoken->apigkid);
        $this->assertTrue($subtoken->hasExactScopes(['userid', 'name', 'email', 'userid-feide']));
    }

    public function tearDown() {

        parent::tearDown();
        $this->db->removeClient($this->client);

    }
}
