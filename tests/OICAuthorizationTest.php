<?php
namespace tests;

use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\Authentication\AuthSource;

class OICAuthorizationTest extends OAuthAuthorizationTest {

    function setUp() {
        parent::setUp();
        $this->client->scopes[] = 'openid';
        $this->db->saveClient($this->client);
        AuthSource::setFactory(function($type) { return new MockAuthSource($type); });
    }

    public function testAuthorizationToCode() {
        $params = parent::testAuthorizationToCode();
        $code = $this->db->getAuthorizationCode($params['code']);
        $this->assertTrue(isset($code->idtoken));
        $this->assertNotNull($code->idtoken);
    }

    public function testAuthorizationToToken() {
        $params = parent::testAuthorizationToToken();
        $this->assertArrayHasKey('idtoken', $params);
    }
}