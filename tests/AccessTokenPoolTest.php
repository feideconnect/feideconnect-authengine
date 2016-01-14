<?php
namespace tests;

use FeideConnect\OAuth\AccessTokenPool;
use FeideConnect\Data\Models\AccessToken;


class AccessTokenPoolTest extends DBHelper {
    protected $user, $client;

    public function setUp() {
        parent::setUp();
        $this->user = $this->user();
        $this->client = $this->client();
    }

    public function testNoToken() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $this->assertEquals([], $pool->getAllTokens());
    }

    public function testBasicRecycleToken() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $this->assertEquals([], $pool->getAllTokens());
        $a = $pool->getToken(['scope1', 'scope2'], null, 1000);
        $pool = new AccessTokenPool($this->client, $this->user);
        $b = $pool->getToken(['scope1', 'scope2'], null, 1000);
        $this->assertEquals($a->access_token, $b->access_token);
    }

    public function testDontRecycleOld() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $this->assertEquals([], $pool->getAllTokens());
        $a = $pool->getToken(['scope1', 'scope2'], null, 500);
        $pool = new AccessTokenPool($this->client, $this->user);
        $b = $pool->getToken(['scope1', 'scope2'], null, 1001);
        $this->assertNotEquals($a->access_token, $b->access_token);
    }

    public function testRecycleNewest() {
        $a = $this->token($this->client, $this->user, ['scope1', 'scope2'], 1000);
        $b = $this->token($this->client, $this->user, ['scope1', 'scope2'], 2000);
        $c = $this->token($this->client, $this->user, ['scope1', 'scope2'], 100);
        $pool = new AccessTokenPool($this->client, $this->user);
        $d = $pool->getToken(['scope1', 'scope2'], null, 2000);
        $this->assertNotEquals($a->access_token, $d->access_token);
        $this->assertNotEquals($c->access_token, $d->access_token);
        $this->assertEquals($b->access_token, $d->access_token);
        $pool = new AccessTokenPool($this->client, $this->user);
    }

    public function testDontRecycleMoreScopes() {
        $a = $this->token($this->client, $this->user, ['scope1', 'scope2'], 1000);
        $pool = new AccessTokenPool($this->client, $this->user);
        $b = $pool->getToken(['scope1'], null, 2000);
        $this->assertNotEquals($a->access_token, $b->access_token);
        $this->assertEquals(['scope1'], $b->scope);
    }

    public function testBasicSubToken() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $a = $pool->getToken(['scope1'], ['api1' => ['scope2']], 1000);
        $this->assertTrue(empty($a->apigkid));
        $this->assertEquals(1, count($a->subtokens));
        $this->assertEquals(['api1'], array_keys($a->subtokens));
        $st = $this->db->getAccessToken($a->subtokens['api1']);
        $this->assertEquals(['scope2'], $st->scope);
    }

    public function testDontRecycleMoreApis() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $t1 = $pool->getToken(['scope1'], ['api1' => ['scope2'], 'api2' => ['scope3']], 1000);
        $pool = new AccessTokenPool($this->client, $this->user);
        $t2 = $pool->getToken(['scope1'], ['api1' => ['scope2']], 1000);
        $this->assertNotEquals($t1->access_token, $t2->access_token);
        $this->assertEquals(1, count($t2->subtokens));
    }

    public function testDontRecycleMoreApiScopes() {
        error_log("Start");
        $pool = new AccessTokenPool($this->client, $this->user);
        $t1 = $pool->getToken(['scope1'], ['api1' => ['scope2', 'scope3']], 1000);
        $pool = new AccessTokenPool($this->client, $this->user);
        $t2 = $pool->getToken(['scope1'], ['api1' => ['scope2']], 1000);
        $this->assertNotEquals($t1->access_token, $t2->access_token);
        $this->assertEquals(1, count($t2->subtokens));
        $st = $this->db->getAccessToken($t2->subtokens['api1']);
        $this->assertEquals(['scope2'], $st->scope);
    }

    public function testRecycleSubtokens() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $t1 = $pool->getToken(['scope1'], ['api1' => ['scope2']], 1000);
        $pool = new AccessTokenPool($this->client, $this->user);
        $t2 = $pool->getToken(['scope1'], ['api1' => ['scope2']], 1000);
        $this->assertEquals($t1->access_token, $t2->access_token);
    }
}
