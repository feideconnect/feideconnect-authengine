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
//		echo "\nUser list: \n\n"; var_dump($u2);
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

	// public function testUsers() {
	// 	// return;
	// 	$user = new Models\User();


	// 	// Test all cassandra db functions
	// 	// Test all Model\User functions.


	// 	$uuid = \FeideConnect\Data\Model::genUUID();
	// 	$feideid = 'feide:andreas@uninett.no';
	// 	$mail = 'mail:andreas.solberg@uninett.no';

	// 	$user->userid = $uuid;

	// 	$user->setUserInfo('feide:uninett.no','Andreas Åkre Solberg', 'andreas.solberg@uninett.no');
	// 	$user->selectedsource = 'feide:uninett.no';

	// 	$user->userid_sec = [$feideid, $mail];
		
	// 	$this->assertTrue($user->userid === $uuid, 'UUID is set and kept');

	// 	$this->db->saveUser($user);


	// 	$u2 = $this->db->getUserByUserID($uuid);

	// 	$this->assertTrue($u2 !== null, 'Should return match when looking up stored user object');
	// 	$this->assertInstanceOf('FeideConnect\Data\Models\User', $u2, 'Should return an user object of correct object type');

	// 	$this->assertEquals($user->userid, $u2->userid, 'UserID should match in returning entry');
	// 	$this->assertTrue(count($u2->userid_sec) === 2, 'User db entry should contain 2 userid sec keys');

	// 	// $u2->debug();
	// 	// print_r(json_encode($u2->getUserInfo(), JSON_PRETTY_PRINT));




	// 	$u3 = $this->db->getUserByUserIDsec($feideid);
	// 	$u4 = $this->db->getUserByUserIDsec($mail);


	// 	$this->assertTrue($u3 !== null, 'Should return match when looking up by usersecid ' . $feideid);
	// 	$this->assertInstanceOf('FeideConnect\Data\Models\User', $u3, 'Should return an user object of correct object type');
	// 	$this->assertTrue($u4 !== null, 'Should return match when looking up by mail ' . $mail);
	// 	$this->assertInstanceOf('FeideConnect\Data\Models\User', $u4, 'Should return an user object of correct object type');

	// 	$this->assertEquals($user->userid, $u3->userid, 'UserID should match in returning entry');
	// 	$this->assertEquals($user->userid, $u4->userid, 'UserID should match in returning entry');


	// 	$u5 = $this->db->getUserByUserID('919d0c79-c294-46df-83d3-1416dedab56b');
	// 	$u6 = $this->db->getUserByUserIDsec('dummy:919d0c79-c294-46df-83d3-1416dedab56b');

	// 	$this->assertEquals($u5, null, 'Should return null when not finding user by key');
	// 	$this->assertEquals($u6, null, 'Should return null when not finding user by seckey');



	// 	$ulist = $this->db->getUserByUserIDsecList(['feide:andreas@uninett.no', 'mail:foo']);
	// 	$this->assertTrue(count($ulist) >= 1, 'Should return at least one result with getUserByUserIDsecList()');





	// 	/*
	// 	 * TODO: Fix. deleteUser does not work. Probably some issues with cassandra v2.0.11 running in CI

	// 	$this->db->deleteUser($user);


	// 	$u7 = $this->db->getUserByUserID($uuid);
	// 	$this->assertTrue($u7 === null, 'Should not find user after user is deleted by userid');





	// 	$u8 = $this->db->getUserByUserIDsec($feideid);
	// 	$u9 = $this->db->getUserByUserIDsec($mail);

	// 	$this->assertTrue($u8 === null, 'Should not find user after user is deleted by feideid');
	// 	$this->assertTrue($u9 === null, 'Should not find user after user is deleted by mail');

	// 	 */
	// }



	// /*
	//  * Performs some tests on cassandra user related functions...
	//  */
	// public function testUsers2() {



	// 	$uuid = \FeideConnect\Data\Model::genUUID();
	// 	$feideid = 'feide:test@test.org';
	// 	$mail = 'tester.test@test@org';


	// 	$photo = file_get_contents(dirname(dirname(__FILE__)) . '/www/static/media/default-profile.jpg');
	// 	$photohash = sha1($photo);
	// 	// echo $photo;
	// 	// echo $photohash;
	// 	// exit;

	// 	$user = new \FeideConnect\Data\Models\User();
	// 	$user->userid = $uuid;
	// 	$user->setUserInfo('test:test', 'Tester Test', $mail, $photo, $photohash);
	// 	$user->selectedsource = 'test:test';
		


	// 	$userinfo = $user->getBasicUserInfo(true);
	// 	// print_r($userinfo);
	// 	$this->assertTrue($userinfo['email'] === $mail, 'Check that mail is set correctly');

	// 	$this->db->saveUser($user);
	// 	$user->ensureProfileAccess(true);

	// 	$this->db->updateProfilePhoto($user, 'test:test');

	// 	$this->db->updateUserInfo($user, 'test:test', ['name', 'email']);

	// 	$this->db->addUserIDsec($uuid, $feideid);

	// 	// $this->db->deleteUser($user);




	// }




}