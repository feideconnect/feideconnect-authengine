<?php

namespace tests;

use FeideConnect\OAuth\AuthorizationEvaluator;
use FeideConnect\OAuth\Messages\AuthorizationRequest;
use FeideConnect\OAuth\ScopesInspector;

class ScopesInspectorTest extends DBHelper {
    protected $api, $org;

    public function setUp() {
        $this->api = $this->apigk();
        $this->client = $this->client();
        $req = new AuthorizationRequest([
            "response_type" => "code",
            "scope" => "",
            "client_id" => "f1343f3a-79cc-424f-9233-5fe33f8bbd56"
        ]);
        $this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $req);
    }

    public function myAssertSubset($a, $b) {
        foreach ($a as $key => $value) {
            $this->assertArrayHasKey($key, $b, "missing key " . $key);
            $this->assertEquals($value, $b[$key], "value does not match for key " . $key);
        }
    }

    public function testGlobalScope() {
        $test = new ScopesInspector(['userid', 'email'], $this->aevaluator);
        $res = $test->getInfo();
        $this->assertEquals($res, array(
            'hasAPIs' => false,
            'allScopes' => ['userid', 'email'],
            'apis' => [],
            'global' => [
                'userid' => true,
                'email' => true,
            ],
        ));
    }

    public function testUnknownScope() {
        $test = new ScopesInspector(['ugle'], $this->aevaluator);
        $res = $test->getInfo();
        $this->assertEquals($res, array(
            'hasAPIs' => false,
            'allScopes' => ['ugle'],
            'apis' => [],
            'global' => [
                'ugle' => true
            ],
        ));
    }

    public function testUnknownGKScope() {
        $test = new ScopesInspector(['gk_ugle'], $this->aevaluator);
        $res = $test->getInfo();
        $this->assertEquals($res, array(
            'hasAPIs' => false,
            'allScopes' => ['gk_ugle'],
            'apis' => [],
            'unknownAPI' => [
                'gk_ugle' => true
            ],
            'global' => [],
        ));
    }

    public function testApiBasic() {
        $test = new ScopesInspector(['gk_test'], $this->aevaluator);
        $res = $test->getInfo();
        $this->myAssertSubset(array(
            'hasAPIs' => true,
            'allScopes' => ['gk_test'],
            'global' => [],
        ), $res);
        $this->assertArrayHasKey('apis', $res);
        $apis = $res['apis'];
        $this->assertCount(1, $apis);
        $api = $apis[0];
        $this->assertArrayHasKey('owner', $api);
        $owner = $api['owner'];
        $this->assertArrayHasKey('userid', $owner);
        $this->assertArrayHasKey('userid_sec', $owner);
        $this->assertArrayHasKey('name', $owner);
        $this->assertArrayHasKey('email', $owner);

        $this->assertArrayHasKey('info', $api);
        $this->assertArrayHasKey('scopes', $api);
        $this->myAssertSubset(array(
            'id' => 'test',
            'name' => 'Test API',
        ), $api['info']);
        $this->assertEquals([array(
            'title' => 'Basic access',
            'descr' => 'Basic access to this API.',
        )], $api['scopes']);
    }

    public function testApiSubscope() {
        $test = new ScopesInspector(['gk_test', 'gk_test_a', 'gk_test_b'], $this->aevaluator);
        $res = $test->getInfo();
        $this->myAssertSubset(array(
            'hasAPIs' => true,
            'allScopes' => ['gk_test', 'gk_test_a', 'gk_test_b'],
            'global' => [],
        ), $res);
        $this->assertArrayHasKey('apis', $res);
        $apis = $res['apis'];
        $this->assertCount(1, $apis);
        $api = $apis[0];
        $this->assertArrayHasKey('owner', $api);
        $owner = $api['owner'];
        $this->assertArrayHasKey('userid', $owner);
        $this->assertArrayHasKey('userid_sec', $owner);
        $this->assertArrayHasKey('name', $owner);
        $this->assertArrayHasKey('email', $owner);

        $this->assertArrayHasKey('info', $api);
        $this->assertArrayHasKey('scopes', $api);
        $this->myAssertSubset(array(
            'id' => 'test',
            'name' => 'Test API',
        ), $api['info']);
        $this->assertEquals([
            array(
                'title' => 'Basic access',
                'descr' => 'Basic access to this API.',
            ),
            array(
                'title' => 'scope a',
                'descr' => 'test scope a',
                'policy' => array('auto' => true),
            ),
            array(
                'title' => 'scope b',
                'descr' => 'test scope b',
                'policy' => array('auto' => false),
            ),
        ], $api['scopes']);
    }

    public function testApiOrg() {
        $this->org = $org = $this->org();
        $this->api->organization = $org->id;
        $this->db->saveAPIGK($this->api);

        $test = new ScopesInspector(['gk_test'], $this->aevaluator);
        $res = $test->getInfo();
        $this->assertArrayHasKey('apis', $res);
        $apis = $res['apis'];
        $this->assertCount(1, $apis);
        $api = $apis[0];
        $this->assertArrayNotHasKey('owner', $api);
        $this->assertArrayHasKey('org', $api);
        $org = $api['org'];
        $this->assertEquals(array(
            'id' => 'fc:org:example.org',
            'name' => array(
                'en' => 'Test organization',
                'nb' => 'Testorganisasjon',
            ),
            'logoURL' => 'https://api.dataporten.no/orgs/fc:org:example.org/logo',
            'type' => ['higher_education'],
        ), $org);
    }

    public function tearDown() {
        $this->db->removeAPIGK($this->api);
        if (isset($this->org)) {
            $this->db->removeOrganization($this->org);
            $this->org = null;
        }
    }

}
