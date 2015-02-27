<?php


namespace FeideConnect;

use FeideConnect\Authentication\UserID;
use FeideConnect\Data\StorageProvider;

class CLI {

	protected $storage;
	function __construct() {
		$this->storage = StorageProvider::getStorage();
	}


	function getUser($userid) {


		$this->header("Fetch information about user " . $userid);

		$uid = new UserID($userid);

		$uuid = null;
		if ($uid->prefix === "uuid") {
			$this->info("Looking up by primary key " . $uid->local);
			$user = $this->storage->getUserByUserID($uid->local);
		} else {
			$this->info("Looking up by secondary key");
			$user = $this->storage->getUserByUserIDsec($userid);
		}

		if ($user === null) {
			$this->info("No user found");
		}

		$info = $user->getAsArray();
		$info["profilephoto"] = '----';

		$this->oneEntry($info);

		$this->info();
		$this->info("looking up reverse entries");

		$cql = '';

		foreach($user->userid_sec AS $k) {

			$u = $this->storage->getUserByUserIDsec($k);

			if ($u === null) {
				$this->info("No reverse lookup for " . $k);

				$cql .= " INSERT INTO userid_sec (userid, userid_sec) VALUES (" . $user->userid . ", '" . $k . "');\n";

			} else if ($u->userid === $user->userid) {
				$this->info("OK reverse for " . $k);
			} else {

				$this->info("ERROR in reverse for " . $k . " userid found was " . $u->userid);
			}

		}

		echo "\n\n" . $cql . "\n\n";

		return $user;

	}


	function getAPIGK($apigkid) {

		$this->header("Fetch information about API Gatekeeper " . $apigkid);
		$apigk = $this->storage->getAPIGK($apigkid);
		$this->oneEntry($apigk);


	}

	function getClient($clientid) {

		$this->header("Fetch information about client " . $clientid);
		$client = $this->storage->getClient($clientid);
		$this->oneEntry($client);
		return $client;

	}

	function setScopes($client, $scopes_requested, $scopes) {
		$this->storage->updateClientScopes($client, $scopes_requested, $scopes );
		return $this->getClient($client->id);
	}

	function getToken($token) {

		$this->header("Fetch information about token " . $token);

		$token = $this->storage->getAccessToken($token);
		$this->oneEntry($token);

	}


	function deleteUser($user) {
		$this->header("Deleting user " . $user->userid);
		$this->storage->deleteUser($user);
	}


	function getUsers() {

		$users = $this->storage->getUsers();
		$this->header("List users");
		$c = 0;
		foreach($users AS $user) {

			$uinfo = $user->getBasicUserInfo(true, true);
			$uinfo["c"] = ++$c;
			echo $this->l($uinfo, [
				"c" => ["%3d", "red"],
				"userid" => ["%38s", "black", 38 ],
				"name" => ["%-30s", "cyan", 25],
				"email" => ["%-22s", "blue", 20],
				"userid_sec" => ["%s", "purple"],
			]);

		}



	}

	public function getClients() {

		$clients = $this->storage->getClients();
		$this->header("List clients");
		$c = 0;
		foreach($clients AS $client) {

			$cinfo = $client->getAsArray();
			$cinfo["c"] = ++$c;

			$this->oneEntry($c);

			echo $this->l($cinfo, [
				"c" => ["%3d", "red"],

				"name" => ["%30s", "green", 30],
				"id" => ["%38s", "black", 38 ],
				"redirect_uri" => ["%-45s", "blue", 45],
				"scopes" => ["%-90s", "purple", 90],
			]);

		}


	}


	public function l($data, $fmt) {

		$s = '';


		foreach($fmt AS $k => $f) {
			$max = (isset($f[2]) ? $f[2] : 100);
			if (!isset($data[$k])) {
				$dv = '_';
			} else if (!is_string($data[$k])) {
				$dv = mb_substr(json_encode($data[$k]), 0, $max, "UTF-8");
			} else {
				$dv = mb_substr($data[$k], 0, $max, "UTF-8");
			}
			$s .= $this->colored(sprintf($f[0], $dv), $f[1]) . " ";
		}
		$s .= "\n";
		return $s;

	}

	function oneEntry($data) {

		foreach($data AS $k => $v) {
			if (!is_string($v)) {
				$dv = json_encode($v);	
			} else {
				$dv = $v;
			}
			
			echo 
				$this->colored(sprintf("%20s", $k), "green")  . " " .
				sprintf("%s", $dv) . "\n";
		}

	} 

	function info($str = '') {
		echo "  " . $str . "\n";
	}

	function header($str) {
		echo "\n\n" . $str . "\n\n";
	}

	function colored($str, $c) {
		$colors = [
			"black" => '30',
			"red" => '31',
			"green" => '32',
			"brown" => '33',
			"blue" => '34',
			"purple" => '35',
			"cyan" => '36',
			"white" => '37'
		];
		return "\033[". $colors[$c] ."m" . $str . "\033[30m";
	}

}

