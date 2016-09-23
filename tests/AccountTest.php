<?php
namespace tests;

use FeideConnect\Authentication\Account;


class AccountTest extends DBHelper {
    protected $org;

    private static $feideidp = 'https://idp.feide.no';
    private static $feideAM = array (
        '_title' => 'Feide account mapper',
        'authSource' => 'default-sp',
        'idp' =>
        array (
            0 => 'https://idp-test.feide.no',
            1 => 'https://idp.feide.no',
        ),
        'sourceID' =>
        array (
            'type' => 'sourceID',
            'prefix' => 'feide',
            'realm' => true,
        ),
        'useridNormalize' => true,
        'userid' =>
        array (
            'feide' => 'eduPersonPrincipalName',
        ),
        'realm' =>
        array (
            'attrname' => 'eduPersonPrincipalName',
            'type' => 'realm',
        ),
        'name' =>
        array (
            'attrnames' =>
            array (
                0 => 'displayName',
                1 => 'cn',
            ),
        ),
        'mail' => 'mail',
        'org' => 'o',
        'photo' => 'jpegPhoto',
        'yob' => 'feideYearOfBirth',
    );
    private static $idportenAM = array (
        '_title' => 'IDporten accountmapper',
        'authSource' => 'default-sp',
        'idp' =>
        array (
            0 => 'idporten.difi.no-v3',
        ),
        'sourceID' =>
        array (
            'type' => 'sourceID',
            'prefix' => 'idporten',
            'realm' => false,
        ),
        'userid' =>
        array (
            'nin' => 'uid',
        ),
        'realm' => null,
        'name' =>
        array (
            'type' => 'fixed',
            'value' => 'IDporten user',
        ),
        'mail' => null,
        'org' => null,
        'photo' => null,
        'yob' => null,
    );

    private static $openidpAM = array (
        '_title' => 'Feide OpenIdP',
        'authSource' => 'default-sp',
        'idp' =>
        array (
            0 => 'https://openidp.feide.no',
        ),
        'sourceID' =>
        array (
            'type' => 'sourceID',
            'prefix' => 'openidp',
            'realm' => false,
        ),
        'userid' =>
        array (
            'feide' => 'eduPersonPrincipalName',
        ),
        'realm' => null,
        'name' =>
        array (
            'attrnames' =>
            array (
                0 => 'displayName',
                1 => 'cn',
            ),
        ),
        'mail' => 'mail',
        'org' => null,
        'photo' => 'jpegPhoto',
        'yob' => null,
    );

    public static $twitterAM = array (
        '_title' => 'Twitter accountmapper',
        'authSource' => 'twitter',
        'sourceID' =>
        array (
            'type' => 'sourceID',
            'prefix' => 'twitter',
            'realm' => false,
        ),
        'userid' =>
        array (
            'twitter' => 'twitter.id_str',
        ),
        'realm' => null,
        'name' =>
        array (
            'attrnames' =>
            array (
                0 => 'twitter.name',
                1 => 'twitter_at_screen_name',
            ),
        ),
        'mail' => null,
        'org' => null,
        'photo' =>
        array (
            'type' => 'urlref',
            'attrname' => 'twitter.profile_image_url',
        ),
        'yob' => null,
    );

    public static $linkedinAM = array (
        '_title' => 'Linkedin accountmapper',
        'authSource' => 'linkedin',
        'sourceID' =>
        array (
            'type' => 'sourceID',
            'prefix' => 'linkedin',
            'realm' => false,
        ),
        'userid' =>
        array (
            'linkedin' => 'linkedin.id',
        ),
        'realm' => null,
        'name' =>
        array (
            'joinattrnames' =>
            array (
                0 => 'linkedin.firstName',
                1 => 'linkedin.lastName',
            ),
        ),
        'mail' => null,
        'org' => null,
        'photo' =>
        array (
            'type' => 'urlref',
            'attrname' => 'linkedin.pictureUrl',
        ),
        'yob' => null,
    );

    public static $facebookAM = array (
        '_title' => 'Facebook accountmapper',
        'authSource' => 'facebook',
        'sourceID' =>
        array (
            'type' => 'sourceID',
            'prefix' => 'facebook',
            'realm' => false,
        ),
        'userid' =>
        array (
            'facebook' => 'facebook.id',
        ),
        'realm' => null,
        'name' =>
        array (
            'attrnames' =>
            array (
                0 => 'facebook.name',
            ),
        ),
        'mail' => null,
        'org' => null,
        'photo' => null,
        'yob' => null,
    );

    public function setUp() {
        parent::setUp();
        $this->org = $this->org();
    }

