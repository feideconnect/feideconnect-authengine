<?php

namespace tests;

use FeideConnect\Authentication\Authenticator;
use FeideConnect\Authentication\UserMapper;
use FeideConnect\Authentication\AttributeMapper;
use FeideConnect\Authentication\Account;
use FeideConnect\Data\Models;

class UserTest extends DBHelper {

    public function setUp() {
        $this->clearUsers();
    }

    public function testAccount() {



        $attributes = [
            "eduPersonPrincipalName" => ["andreas@uninett.no"],
            "jpegPhoto" => null,
            "mail" => ["andreas.solberg@uninett.no"],
            "displayName" => ["Andreas Åkre Solberg"],
            "o" => ["UNINETT AS"],
            "feideYearOfBirth" => ["1980"],
            "idp" => "https://idp-test.feide.no",
            "authSource" => "default-sp",
            "o" => ["UNINETT AS"]
        ];

        $attributeMapper = new AttributeMapper();


        // print_r($this->as); exit;
        $account = $attributeMapper->getAccount($attributes);


        $userids = $account->getUserIDs();
        $uidsShouldBe = ['feide:andreas@uninett.no'];

        $isAbove = $account->aboveAgeLimit();
        $this->assertEquals(true, $isAbove, 'Should be above legal age');

        $this->assertEmpty(array_diff($uidsShouldBe, $userids), 'Check that userids from account should be correct');
        $this->assertEquals('feide:uninett.no', $account->getSourceID(), 'Check that we get correct sourceID from account');
        $this->assertEquals('Andreas Åkre Solberg', $account->getName(), 'Check that we get correct name from account');
        $this->assertEquals('andreas.solberg@uninett.no', $account->getMail(), 'Check that we get correct mail from account');


        $u2 = $this->db->getUserByUserIDsecList($userids);
//        echo "\nUser list: \n\n"; var_dump($u2);
        $this->assertEquals(null, $u2, "Should not find any existing users  (result should be [null])");


        // $user = new Models\User();
        // $user->userid = '1e1d9730-fbf5-44c2-9c85-48594a2aecc3';
        // $user->userid_sec = ['feide:andreas@uninett.no'];


        $um = new UserMapper($this->db);
        $user = $um->getUser($account, true, true, false);

        // $this->assertEquals();

        // echo "\n\nabove age limit " . var_export($user->aboveagelimit, true);
        // echo "\n\nusage terms " . var_export($user->usageterms, true);





        $attributes2 = [
            "eduPersonPrincipalName" => ["andreas@uninett.no"],
            "jpegPhoto" => null,
            "mail" => ["andreas.solberg@uninett.no"],
            "displayName" => ["Andreas Åkre Solberg"],
            "o" => ["UNINETT AS"],
            "feideYearOfBirth" => ["2010"], // NOTICE BELOW AGE LIMIT
            "idp" => "https://idp-test.feide.no",
            "authSource" => "default-sp",
            "o" => ["UNINETT AS"]
        ];


        // $account2 = new Account($attributes2);
        $account2 = $attributeMapper->getAccount($attributes2);

        $isAbove2 = $account2->aboveAgeLimit();
        $this->assertEquals(false, $isAbove2, 'Should not be above legal age');


        $user2 = $um->getUser($account2, true, true, false);



        $u3 = $this->db->getUserByUserIDsecList($userids);
        $this->assertEquals(1, count($u3), 'Found exactly one user');

        $u3 = $u3[0];

        $this->assertEquals(false, $user2->aboveagelimit, 'aboveagelimit is set to false');
        $this->assertEquals(false, $u3->aboveagelimit, 'aboveagelimit is set to false');

        $this->assertEquals(false, $user2->usageterms, 'usageterms is set to false');
        $this->assertEquals(false, $u3->usageterms, 'usageterms is set to false');





        $this->db->deleteUser($user);

        $u2 = $this->db->getUserByUserIDsecList($userids);
        $this->assertEquals(null, $u2, "Should not find any existing users");


    }


