<?php
namespace tests;

use FeideConnect\OAuth\APIProtector;
use FeideConnect\OAuth\OAuthUtils;


class APIProtectorTest extends DBHelper {
    protected $user, $client;

    public function setUp() {
        parent::setUp();
        $this->user = $this->user();
        $this->client = $this->client();
    }

    public function testNoToken() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/not present in request/'
        );
        $apiprotector = new APIProtector();
        $apiprotector->requireToken();
    }

    public function testMalformedToken1() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/invalid format/'
        );
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer øgle';
        $apiprotector = new APIProtector();
        $apiprotector->requireToken();
    }

    public function testMalformedToken2() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/invalid format/'
        );
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer f6773b2a-94af-4019-a54b-150a50c5ff';
        $apiprotector = new APIProtector();
        $apiprotector->requireToken();
    }

    public function testNonExistingToken() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/not valid/'
        );
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ac868d52-139e-43c1-975a-075e8dc93746';
        $apiprotector = new APIProtector();
        $apiprotector->requireToken();
    }

    public function testExpiredToken() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/expired/'
        );
        $token = OAuthUtils::generateToken($this->client, $this->user, ['userinfo'], null, 100);
        $token->validuntil->addSeconds(-1000);
        $this->db->rawSaveToken($token);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $apiprotector->requireToken();
        
    }

    public function testRequireTokenOK() {
        $token = OAuthUtils::generateToken($this->client, $this->user, ['userinfo'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $this->assertEquals($apiprotector, $apiprotector->requireToken());
    }

    public function testGetScopes() {
        $token = OAuthUtils::generateToken($this->client, $this->user, ['userinfo'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $this->assertEquals(['userinfo'], $apiprotector->getScopes());
    }

    public function testRequireScopesOK() {
        $token = OAuthUtils::generateToken($this->client, $this->user, ['scope1', 'scope2', 'scope3'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $this->assertEquals($apiprotector, $apiprotector->requireScopes(['scope1', 'scope3']));
    }

    public function testRequireScopesFail() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/does not have sufficient scope/'
        );
        $token = OAuthUtils::generateToken($this->client, $this->user, ['scope1', 'scope2', 'scope3'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $this->assertEquals($apiprotector, $apiprotector->requireScopes(['scope1', 'scope4']));
    }

    public function testRequireUserOK() {
        $token = OAuthUtils::generateToken($this->client, $this->user, ['userinfo'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $this->assertEquals($apiprotector, $apiprotector->requireUser());
    }

    public function testRequireUserUserlessToken() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/not associated with an authenticated user/'
        );
        $token = OAuthUtils::generateToken($this->client, null, ['userinfo'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $apiprotector->requireUser();
    }

    public function testGetUser() {
        $token = OAuthUtils::generateToken($this->client, $this->user, ['userinfo'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $user = $apiprotector->getUser();
        $this->assertEquals($this->user->getBasicUserInfo(), $user->getBasicUserInfo());
    }

    public function testGetUserNoUser() {
        $token = OAuthUtils::generateToken($this->client, null, ['userinfo'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $this->assertNull($apiprotector->getUser());
    }

    public function testGetClient() {
        $token = OAuthUtils::generateToken($this->client, $this->user, ['userinfo'], null, 100);
        $_SERVER["HTTP_AUTHORIZATION"] = 'Bearer ' . $token->access_token;
        $apiprotector = new APIProtector();
        $client = $apiprotector->getClient();
        $this->assertEquals($this->client->id, $client->id);
    }
}
