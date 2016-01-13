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
        $a = $pool->getToken(['scope1', 'scope2'], 1000);
        $pool = new AccessTokenPool($this->client, $this->user);
        $b = $pool->getToken(['scope1', 'scope2'], 1000);
        $this->assertEquals($a->access_token, $b->access_token);
    }

    public function testDontRecycleOld() {
        $pool = new AccessTokenPool($this->client, $this->user);
        $this->assertEquals([], $pool->getAllTokens());
        $a = $pool->getToken(['scope1', 'scope2'], 500);
        $pool = new AccessTokenPool($this->client, $this->user);
        $b = $pool->getToken(['scope1', 'scope2'], 1001);
        $this->assertNotEquals($a->access_token, $b->access_token);
    }

    public function testRecycleNewest() {
        $a = $this->token($this->client, $this->user, ['scope1', 'scope2'], 1000);
        $b = $this->token($this->client, $this->user, ['scope1', 'scope2'], 2000);
        $c = $this->token($this->client, $this->user, ['scope1', 'scope2'], 100);
        $pool = new AccessTokenPool($this->client, $this->user);
        $d = $pool->getToken(['scope1', 'scope2'], 2000);
        $this->assertNotEquals($a->access_token, $d->access_token);
        $this->assertNotEquals($c->access_token, $d->access_token);
        $this->assertEquals($b->access_token, $d->access_token);
        $pool = new AccessTokenPool($this->client, $this->user);
    }

    public function testDontRecycleMoreScopes() {
        $a = $this->token($this->client, $this->user, ['scope1', 'scope2'], 1000);
        $pool = new AccessTokenPool($this->client, $this->user);
        $b = $pool->getToken(['scope1'], 2000);
        $this->assertNotEquals($a->access_token, $b->access_token);
        $this->assertEquals(['scope1'], $b->scope);
    }
}
