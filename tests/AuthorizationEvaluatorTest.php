<?php

namespace tests;

use FeideConnect\OAuth\AuthorizationEvaluator;
use FeideConnect\OAuth\Messages\AuthorizationRequest;

class AuthorizationEvaluatorTest extends DBHelper {

    protected $user, $client, $aevaluator;

    public function setUp() {
        $this->user = $this->user();
        $this->client = $this->client();
        $this->apigk = $this->apigk();
    }

    private function getRequest($redirect_uri = null, $scopes=["userinfo-mail", "userinfo"]) {

        $req = [
            "response_type" => "code",
            "scope" => join(" ", $scopes),
            "client_id" => "f1343f3a-79cc-424f-9233-5fe33f8bbd56"
        ];
        if ($redirect_uri !== null) {
            $req['redirect_uri'] = $redirect_uri;
        }
        return new AuthorizationRequest($req);
    }

    public function testAuthorizationEvaluatorNoURLinRequest() {


        $request = $this->getRequest();
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request);
        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();
        $this->assertEquals($redirect_uri, 'http://example.org', 'When request does not contain any redirect_uri, return first preconfigured one.');

    }

    public function testAuthorizationEvaluatorCorrectURLinRequest() {


        $request = $this->getRequest('http://example.org');
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request);
        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();
        $this->assertEquals($request->redirect_uri, 'http://example.org', 'Request contains redirect_uri');
        $this->assertEquals($redirect_uri, 'http://example.org', 'When request does contain correct rediret_uri it should be accepted. ');

    }


    public function testAuthorizationEvaluatorBadURLinRequest() {

        $this->setExpectedException('FeideConnect\OAuth\Exceptions\OAuthException');
        $request = $this->getRequest('http://bad.example.org');
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request);
        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();

    }

    public function testGetEffectiveScopesNoRequestedScopes() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $request = $this->getRequest(null, []);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, $this->user);
        $this->assertEquals($scopes, $this->aevaluator->getEffectiveScopes());
    }

    public function testGetEffectiveScopesAllRequestedScopesOK() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $request = $this->getRequest(null, $scopes);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, $this->user);
        $this->assertEquals($scopes, $this->aevaluator->getEffectiveScopes());
    }

    public function testGetEffectiveScopesNoRequestedScopesOK() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $request = $this->getRequest(null, ['longterm']);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, $this->user);
        $this->assertEquals([], $this->aevaluator->getEffectiveScopes());
    }

    public function testGetEffectiveScopesSomeRequestedScopesOK() {
        $scopes = ['userinfo', 'groups'];
        $this->client->scopes = $scopes;
        $request = $this->getRequest(null, ['longterm', 'userinfo']);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, $this->user);
        $this->assertEquals(['userinfo'], $this->aevaluator->getEffectiveScopes());
    }

    public function testGetEffectiveScopesOrgAdminScopesNotOK() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $request = $this->getRequest(null, $scopes);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, $this->user);
        $this->assertEquals(['userinfo'], $this->aevaluator->getEffectiveScopes());
    }

    public function testGetEffectiveScopesOrgAdminScopesOK() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $this->client->orgauthorization['example.org'] = ['gk_test_moderated'];
        $this->db->saveClient($this->client);
        $this->client = $this->db->getClient($this->client->id);
        $request = $this->getRequest(null, $scopes);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, $this->user);
        $this->assertEquals($scopes, $this->aevaluator->getEffectiveScopes());
    }

    public function testGetEffectiveScopesOrgAdminScopesNoUser() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $request = $this->getRequest(null, $scopes);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, null);
        $this->assertEquals(['userinfo'], $this->aevaluator->getEffectiveScopes());
    }

    public function testGetEffectiveScopesOrgAdminScopesNotFeideUser() {
        $scopes = ['gk_test_moderated', 'userinfo'];
        $this->client->scopes = $scopes;
        $this->user->userid_sec = ['p:123'];
        $request = $this->getRequest(null, $scopes);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request, $this->user);
        $this->assertEquals(['userinfo'], $this->aevaluator->getEffectiveScopes());
    }

}
