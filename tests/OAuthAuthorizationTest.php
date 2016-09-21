<?php
namespace tests;

use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\Authentication\AuthSource;
use FeideConnect\Utils\Misc;

class OAuthAuthorizationTest extends AuthorizationHelper {

    protected $client;

    public function setUp() {
        parent::setUp();
    }

    protected function doRun() {
        $request = new Messages\AuthorizationRequest($_REQUEST);
        $auth = new OAuthAuthorization($request, false);
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

    public function testAuthorizationToCode() {
        $this->codeFlowHelper();
    }

    public function testAuthorizationToCodeSubtoken($approved_scopes = 'gk_test groups') {
        $apigk = $this->apigk();
        $this->client->scopes = ['groups', 'gk_test'];
        $this->db->saveClient($this->client);
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';
        $_REQUEST['verifier'] = $this->user->getVerifier();
        $_REQUEST['bruksvilkar'] = 'yes';
        $_REQUEST['redirect_uri'] = 'http://example.org';
        $_REQUEST['approved_scopes'] = $approved_scopes;
        $_REQUEST['gk_approved_scopes_test'] = 'userid name email userid-feide';

        $response = $this->doRun();
        $this->assertInstanceOf('FeideConnect\HTTP\Redirect', $response, 'Expected /oauth/authorization endpoint to redirect');

//        var_export($response);
        $url = $response->getURL();
        $this->assertEquals("http", parse_url($url, PHP_URL_SCHEME));
        $this->assertEquals("example.org", parse_url($url, PHP_URL_HOST));
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        $this->assertArrayHasKey('code', $params);
        $code = $this->db->getAuthorizationCode($params['code']);
        $this->assertEquals(1, count($code->apigk_scopes));
        $this->assertArrayHasKey('test', $code->apigk_scopes);
        $this->assertTrue(Misc::containsSameElements(['userid', 'name', 'email', 'userid-feide'], $code->apigk_scopes['test']));
        return $params;
    }

    public function testAuthorizationToToken() {
        $this->tokenFlowHelper();
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
