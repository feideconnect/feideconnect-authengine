<?php
namespace tests;

use FeideConnect\OpenIDConnect\Messages;
use FeideConnect\OpenIDConnect\Protocol\OICAuthorization;
use FeideConnect\Authentication\AuthSource;

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function JWT_decode($data) {
    $elements = explode('.', $data);
    return json_decode(base64url_decode($elements[1]), true);
}

class OICAuthorizationTest extends OAuthAuthorizationTest {

    public function setUp() {
        parent::setUp();
        $this->client->scopes[] = 'openid';
        $this->db->saveClient($this->client);
        AuthSource::setFactory(['\tests\MockAuthSource', 'create']);
        $_REQUEST['scopes'] = 'openid';
        $_REQUEST['redirect_uri'] = $this->client->redirect_uri[0];
        $_REQUEST['approved_scopes'] = 'openid userinfo groups';
    }

    protected function doRun() {
        $request = new Messages\AuthorizationRequest($_REQUEST);
        $auth = new OICAuthorization($request);
        return $auth->process();
    }

    public function testMissingRedirectUri() {
        $this->setExpectedExceptionRegExp(
            'FeideConnect\OAuth\Exceptions\OAuthException',
            '/redirect_uri/'
        );
        unset($_REQUEST['redirect_uri']);
        $this->doRun();
    }

    public function testAuthorizationToCode() {
        $params = parent::testAuthorizationToCode('openid userinfo groups');
        $code = $this->db->getAuthorizationCode($params['code']);
        $this->assertTrue(isset($code->idtoken));
        $this->assertNotNull($code->idtoken);
        $idtoken = JWT_decode($code->idtoken);
        $this->assertArrayNotHasKey('nonce', $idtoken);
    }

    public function testAuthorizationToCodeWithNonce() {
        $_REQUEST['nonce'] = 'ugle';
        $params = parent::testAuthorizationToCode('openid userinfo groups');
        $code = $this->db->getAuthorizationCode($params['code']);
        $this->assertTrue(isset($code->idtoken));
        $this->assertNotNull($code->idtoken);
        $idtoken = JWT_decode($code->idtoken);
        $this->assertArrayHasKey('nonce', $idtoken);
        $this->assertEquals('ugle', $idtoken['nonce']);
    }

    public function testAuthorizationToToken() {
        $params = parent::testAuthorizationToToken('id_token token', 'openid userinfo groups');
        $this->assertArrayHasKey('id_token', $params);
    }
}
