<?php

namespace tests;

use FeideConnect\OAuth\OAuthUtils;

class EvaluateScopesTest extends DBHelper {

    protected $user, $client, $apigk;

    public function setUp() {
        $this->user = $this->user();
        $this->client = $this->client();
        $this->apigk = $this->apigk();
    }

    public function testNoRequestedScopes() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $this->assertEquals($scopes, OAuthUtils::evaluateScopes($this->client, $this->user, []));
    }

    public function testAllRequestedScopesOK() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $this->assertEquals($scopes, OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
    }

    public function testNoRequestedScopesOK() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $this->assertEquals([], OAuthUtils::evaluateScopes($this->client, $this->user, ['longterm']));
    }

    public function testSomeRequestedScopesOK() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, $this->user, ['longterm', 'userinfo']));
    }

    public function testOrgAdminScopesNotOK() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
    }

    public function testOrgAdminScopesOK() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $this->client->orgauthorization['example.org'] = ['gk_test_moderated'];
        $this->db->saveClient($this->client);
        $this->client = $this->db->getClient($this->client->id);
        $this->assertEquals($scopes, OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
    }

    public function testOrgAdminScopesNoUser() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, null, $scopes));
    }

    public function testOrgAdminScopesNotFeideUser() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $this->user->userid_sec = ['p:123'];
        $this->assertEquals(['userinfo'], OAuthUtils::evaluateScopes($this->client, $this->user, $scopes));
    }

}