    public function testGetFeideRealms() {
        $user = $this->user();
        $this->assertEquals($user->getFeideRealms(), ['example.org']);
        $user->userid_sec = [];
        $this->assertEquals($user->getFeideRealms(), []);
        $user->userid_sec = ['mail:foo@example.com', 'feide:dust@example.org', 'p:123', 'feide:ugle@example.net'];
        $realms = $user->getFeideRealms();
        sort($realms);
        $this->assertEquals($realms, ['example.net', 'example.org']);
        $user->userid_sec = ['feide:foo'];
        $this->assertEquals($user->getFeideRealms(), []);
    }

    public function testGetAccessibleUserInfo() {
        $user = $this->user();
        $user->email = ['feide:example.org' => 'test.user@example.org'];
        $user->name = ['feide:example.org' => 'Test User'];
        $user->userid_sec[] = 'p:abcde12345';
        $user->userid_sec[] = 'nin:01234567890';
        $user->userid_sec[] = 'facebook:3141592653589793';
        $accesses = [];
        $this->assertEquals(['userid_sec' => []], $user->getAccessibleUserInfo($accesses));
        $accesses = ['userid'];
        $this->assertEquals(['userid_sec' => [], 'userid' => $user->userid], $user->getAccessibleUserInfo($accesses));
        $accesses = ['name'];
        $this->assertEquals(['userid_sec' => [], 'name' => 'Test User'], $user->getAccessibleUserInfo($accesses));
        $accesses = ['email'];
        $this->assertEquals(['userid_sec' => [], 'email' => 'test.user@example.org'], $user->getAccessibleUserInfo($accesses));
        $accesses = ['photo'];
        $this->assertEquals(['userid_sec' => [], 'profilephoto' => 'p:abcde12345'], $user->getAccessibleUserInfo($accesses));
        $accesses = ['userid-feide'];
        $this->assertEquals(['userid_sec' => ['feide:testuser@example.org']], $user->getAccessibleUserInfo($accesses));
        $accesses = ['userid-nin'];
        $this->assertEquals(['userid_sec' => ['nin:01234567890']], $user->getAccessibleUserInfo($accesses));
        $accesses = ['userid-social'];
        $this->assertEquals(['userid_sec' => ['facebook:3141592653589793']], $user->getAccessibleUserInfo($accesses));
    }

    public function testUpdateFromAccount() {
        $user = $this->user();
        $attributes = [
            "name" => ["Test User"],
            "mail" => ["test.user@example.org"],
            "idp" => "feide",
            "realm" => ["testuser@example.org"],
        ];
        $rules = [
            "sourceID" => [
                "type" => "sourceID",
                "prefix" => "feide",
                "realm" => true
            ],
            "userid" => [],
            "name" => "name",
            "mail" => "mail",
            "yob" => "yob",
            "realm" => [
                "attrname" => "realm",
            ],
            "photo" => "photo",
        ];
        $account = new Account($attributes, $rules);
        $user->updateFromAccount($account);
        $user2 = $this->db->getUserByUserID($user->userid);
        $this->assertEquals(["feide:example.org" => "Test User"], $user2->name);
        $this->assertEquals(["feide:example.org" => "test.user@example.org"], $user2->email);
        $this->assertEquals("feide:example.org", $user2->selectedsource);

        # Test handling of users that has been "deleted"
        $user = $this->user();
        $user->selectedsource = null;
        $user->updateFromAccount($account);
        $user2 = $this->db->getUserByUserID($user->userid);
        $this->assertEquals(["feide:example.org" => "Test User"], $user2->name);
        $this->assertEquals(["feide:example.org" => "test.user@example.org"], $user2->email);
        $this->assertEquals("feide:example.org", $user2->selectedsource);

        # Test handling of users that has been added by peoplesearch
        $user->selectedsource = "ps:example.org";
        $user->updateFromAccount($account);
        $user2 = $this->db->getUserByUserID($user->userid);
        $this->assertEquals(["feide:example.org" => "Test User"], $user2->name);
        $this->assertEquals(["feide:example.org" => "test.user@example.org"], $user2->email);
        $this->assertEquals("feide:example.org", $user2->selectedsource);

    }
}
