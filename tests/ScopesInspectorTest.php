<?php

namespace tests;

use FeideConnect\OAuth\ScopesInspector;
use FeideConnect\Data\StorageProvider;

class ScopesInspectorTest extends \PHPUnit_Framework_TestCase {
	protected $db, $dbhelper, $api, $org;

	function __construct() {

		// $config = json_decode(file_get_contents(__DIR__ . '/../etc/ci/config.json'), true);
		$this->db = StorageProvider::getStorage();
		$this->dbhelper = new DBHelper();
	}

	public function setUp() {
		$this->api = $this->dbhelper->apigk();
	}

	public function myAssertSubset($a, $b) {
		foreach ($a AS $key => $value) {
			$this->assertArrayHasKey($key, $b, "missing key " . $key);
			$this->assertEquals($value, $b[$key], "value does not match for key " . $key);
		}
	}

	public function testGlobalScope() {
		$test = new ScopesInspector(null, ['userinfo']);
		$res = $test->getInfo();
		$this->assertEquals($res, array(
			'hasAPIs' => false,
			'allScopes' => ['userinfo'],
			'unknown' => [],
			'apis' => [],
			'global' => array(
				'userinfo' => array(
					'title' => 'Brukerinfo',
					'descr' => 'Basisinformasjon om brukere. BrukerID og navn.',
					'public' => true,
					'policy' => array('auto' => true),
					'scope' => 'userinfo',
				),
			),
		));
	}

	public function testUnknownScope() {
		$test = new ScopesInspector(null, ['ugle']);
		$res = $test->getInfo();
		$this->assertEquals($res, array(
			'hasAPIs' => false,
			'allScopes' => ['ugle'],
			'unknown' => ['ugle'],
			'apis' => [],
			'global' => [],
		));
	}

	public function testUnknownGKScope() {
		$test = new ScopesInspector(null, ['gk_ugle']);
		$res = $test->getInfo();
		$this->assertEquals($res, array(
			'hasAPIs' => false,
			'allScopes' => ['gk_ugle'],
			'unknown' => ['gk_ugle'],
			'apis' => [],
			'global' => [],
		));
	}

	public function testApiBasic() {
		$test = new ScopesInspector(null, ['gk_test']);
		$res = $test->getInfo();
		$this->myAssertSubset(array(
			'hasAPIs' => true,
			'allScopes' => ['gk_test'],
			'unknown' => [],
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
		$test = new ScopesInspector(null, ['gk_test', 'gk_test_a']);
		$res = $test->getInfo();
		$this->myAssertSubset(array(
			'hasAPIs' => true,
			'allScopes' => ['gk_test', 'gk_test_a'],
			'unknown' => [],
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
		], $api['scopes']);
	}

	public function testApiOrg() {
		$this->org = $org = $this->dbhelper->org();
		$this->api->organization = $org->id;
		$this->db->saveAPIGK($this->api);
		
		$test = new ScopesInspector(null, ['gk_test']);
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
			'logoURL' => 'https://api.feideconnect.no/orgs/fc:org:example.org/logo'
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
