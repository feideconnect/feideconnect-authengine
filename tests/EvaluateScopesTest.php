<?php

namespace tests;

use FeideConnect\OAuth\OAuthUtils;
use FeideConnect\Data\StorageProvider;

class EvaluateScopesTest extends \PHPUnit_Framework_TestCase {

	protected $db, $dbhelper;
	protected $user, $client, $apigk;

	function __construct() {

		// $config = json_decode(file_get_contents(__DIR__ . '/../etc/ci/config.json'), true);
		$this->db = StorageProvider::getStorage();
		$this->dbhelper = new DBHelper();
	}

	function setUp() {
		$this->user = $this->dbhelper->user();
		$this->client = $this->dbhelper->client();
		$this->apigk = $this->dbhelper->apigk();
	}

	function testNoRequestedScopes() {
		$scopes = ['userinfo', 'groups'];
		$this->client->scopes = $scopes;
		$this->assertEquals($scopes, OAuthUtils::evaluateScopes($this->client, $this->user, []));
	}

	function testAllRequestedScopesOK() {
		$scopes = ['userinfo', 'groups'];
		$this->client->scopes = $scopes;
		$this->assertEquals($scopes, OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
	}

	function testNoRequestedScopesOK() {
		$scopes = ['userinfo', 'groups'];
		$this->client->scopes = $scopes;
		$this->assertEquals([], OAuthUtils::evaluateScopes($this->client, $this->user, ['longterm']));
	}

	function testSomeRequestedScopesOK() {
		$scopes = ['userinfo', 'groups'];
		$this->client->scopes = $scopes;
		$this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, $this->user, ['longterm', 'userinfo']));
	}

	function testOrgAdminScopesNotOK() {
		$scopes = ['gk_test_moderated', 'userinfo'];
		$this->client->scopes = $scopes;
		$this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
	}

	function testOrgAdminScopesOK() {
		$scopes = ['gk_test_moderated', 'userinfo'];
		$this->client->scopes = $scopes;
		$this->client->orgauthorizations['example.org'] = json_encode(['gk_test_moderated']);
		$this->assertEquals($scopes, OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
	}

	function testOrgAdminScopesNoUser() {
		$scopes = ['gk_test_moderated', 'userinfo'];
		$this->client->scopes = $scopes;
		$this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, null, $scopes));
	}

	function testOrgAdminScopesNotFeideUser() {
		$scopes = ['gk_test_moderated', 'userinfo'];
		$this->client->scopes = $scopes;
		$this->user->userid_sec = ['p:123'];
		$this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
	}

}
