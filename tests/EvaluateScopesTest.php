<?php

namespace tests;

use FeideConnect\OAuth\OAuthUtils;
use FeideConnect\Data\StorageProvider;

class EvaluateScopesTest extends DBHelper {

	protected $user, $client, $apigk;

	function setUp() {
		$this->user = $this->user();
		$this->client = $this->client();
		$this->apigk = $this->apigk();
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
		$this->client->orgauthorization['example.org'] = ['gk_test_moderated'];
		$this->db->saveClient($this->client);
		$this->client = $this->db->getClient($this->client->id);
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
