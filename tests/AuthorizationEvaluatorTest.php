<?php

namespace tests;

use FeideConnect\OAuth\AuthorizationEvaluator;
use FeideConnect\OAuth\Messages\AuthorizationRequest;

class AuthorizationEvaluatorTest extends DBHelper {

    protected $user, $client, $aevaluator;

    public function setUp() {
        $this->user = $this->user();
        $this->client = $this->client();
    }

    private function getRequest($redirect_uri = null) {

        $req = [
            "response_type" => "code",
            "scope" => "userinfo-mail userinfo",
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
}
