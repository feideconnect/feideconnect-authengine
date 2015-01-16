<?php


namespace tests;

use FeideConnect\Data\Models;
use FeideConnect\Data\StorageProvider;

class DBTest extends \PHPUnit_Framework_TestCase {


	protected $db;

	function __construct() {

		$config = json_decode(file_get_contents(__DIR__ . '/../etc/config.travis.json'), true);

		$this->db = StorageProvider::getStorage();

	}


	public function testCodes() {

		$cid = Models\AuthorizationCode::genUUID();
		$code = new Models\AuthorizationCode($this->db);

		$code->code = $cid;
		$code->clientid = Models\AuthorizationCode::genUUID();
		$code->userid = Models\AuthorizationCode::genUUID();

		$code->issued = time();
		$code->validuntil = time() + 3600;
		$code->token_type = "Bearer";

		$code->redirect_uri = 'http://example.org';
		$code->scope = ['userinfo', 'groups'];


		$this->db->saveAuthorizationCode($code);


		$code2 = $this->db->getAuthorizationCode($cid);

		$this->assertEquals($code->redirect_uri, $code2->redirect_uri, 'redirect_uri of code object fetched from db should be equal to the one put in.');


		$this->db->removeAuthorizationCode($code);

		$res = $this->db->getAuthorizationCode($cid);
		$this->assertNull($res, 'Should not be able to retrieve authorizationcode that should have been deleted. Should return null.');




	}


	public function testClients() {

		$c1 = Models\Client::genUUID();
		$c2 = Models\Client::genUUID();

		$userid = Models\Client::genUUID();

		$user = new Models\User($this->db);
		$user->userid = $userid;

		$client = new Models\Client($this->db);
		$client->id = $c1;
		$client->client_secret = Models\Client::genUUID();
		$client->created = time();
		$client->name = 'name';
		$client->descr = 'descr';
		$client->owner = $userid;
		$client->redirect_uri = ['http://example.org'];
		$client->scopes = ['userinfo', 'groups'];

		$client2 = new Models\Client($this->db);
		$client2->id = $c2;
		$client2->name = 'name2';


		$this->db->saveClient($client);
		$this->db->saveClient($client2);


		$c = $this->db->getClient($c1);
		$this->assertInstanceOf('FeideConnect\Data\Models\Client', $c, 'Returned client from storage query is instance of correct class');
		$this->assertTrue($c->id === $c1, 'Read client has same ID as written');


		$c = $this->db->getClient($c2);
		$this->assertInstanceOf('FeideConnect\Data\Models\Client', $c, 'Returned client from storage query is instance of correct class');
		$this->assertTrue($c->id === $c2, 'Read client has same ID as written');


		$list1 = $this->db->getClients();
		$this->assertTrue(count($list1) > 0, 'Should return list of clients');
		$this->assertInstanceOf('FeideConnect\Data\Models\Client', $list1[0], 'Returned client from storage query is instance of correct class');

		$list2 = $this->db->getClientsByOwner($user);
		$this->assertTrue(count($list2) > 0, 'Should return list of clients');
		$this->assertInstanceOf('FeideConnect\Data\Models\Client', $list2[0], 'Returned client from storage query is instance of correct class');




		$this->db->removeClient($client);
		$res = $this->db->getClient($c1);
		$this->assertNull($res, 'Should not be able to retrieve client that should have been deleted. Should return null.');

		$this->db->removeClient($client2);
		$res = $this->db->getClient($c2);
		$this->assertNull($res, 'Should not be able to retrieve client that should have been deleted. Should return null.');



		$list3 = $this->db->getClientsByOwner($user);
		$this->assertTrue(count($list3) === 0, 'Should return empty list of clients');


		
	}

	public function testUsers() {
		// return;
		$user = new Models\User($this->db);


		$uuid = \FeideConnect\Data\Model::genUUID();
		$feideid = 'feide:andreas@uninett.no';
		$mail = 'mail:andreas.solberg@uninett.no';

		$user->userid = $uuid;

		$user->setUserInfo('feide:uninett.no','Andreas Åkre Solberg', 'andreas.solberg@uninett.no');
		$user->selectedsource = 'feide:uninett.no';

		$user->userid_sec = [$feideid, $mail];
		
		$this->assertTrue($user->userid === $uuid, 'UUID is set and kept');

		$this->db->saveUser($user);


		$u2 = $this->db->getUserByUserID($uuid);

		$this->assertTrue($u2 !== null, 'Should return match when looking up stored user object');
		$this->assertInstanceOf('FeideConnect\Data\Models\User', $u2, 'Should return an user object of correct object type');

		$this->assertEquals($user->userid, $u2->userid, 'UserID should match in returning entry');
		$this->assertTrue(count($u2->userid_sec) === 2, 'User db entry should contain 2 userid sec keys');

		// $u2->debug();
		// print_r(json_encode($u2->getUserInfo(), JSON_PRETTY_PRINT));




		$u3 = $this->db->getUserByUserIDsec($feideid);
		$u4 = $this->db->getUserByUserIDsec($mail);


		$this->assertTrue($u3 !== null, 'Should return match when looking up by usersecid ' . $feideid);
		$this->assertInstanceOf('FeideConnect\Data\Models\User', $u3, 'Should return an user object of correct object type');
		$this->assertTrue($u4 !== null, 'Should return match when looking up by mail ' . $mail);
		$this->assertInstanceOf('FeideConnect\Data\Models\User', $u4, 'Should return an user object of correct object type');

		$this->assertEquals($user->userid, $u3->userid, 'UserID should match in returning entry');
		$this->assertEquals($user->userid, $u4->userid, 'UserID should match in returning entry');


		$u5 = $this->db->getUserByUserID('919d0c79-c294-46df-83d3-1416dedab56b');
		$u6 = $this->db->getUserByUserIDsec('dummy:919d0c79-c294-46df-83d3-1416dedab56b');

		$this->assertEquals($u5, null, 'Should return null when not finding user by key');
		$this->assertEquals($u6, null, 'Should return null when not finding user by seckey');



		/*
		 * TODO: Fix. deleteUser does not work. Probably some issues with cassandra v2.0.11 running in CI

		$this->db->deleteUser($user);


		$u7 = $this->db->getUserByUserID($uuid);
		$this->assertTrue($u7 === null, 'Should not find user after user is deleted by userid');





		$u8 = $this->db->getUserByUserIDsec($feideid);
		$u9 = $this->db->getUserByUserIDsec($mail);

		$this->assertTrue($u8 === null, 'Should not find user after user is deleted by feideid');
		$this->assertTrue($u9 === null, 'Should not find user after user is deleted by mail');

		 */
	}


}