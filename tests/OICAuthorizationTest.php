<?php
namespace tests;
require_once(__DIR__ . '/ssp_mock_helper.php');
require_once(__DIR__ . '/OAuthAuthorizationTest.php');

use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;

class OICAuthorizationTest extends OAuthAuthorizationTest {

	function setUp() {
		parent::setUp();
		$this->client->scopes[] = 'openid';
		$this->db->saveClient($this->client);
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
