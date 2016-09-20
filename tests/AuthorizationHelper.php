<?php
namespace tests;

use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\Authentication\AuthSource;
use FeideConnect\Utils\Misc;

class AuthorizationHelper extends DBHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
        $this->client = $this->client();
        $this->user = $this->user();
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';
        $_REQUEST['response_type'] = 'code';
        $_REQUEST['client_id'] = $this->client->id;
        AuthSource::setFactory(['\tests\MockAuthSource', 'create']);
    }

    public function codeFlowHelper($approved_scopes = 'userinfo groups') {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['bruksvilkar'] = 'yes';
        $_REQUEST['redirect_uri'] = 'http://example.org';
        $_REQUEST['approved_scopes'] = $approved_scopes;

        $response = $this->doRun();
        $this->assertInstanceOf('FeideConnect\HTTP\Redirect', $response, 'Expected /oauth/authorization endpoint to redirect');

//        var_export($response);
        $url = $response->getURL();
        $this->assertEquals("http", parse_url($url, PHP_URL_SCHEME));
        $this->assertEquals("example.org", parse_url($url, PHP_URL_HOST));
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        $this->assertArrayHasKey('code', $params);
        return $params;
    }

    public function tokenFlowHelper($type = 'token', $approved_scopes = 'userinfo groups') {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['bruksvilkar'] = 'yes';
        $_REQUEST['redirect_uri'] = 'http://example.org';
        $_REQUEST['state'] = '12354';
        $_REQUEST['response_type'] = $type;
        $_REQUEST['approved_scopes'] = $approved_scopes;

        $response = $this->doRun();
        $this->assertInstanceOf('FeideConnect\HTTP\Redirect', $response, 'Expected /oauth/authorization endpoint to redirect');

//        var_export($response);
        $url = $response->getURL();
        $this->assertEquals("http", parse_url($url, PHP_URL_SCHEME));
        $this->assertEquals("example.org", parse_url($url, PHP_URL_HOST));
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        parse_str($fragment, $params);
        $this->assertArrayHasKey('access_token', $params);
        $this->assertArrayHasKey('token_type', $params);
        $this->assertArrayHasKey('expires_in', $params);
        $this->assertArrayHasKey('scope', $params);
        $this->assertArrayHasKey('state', $params);
        $this->assertEquals($params['state'], '12354');
        $this->assertEquals($params['token_type'], 'Bearer');
        return $params;
    }
}
