<?php
namespace tests;

use FeideConnect\Controllers\OpenIDConnect;
use Prophecy;
use Prophecy\Argument;


class OpenIDConnectControllerTest extends DBHelper {
    private $user;

    public function getUser() {
        return $this->user;
    }

    public function testConfig() {
        $response = OpenIDConnect::config();
        $this->assertInstanceOf('\FeideConnect\HTTP\JSONResponse', $response);
        $this->assertEquals([
            'authorization_endpoint' => 'http://localhost/oauth/authorization',
            'token_endpoint' => 'http://localhost/oauth/token',
            'userinfo_endpoint' => 'http://localhost/openid/userinfo',
            'jwks_uri' => 'http://localhost/openid/jwks',
            'issuer' => 'https://sa-test-auth.feideconnect.no',
            'service_documentation' => 'https://docs.dataporten.no',
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
            'ui_locales_supported' => ['en', 'no', 'nb', 'nn'],
            'response_types_supported' => ['code', 'id_token token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
        ], $response->getData());
    }

    public function testGetJWKs() {
        $response = OpenIDConnect::getJWKs();
        $this->assertInstanceOf('\FeideConnect\HTTP\JSONResponse', $response);
        $data = $response->getData();
        $this->assertArrayHasKey('keys', $data);
        $jwk = $data['keys'];
        foreach ($jwk as $d) {
            $this->assertArrayHasKey('kty', $d);
            $this->assertArrayHasKey('n', $d);
            $this->assertArrayHasKey('e', $d);
            $this->assertEquals('RSA', $d['kty']);
            $this->assertArrayHasKey('x5c', $d);
        }
    }

    protected function protector($scopes) {
        $apiprotector = $this->prophesize('\FeideConnect\OAuth\APIProtector');
        $returnThis = new Prophecy\Promise\CallbackPromise(['\FeideConnect\OAuth\APIProtector', 'get']);
        $apiprotector->requireClient()->will($returnThis)->shouldBeCalled();
        $apiprotector->requireUser()->will($returnThis)->shouldBeCalled();
        $apiprotector->requireScopes(['openid'])->will($returnThis)->shouldBeCalled();
        $apiprotector->getUser()->will([$this, "getUser"])->shouldBeCalled();
        $apiprotector->getScopes()->willReturn($scopes)->shouldBeCalled();
        foreach ($scopes as $scope) {
            $apiprotector->hasScopes([$scope])->willReturn(true);
        }
        $apiprotector->hasScopes(Argument::any())->willReturn(false);
        \FeideConnect\OAuth\APIProtector::$instance = $apiprotector->reveal();
    }

    public function testUserInfoBasic() {
        $this->user = $this->user();
        $this->protector(['openid', 'profile']);
        $response = OpenIDConnect::userinfo();
        $this->assertInstanceOf('\FeideConnect\HTTP\JSONResponse', $response);
        $data = $response->getData();
        $this->assertEquals([
            'sub' => $this->user->userid,
            'dataporten-userid_sec' => [],
            'connect-userid_sec' => [],
        ], $data);
    }

    public function testUserInfoBasicScopesNoInfo() {
        $this->user = $this->user();
        $this->protector(['openid', 'profile', 'email', 'userid-feide']);
        $response = OpenIDConnect::userinfo();
        $this->assertInstanceOf('\FeideConnect\HTTP\JSONResponse', $response);
        $data = $response->getData();
        $this->assertEquals([
            'sub' => $this->user->userid,
            'dataporten-userid_sec' => ['feide:testuser@example.org'],
            'connect-userid_sec' => ['feide:testuser@example.org'],
        ], $data);
    }

    protected function fullUser() {
        $this->user = $this->user();
        $this->user->email = ['feide:example.org' => 'test.user@example.org'];
        $this->user->name = ['feide:example.org' => 'Test User'];
        $this->user->ensureProfileAccess(false);
    }

    public function testUserInfoFull() {
        $this->fullUser();
        $this->protector(['openid', 'profile', 'email', 'userid-feide']);
        $response = OpenIDConnect::userinfo();
        $this->assertInstanceOf('\FeideConnect\HTTP\JSONResponse', $response);
        $data = $response->getData();
        $this->assertEquals([
            'sub' => $this->user->userid,
            'dataporten-userid_sec' => ['feide:testuser@example.org'],
            'connect-userid_sec' => ['feide:testuser@example.org'],
            'email' => 'test.user@example.org',
            'email_verified' => true,
            'name' => 'Test User',
            'picture' => 'https://api.feideconnect.no/userinfo/v1/user/media/' . $this->user->getProfileAccess(),
        ], $data);
    }

    public function testUserInfoFiltered() {
        $this->fullUser();
        $this->protector(['openid']);
        $response = OpenIDConnect::userinfo();
        $this->assertInstanceOf('\FeideConnect\HTTP\JSONResponse', $response);
        $data = $response->getData();
        $this->assertEquals([
            'sub' => $this->user->userid,
            'dataporten-userid_sec' => [],
            'connect-userid_sec' => [],
        ], $data);
    }
    
    public function tearDown() {
        parent::tearDown();
        \FeideConnect\OAuth\APIProtector::$instance = null;
    }
}
