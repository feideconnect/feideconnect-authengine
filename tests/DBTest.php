<?php


namespace tests;

use FeideConnect\Data\Repositories;
use FeideConnect\Data\Models;


class DBTest extends \PHPUnit_Framework_TestCase {


	protected $db;

	function __construct() {

		$config = json_decode(file_get_contents(__DIR__ . '/../etc/config.travis.json'), true);

		$this->db = new Repositories\Cassandra($config);

	}


    public function testUsers() {
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


		$this->db->deleteUser($user);


		$u7 = $this->db->getUserByUserID($uuid);
		$this->assertTrue($u7 === null, 'Should not find user after user is deleted by userid');

		$u8 = $this->db->getUserByUserIDsec($feideid);
		$u9 = $this->db->getUserByUserIDsec($mail);
		$this->assertTrue($u8 === null, 'Should not find user after user is deleted by feideid');
		$this->assertTrue($u9 === null, 'Should not find user after user is deleted by mail');



    }


}