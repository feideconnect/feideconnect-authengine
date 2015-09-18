<?php

namespace tests;

use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Models;

class DBHelper {


	protected $db;

	function __construct() {
		$this->db = StorageProvider::getStorage();
	}

	public function clearUsers() {
		$users = $this->db->getUsers();
		foreach ($users as $user) {
			$this->db->deleteUser($user);
		}
	}

	public function client() {
		$clientid = Models\Client::genUUID();

		$client = new Models\Client($this->db);
		$client->id = $clientid;
		$client->client_secret = Models\Client::genUUID();
		$client->created = new \FeideConnect\Data\Types\Timestamp();
		$client->name = 'name';
		$client->descr = 'descr';
		$client->owner = null;
		$client->redirect_uri = ['http://example.org'];
		$client->scopes = ['userinfo', 'groups'];
		$client->client_secret = Models\Client::genUUID();
		$this->db->saveClient($client);

		return $client;
	}

	public function user($username = null) {
		if (empty($username)) {
			$username = TESTUSER_SEC;
		}
		$user = $this->db->getUserByUserIDsec($username);
		while ($user !== null) {
			$this->db->deleteUser($user);
			$user = $this->db->getUserByUserIDsec($username);
		}
		$userid = Models\User::genUUID();
		$user = new Models\User($this->db);
		$user->userid = $userid;
		$user->userid_sec = array($username);
		$user->selectedsource = 'feide:example.org';

		$this->db->saveUser($user);
		return $user;
	}
}
