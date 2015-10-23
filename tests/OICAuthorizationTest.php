<?php
namespace tests;

use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\Authentication\AuthSource;

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function JWT_decode($data) {
    $elements = explode('.', $data);
    return json_decode(base64url_decode($elements[1]), True);
}

class OICAuthorizationTest extends OAuthAuthorizationTest {

    public function setUp() {
        parent::setUp();
        $this->client->scopes[] = 'openid';
        $this->db->saveClient($this->client);
        AuthSource::setFactory(['\tests\MockAuthSource', 'create']);
    }

    public function testAuthorizationToCode() {
        $params = parent::testAuthorizationToCode();
        $code = $this->db->getAuthorizationCode($params['code']);
        $this->assertTrue(isset($code->idtoken));
        $this->assertNotNull($code->idtoken);
        $idtoken = JWT_decode($code->idtoken);
        $this->assertArrayNotHasKey('nonce', $idtoken);
    }

    public function testAuthorizationToCodeWithNonce() {
        $_REQUEST['nonce'] = 'ugle';
        $params = parent::testAuthorizationToCode();
        $code = $this->db->getAuthorizationCode($params['code']);
        $this->assertTrue(isset($code->idtoken));
        $this->assertNotNull($code->idtoken);
        $idtoken = JWT_decode($code->idtoken);
        $this->assertArrayHasKey('nonce', $idtoken);
        $this->assertEquals('ugle', $idtoken['nonce']);
    }

    public function testAuthorizationToToken() {
        $params = parent::testAuthorizationToToken();
        $this->assertArrayHasKey('id_token', $params);
    }
}
