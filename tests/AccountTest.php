<?php
namespace tests;

use FeideConnect\Authentication\Account;
use FeideConnect\Exceptions\Exception;
use tests\EdugainMetadata;

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
        'photo' => null,
        'yob' => null,
    );

    public static $edugainAM = array (
        '_title' => 'EduGAIN Account Mapper',
        'authSource' => 'default-sp',
        'idp' => NULL,
        'sourceID' => array (
            'type' => 'sourceID',
            'prefix' => 'edugain',
            'realm' => false,
            'country' => true,
        ),
        'userid' => array (
            'edugain' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
        ),
        'realm' => NULL,
        'name' => array (
            'attrnames' => array (
                0 => 'urn:oid:2.16.840.1.113730.3.1.241',
                1 => 'urn:oid:2.5.4.3',
            ),
        ),
        'mail' => 'urn:oid:0.9.2342.19200300.100.1.3',
        'org' => NULL,
        'photo' => 'jpegPhoto',
        'yob' => NULL,
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

    public function testGetVisualTagEduGAINnoIdP() {
        $this->setExpectedException('\FeideConnect\Exceptions\Exception');
        $account = new Account([
            'eduPersonPrincipalName' => ['test@example.net'],
            'idp' => 'http://someidp.that.does.notexist.org',
        ], self::$edugainAM);
    }

    public function testGetVisualTagEduGAIN() {

        // We need to insert a metadata entry to be able to create an account
        // representing an edugain user. The idp reference will be used to lookup information
        // about the origin country.
        $metastore = new \sspmod_cassandrastore_MetadataStore_CassandraMetadataStore([]);
        $metastore->insert('edugain', EdugainMetadata::$mentityid, EdugainMetadata::$m,
            EdugainMetadata::$mui, EdugainMetadata::$mreg
        );

        $account = new Account([
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.6' => ['test@example.net'],
            'idp' => EdugainMetadata::$mentityid,
            'country' => 'de'
        ], self::$edugainAM);
        $tag = $account->getVisualTag();
        $this->assertEquals([
            'name' => '',
            'type' => 'saml',
            'id' => EdugainMetadata::$mentityid,
            'title' => "eduGAIN",
            'userids' => ['edugain:test@example.net'],
            'def' => [
                ['edugain', 'de'],
            ],
            'country' => [
                "code" => "de",
                "title" => "Germany",
            ]
        ], $tag);
        $metastore->delete('edugain', EdugainMetadata::$mentityid);
    }

    public function testUserIDs() {
        $account = new Account([
            'eduPersonPrincipalName' => ['teST@example.net'],
            'idp' => self::$feideidp,
        ], self::$feideAM);
        $this->assertTrue($account->hasUserID('feide:test@example.net'));
        $this->assertTrue($account->hasMaskedUserID('feide:test@example.net'));
        $this->assertFalse($account->hasUserID('feide:teST@example.net'));
        $this->assertFalse($account->hasUserID('feide:nobody@example.net'));
        $this->assertTrue($account->hasAnyOfUserIDs(['feide:anyone@example.net', 'feide:test@example.net']));
        $this->assertTrue($account->hasAnyOfMaskedUserIDs(['feide:anyone@example.net', 'feide:test@example.net']));
        $this->assertFalse($account->hasAnyOfUserIDs(['feide:anyone@example.net', 'feide:nobody@example.net']));

        $fbaccount = new Account([
            'facebook.name' => ['fb user'],
            'facebook.id' => [ 'sdlkfjsdfX' ],
            'idp' => null,
        ], self::$facebookAM);
        $this->assertTrue($fbaccount->hasUserID('facebook:sdlkfjsdfX'));
        $this->assertFalse($fbaccount->hasUserID('facebook:sdlkfjsdfx'));


        $idportenAccount = new Account([
            'uid' => ['01020312345'],
            'idp' => 'idporten.difi.no-v3',
        ], self::$idportenAM);
        // echo var_export($idportenAccount->userids, true);
        $this->assertTrue($idportenAccount->hasUserID('nin:01020312345'));
        $this->assertFalse($idportenAccount->hasUserID('nin:0102.......'));
        $this->assertTrue($idportenAccount->hasAnyOfUserIDs(['nin:01020312345']));
        $this->assertFalse($idportenAccount->hasAnyOfUserIDs(['nin:0102.......']));

        $this->assertFalse($idportenAccount->hasMaskedUserID('nin:01020312345'));
        $this->assertTrue($idportenAccount->hasMaskedUserID('nin:0102.......'));
        $this->assertFalse($idportenAccount->hasAnyOfMaskedUserIDs(['feide:foo', 'nin:01020312345']));
        $this->assertTrue($idportenAccount->hasAnyOfMaskedUserIDs(['feide:foo', 'nin:0102.......']));


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
            'title' => 'Testorganisasjon',
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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Year of birth in user account is in the future');
        Account::checkAgeLimit(1950, 13, mktime(0, 0, 0, 1, 1, 1943));
    }
}
