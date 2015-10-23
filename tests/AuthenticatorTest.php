<?php
namespace tests;

use FeideConnect\Authentication\AuthSource;
use FeideConnect\Authentication\Authenticator;

class AuthenticatorRequireAuthenticationTest extends DBHelper {
    public function setUp() {
        parent::setUp();
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        AuthSource::setFactory(['\tests\MockAuthSource', 'create']);
    }

    public function testToAccountChooser() {
        $this->setExpectedExceptionRegExp(
            'FeideConnect\Exceptions\RedirectException',
            '/^http:\/\/localhost\/accountchooser\?/'
        );
        $authenticator = new Authenticator();
        $authenticator->requireAuthentication();
    }

    public function testActiveLoggedIn() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $as = AuthSource::create('default-sp');
        $as->authenticated = true;
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->requireAuthentication(false));
    }

    public function testActiveNotLoggedIn() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $as = $this->prophesize('\tests\MockAuthSource');
        $as->requireAuth(['saml:idp' => 'https://idp.feide.no'])->shouldBeCalled();
        $as->isAuthenticated()->willReturn(false)->shouldBeCalled();
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $authenticator->requireAuthentication(false);
    }

    public function testPassiveLoggedIn() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $as = new MockAuthSource('default-sp');
        $as->authenticated = true;
        MockAuthSource::set('default-sp', $as);
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->requireAuthentication(true));
    }

    public function testPassiveNotLoggedIn() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $as = $this->prophesize('\tests\MockAuthSource');
        $as->login([
            'saml:idp' => 'https://idp.feide.no',
            'isPassive' => true,
            'ErrorURL' => "http://localhost/foo?error=1",
        ])->shouldBeCalled();
        $as->getAuthData('AuthnInstant')->willReturn(time() - 30);
        $as->isAuthenticated()->willReturn(false)->shouldBeCalled();
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->requireAuthentication(true));
    }

    public function testActiveMaxageOK() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $as = $this->prophesize('\tests\MockAuthSource');
        $as->isAuthenticated()->willReturn(true)->shouldBeCalled();
        $as->getAuthData('AuthnInstant')->willReturn(time() - 30)->shouldBeCalled();
        $as->getAuthData('saml:sp:IdP')->willReturn("https://idp.feide.no")->shouldBeCalled();
        $as->getAttributes()->willReturn(MockAuthSource::$attributes)->shouldBeCalled();
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->requireAuthentication(false, 60));
    }

    public function testActiveMaxagePassed() {
        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $as = $this->prophesize('\tests\MockAuthSource');
        $as->isAuthenticated()->willReturn(true)->shouldBeCalled();
        $as->getAuthData('AuthnInstant')->willReturn(time() - 90)->shouldBeCalled();
        $as->getAuthData('saml:sp:IdP')->willReturn("https://idp.feide.no")->shouldBeCalled();
        $as->getAttributes()->willReturn(MockAuthSource::$attributes)->shouldBeCalled();
        $as->login([
            'saml:idp' => 'https://idp.feide.no',
            'ForceAuthn' => true,
        ])->shouldBeCalled();
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->requireAuthentication(false, 60));
    }

    public function testPassiveMaxagePassed() {
        $this->setExpectedException(
            'FeideConnect\Exceptions\RedirectException',
            'http://localhost/foo?error=1'
        );

        $_REQUEST['acresponse'] = '{"id": "https://idp.feide.no","subid":"example.org"}';

        $as = $this->prophesize('\tests\MockAuthSource');
        $as->isAuthenticated()->willReturn(true)->shouldBeCalled();
        $as->getAuthData('AuthnInstant')->willReturn(time() - 90)->shouldBeCalled();
        $as->getAuthData('saml:sp:IdP')->willReturn("https://idp.feide.no")->shouldBeCalled();
        $as->getAttributes()->willReturn(MockAuthSource::$attributes)->shouldBeCalled();
        $as->login([
            'saml:idp' => 'https://idp.feide.no',
            'isPassive' => true,
            'ErrorURL' => "http://localhost/foo?error=1",
        ])->shouldBeCalled();
        MockAuthSource::set('default-sp', $as->reveal());
        $authenticator = new Authenticator();
        $this->assertNull($authenticator->requireAuthentication(true, 60));
    }
}