    public function testGetVisualTagFeide() {
        $account = new Account([
            'eduPersonPrincipalName' => ['test@example.net'],
            'idp' => self::$feideidp,
        ], self::$feideAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => '',
            'type' => 'saml',
            'id' => self::$feideidp,
            'subid' => 'example.net',
            'title' => null,
            'userids' => ['feide:test@example.net'],
            'def' => [
                ['feide', 'realm', 'example.net'],
            ]
        ], $tag);
    }

    public function testUserIDs() {
        $account = new Account([
            'eduPersonPrincipalName' => ['teST@example.net'],
            'idp' => self::$feideidp,
        ], self::$feideAM);
        $this->assertTrue($account->hasUserID('feide:test@example.net'));
        $this->assertFalse($account->hasUserID('feide:teST@example.net'));
        $this->assertFalse($account->hasUserID('feide:nobody@example.net'));
        $this->assertTrue($account->hasAnyOfUserIDs(['feide:anyone@example.net', 'feide:test@example.net']));
        $this->assertFalse($account->hasAnyOfUserIDs(['feide:anyone@example.net', 'feide:nobody@example.net']));

        $fbaccount = new Account([
            'facebook.name' => ['fb user'],
            'facebook.id' => [ 'sdlkfjsdfX' ],
            'idp' => null,
        ], self::$facebookAM);
        $this->assertTrue($fbaccount->hasUserID('facebook:sdlkfjsdfX'));
        $this->assertFalse($fbaccount->hasUserID('facebook:sdlkfjsdfx'));
    }

    public function testGetVisualTagFeideOrg() {
        $account = new Account([
            'eduPersonPrincipalName' => ['test@example.org'],
            'idp' => self::$feideidp,
        ], self::$feideAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => '',
            'type' => 'saml',
            'id' => self::$feideidp,
            'subid' => 'example.org',
            'title' => null,
            'userids' => ['feide:test@example.org'],
            'def' => [
                ['feide', 'realm', 'example.org'],
                ['feide', 'he'],
            ]
        ], $tag);
    }

    public function testGetVisualTagFeideTest() {
        $account = new Account([
            'eduPersonPrincipalName' => ['test@spusers.feide.no'],
            'idp' => self::$feideidp,
        ], self::$feideAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => '',
            'type' => 'saml',
            'id' => self::$feideidp,
            'subid' => 'spusers.feide.no',
            'title' => 'Feide testbruker',
            'userids' => ['feide:test@spusers.feide.no'],
            'def' => [
                ['other', 'feidetest'],
            ]
        ], $tag);
    }


    public function testGetVisualTagIDPorten() {
        $account = new Account([
            'uid' => ['01020312345'],
            'idp' => 'idporten.difi.no-v3',
        ], self::$idportenAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => 'IDporten user',
            'type' => 'saml',
            'id' => 'idporten.difi.no-v3',
            'title' => 'IDporten',
            'userids' => ['nin:0102.......'],
            'def' => [
                ['idporten'],
            ]
        ], $tag);
    }

    public function testGetVisualTagOpenIdP() {
        $account = new Account([
            'eduPersonPrincipalName' => ['test@example.org'],
            'displayName' => ['test user'],
            'idp' => 'https://openidp.feide.no',
        ], self::$openidpAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => 'test user',
            'type' => 'saml',
            'id' => 'https://openidp.feide.no',
            'title' => 'Feide OpenIdP guest account',
            'userids' => ['feide:test@example.org'],
            'def' => [
                ['other', 'openidp'],
            ]
        ], $tag);
    }

    public function testGetVisualTagTwitter() {
        $account = new Account([
            'twitter.name' => ['twitter user'],
            'twitter.id_str' => [ '12345' ],
            'idp' => null,
        ], self::$twitterAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => 'twitter user',
            'type' => 'twitter',
            'title' => 'Twitter',
            'userids' => ['twitter:12345'],
            'def' => [
                ['social', 'twitter'],
            ]
        ], $tag);
    }

    public function testGetVisualTagLinkedIn() {
        $account = new Account([
            'linkedin.firstName' => ['ln'],
            'linkedin.lastName' => ['user'],
            'linkedin.id' => [ '12345' ],
            'idp' => null,
        ], self::$linkedinAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => 'ln user',
            'type' => 'linkedin',
            'title' => 'LinkedIn',
            'userids' => ['linkedin:12345'],
            'def' => [
                ['social', 'linkedin'],
            ]
        ], $tag);
    }

    public function testGetVisualTagFacebook() {
        $account = new Account([
            'facebook.name' => ['fb user'],
            'facebook.id' => [ '12345' ],
            'idp' => null,
        ], self::$facebookAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => 'fb user',
            'type' => 'facebook',
            'title' => 'Facebook',
            'userids' => ['facebook:12345'],
            'def' => [
                ['social', 'facebook'],
            ]
        ], $tag);
    }


    public function testValidateAuthProviderOK() {
        $account = new Account([
            'eduPersonPrincipalName' => ['test@example.org'],
            'idp' => self::$feideidp,
        ], self::$feideAM);
        $this->assertTrue($account->validateAuthProvider(['all']));
        $this->assertTrue($account->validateAuthProvider(['feide|all']));
        $this->assertTrue($account->validateAuthProvider(['feide|realm|example.org']));
    }

    public function testValidateAuthProviderFail() {
        $this->setExpectedException('\FeideConnect\Exceptions\AuthProviderNotAccepted');
        $account = new Account([
            'eduPersonPrincipalName' => ['test@example.org'],
            'idp' => self::$feideidp,
        ], self::$feideAM);
        $account->validateAuthProvider(['social|facebook']);
    }

    public function testAgeLimit() {
        $this->assertTrue(Account::checkAgeLimit(1950, 13, mktime(0, 0, 0, 1, 1, 1964)));
        $this->assertTrue(Account::checkAgeLimit(1950, 13, mktime(0, 0, 0, 9, 1, 1963)));
        $this->assertTrue(Account::checkAgeLimit(1950, 13, mktime(0, 0, 0, 9, 1, 1982)));
        $this->assertTrue(Account::checkAgeLimit(1950, 13, mktime(0, 0, 0, 9, 1, 2063)));
        $this->assertFalse(Account::checkAgeLimit(1950, 13, mktime(0, 0, 0, 9, 1, 1953)));
        $this->assertFalse(Account::checkAgeLimit(1950, 13, mktime(0, 0, 0, 1, 1, 1963)));
    }
}
