<?php
namespace tests;

use FeideConnect\OAuth\APIProtector;
use FeideConnect\OAuth\AccessTokenPool;


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
            '/not present in request/');
        $apiprotector = new APIProtector([]);
        $apiprotector->requireToken();
    }

    public function testMalformedToken1() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/invalid format/');
        $apiprotector = new APIProtector(['Authorization' => 'Bearer øgle']);
        $apiprotector->requireToken();
    }

    public function testMalformedToken2() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/invalid format/');
        $apiprotector = new APIProtector(['Authorization' => 'Bearer f6773b2a-94af-4019-a54b-150a50c5ff']);
        $apiprotector->requireToken();
    }

    public function testNonExistingToken() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/not valid/');
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ac868d52-139e-43c1-975a-075e8dc93746']);
        $apiprotector->requireToken();
    }

    public function testExpiredToken() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/expired/');
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['userinfo'], 100);
        $token->validuntil->addSeconds(-1000);
        $this->db->rawSaveToken($token);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $apiprotector->requireToken();
        
    }

    public function testRequireTokenOK() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['userinfo'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $this->assertEquals($apiprotector, $apiprotector->requireToken());
    }

    public function testGetScopes() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['userinfo'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $this->assertEquals(['userinfo'], $apiprotector->getScopes());
    }

    public function testRequireScopesOK() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['scope1', 'scope2', 'scope3'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $this->assertEquals($apiprotector, $apiprotector->requireScopes(['scope1', 'scope3']));
    }

    public function testRequireScopesFail() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/does not have sufficient scope/');
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['scope1', 'scope2', 'scope3'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $this->assertEquals($apiprotector, $apiprotector->requireScopes(['scope1', 'scope4']));
    }

    public function testRequireUserOK() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['userinfo'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $this->assertEquals($apiprotector, $apiprotector->requireUser());
    }

    public function testRequireUserUserlessToken() {
        $this->setExpectedExceptionRegExp(
            '\FeideConnect\OAuth\Exceptions\APIAuthorizationException',
            '/not associated with an authenticated user/');
        $pool = new AccessTokenPool($this->client, null);
        $token = $pool->getToken(['userinfo'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $apiprotector->requireUser();
    }

    public function testGetUser() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['userinfo'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $user = $apiprotector->getUser();
        $this->assertEquals($this->user->getBasicUserInfo(), $user->getBasicUserInfo());
    }

    public function testGetUserNoUser() {
        $pool = new AccessTokenPool($this->client, null);
        $token = $pool->getToken(['userinfo'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $this->assertNull($apiprotector->getUser());
    }

    public function testGetClient() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $token = $pool->getToken(['userinfo'], 100);
        $apiprotector = new APIProtector(['Authorization' => 'Bearer ' . $token->access_token]);
        $client = $apiprotector->getClient();
        $this->assertEquals($this->client->id, $client->id);
    }
}
