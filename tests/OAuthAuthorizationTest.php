<?php
namespace tests;

use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\Authentication\AuthSource;

class OAuthAuthorizationTest extends DBHelper {

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

    protected function doRun() {
        $request = new Messages\AuthorizationRequest($_REQUEST);
        $auth = new OAuthAuthorization($request);
        return $auth->process();
    }

    public function testAuthorizationToAccountChooser() {
        $this->setExpectedExceptionRegExp(
            'FeideConnect\Exceptions\RedirectException',
            '/^http:\/\/localhost\/accountchooser\?/'
        );
        $this->doRun();
    }


    public function testAuthorizationToConsent() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $response = $this->doRun();
        $this->assertInstanceOf('FeideConnect\HTTP\LocalizedTemplatedHTMLResponse', $response, 'Expected /oauth/authorization endpoint to return html');

        $data = $response->getData();
        $this->assertArrayHasKey('posturl', $data);
        $this->assertEquals($data['posturl'], 'http://localhost/oauth/authorization');
        $this->assertArrayHasKey('needsAuthorization', $data);
        $this->assertEquals($data['needsAuthorization'], true);
    }

    /*
     * When a user is requested to authenticate as X, and is already authenticated as Y, display a user dialog.
     * First check that we can bypass the dialog by setting strict=1
     */
    public function testAuthorizationUnexpectedUserStrict() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"another.org", "userids": ["feide:testuser@example.org"]}';
        $_REQUEST['strict'] = '1';

        $response = $this->doRun();
        $this->assertInstanceOf('FeideConnect\HTTP\LocalizedTemplatedHTMLResponse', $response, 'Expected /oauth/authorization endpoint to return html');

        $data = $response->getData();
        $this->assertArrayHasKey('posturl', $data);
        $this->assertEquals($data['posturl'], 'http://localhost/oauth/authorization');
        $this->assertArrayHasKey('needsAuthorization', $data);
        $this->assertEquals($data['needsAuthorization'], true);
    }

    public function testAuthorizationUnexpectedUser() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"another.org", "userids": ["feide:testuser@example.org"]}';

        $response = $this->doRun();
        $this->assertInstanceOf('FeideConnect\HTTP\LocalizedTemplatedHTMLResponse', $response, 'Expected /oauth/authorization endpoint to return html');

        $data = $response->getData();
        $this->assertArrayHasKey('urllogout', $data);
    }

    public function testAuthorizationBadClientID() {
        $_REQUEST['client_id'] = '00000000-0000-0000-0000-000000000000';
        try {
            $this->doRun();
            $this->assertEquals(true, false, "Did not raise exception as expected");
        } catch (\FeideConnect\OAuth\Exceptions\OAuthException $e) {
            $this->assertEquals('invalid_client', $e->code);
        } catch (\Exception $f) {
            $this->assertEquals("", $f);
        }
    }

    public function testBadVerifier() {
        $this->setExpectedException('\Exception');
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = 'ugle';
        $_REQUEST['bruksvilkar'] = 'yes';
        $_REQUEST['redirect_uri'] = 'http://example.org';

        $this->doRun();
    }

    public function testBruksvilkarMissing() {
        $this->setExpectedException('\Exception');
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['redirect_uri'] = 'http://example.org';

        $this->doRun();
    }

    public function testBruksvilkarNotAccepted() {
        $this->setExpectedException('\Exception');
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['bruksvilkar'] = 'no';
        $_REQUEST['redirect_uri'] = 'http://example.org';

        $this->doRun();
    }

    public function testAuthorizationToCode($approved_scopes = 'userinfo groups') {
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

    public function testAuthorizationToToken($type = 'token', $approved_scopes = 'userinfo groups') {
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

    public function testAuthorizationNotAllScopesAuthorized() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['bruksvilkar'] = 'yes';
        $_REQUEST['redirect_uri'] = 'http://example.org';
        $_REQUEST['approved_scopes'] = 'userinfo';

        $response = $this->doRun();
        $this->assertInstanceOf('FeideConnect\HTTP\LocalizedTemplatedHTMLResponse', $response, 'Expected /oauth/authorization endpoint to return html');

        $data = $response->getData();
        $this->assertArrayHasKey('posturl', $data);
        $this->assertEquals($data['posturl'], 'http://localhost/oauth/authorization');
        $this->assertArrayHasKey('needsAuthorization', $data);
        $this->assertEquals($data['needsAuthorization'], true);
    }

}
