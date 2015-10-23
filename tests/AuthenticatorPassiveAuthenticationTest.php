<?php
namespace tests;

use FeideConnect\Authentication\AuthSource;
use FeideConnect\Authentication\Authenticator;
use FeideConnect\Config;

class AuthenticatorPassiveAuthenticationTest extends DBHelper {
    public function setUp() {
        parent::setUp();
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        AuthSource::setFactory(['\tests\MockAuthSource', 'create']);
        $this->client = $this->client();
        $this->client->authoptions["requireInteraction"] = false;

        $authTypes = Config::getValue("authTypes");
        $as = $this->prophesize('\tests\MockAuthSource');
        $as->isAuthenticated()->willReturn(false);
        foreach ($authTypes as $authtype => $authTypeConfig) {
            MockAuthSource::set($authTypeConfig["authSource"], $as->reveal());
        }
    }

    public function testPassiveLoggedIn() {
        $as = new MockAuthSource('default-sp');
        $as->authenticated = true;
        MockAuthSource::set('default-sp', $as);
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->passiveAuthentication($this->client));
    }

    public function testPassiveNotLoggedIn() {
        $as = $this->prophesize('\tests\MockAuthSource');
        $as->login([
            'isPassive' => true,
            'saml:idp' => 'https://idp.feide.no',
            'ErrorURL' => "http://localhost/foo?error=1",
        ])->shouldBeCalled();
        $as->getAuthData('AuthnInstant')->willReturn(time() - 30);
        $as->isAuthenticated()->willReturn(false)->shouldBeCalled();
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->passiveAuthentication($this->client));
    }

    public function testPassiveMaxagePassed() {
        $this->setExpectedException(
            'FeideConnect\Exceptions\RedirectException',
            'http://localhost/foo?error=1'
        );

        $as = $this->prophesize('\tests\MockAuthSource');
        $as->isAuthenticated()->willReturn(true)->shouldBeCalled();
        $as->getAuthData('AuthnInstant')->willReturn(time() - 90)->shouldBeCalled();
        $as->getAuthData('saml:sp:IdP')->willReturn("https://idp.feide.no");
        $as->getAttributes()->willReturn(MockAuthSource::$attributes);
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->passiveAuthentication($this->client, 60));
    }

    public function testPassiveLoggedInMaxageOK() {
        $as = $this->prophesize('\tests\MockAuthSource');
        $as->isAuthenticated()->willReturn(true)->shouldBeCalled();
        $as->getAuthData('AuthnInstant')->willReturn(time() - 30)->shouldBeCalled();
        $as->getAuthData('saml:sp:IdP')->willReturn("https://idp.feide.no");
        $as->getAttributes()->willReturn(MockAuthSource::$attributes);
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->passiveAuthentication($this->client, 60));
    }

    public function testPassiveClientRequiresInteraction() {
        $this->setExpectedException(
            'FeideConnect\Exceptions\RedirectException',
            'http://localhost/foo?error=1'
        );
        $this->client->authoptions["requireInteraction"] = true;
        $authenticator = new Authenticator();
        $authenticator->passiveAuthentication($this->client, 60);
    }

    public function tearDown() {
        parent::tearDown();
        MockAuthSource::clear();
    }
}