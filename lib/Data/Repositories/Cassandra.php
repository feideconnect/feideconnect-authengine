<?php


namespace FeideConnect\Data\Repositories;

use FeideConnect\Logger;
use \Exception;

class Cassandra extends \FeideConnect\Data\Repository {

	protected $db;

	function __construct() {

		$config = \FeideConnect\Config::getValue('storage');
		
		if (empty($config['keyspace'])) throw new Exception('Required config not set');
		if (empty($config['nodes'])) throw new Exception('Required config not set');

		$this->db = new \evseevnn\Cassandra\Database($config['nodes'], $config['keyspace']);
		$this->db->connect();

	}

	protected static function generateInsert($table, $data) {

		$keys = array_keys($data);

		$keystr = join(', ', array_map(function($a) {
			return '"' . $a . '"';
		}, $keys));
		$keyval = join(', ', array_map(function($a) {
			return ':' . $a . '';
		}, $keys));
		$query = 'INSERT INTO "' . $table . '" (' . $keystr . ') VALUES (' . $keyval . ')' . "\n\n";

		return $query;
	}


	/* 
	 * --- Database handling of the 'accesstoken' column family
	 */
	function getAccessToken($accesstoken) {
		$data = $this->db->query('SELECT * FROM "accesstoken" WHERE "accesstoken" = :accesstoken', 
			['accesstoken' => $accesstoken]);
		if (empty($data)) return null;
		return new \FeideConnect\Data\Models\AccessToken($this, $data[0]);
	}

	function saveToken(\FeideConnect\Data\Models\AccessToken $token) {

		$data = $token->getAsArray();
		$query = self::generateInsert('oauth_tokens', $data);
		$this->db->beginBatch();
		$this->db->query($query, $data);
		$result = $this->db->applyBatch();
		
	}
	

	/* 
	 * --- Database handling of the 'users' and 'userid_sec' column family
	 */
	function saveUser(\FeideConnect\Data\Models\User $user) {


		$data = $user->getAsArray();
		$query = self::generateInsert('users', $data);

		// print_r($data);
		// echo $query; exit;

		// echo "ABOUT TO INSERT DATA\n" . var_export($data, true) . "\n\n" . $query . "\n-------\n"; return;
		// $this->db->beginBatch();
		// echo "about to insert user";
		$this->db->query($query, $data);

		if (is_array($user->userid_sec)) {
			foreach($user->userid_sec AS $sec) {
				// echo "adding userid sec " . $user->userid . " " . $sec . "\n";
				$this->addUserIDsec($user->userid, $sec);
			}
		}
		
		// $result = $this->db->applyBatch();

		// print_r($result);
	}


	function addUserIDsec($userid, $userid_sec) {


		$query  = 'UPDATE "users" SET userid_sec = userid_sec + :useridsec  WHERE userid = :userid';
		$query2 = 'INSERT INTO "userid_sec" (userid_sec, userid) VALUES (:useridsec, :userid)';

		// $this->db->beginBatch();
		$this->db->query($query, array(
			'userid' => $userid,
			'useridsec' => array($userid_sec)
		));
		$this->db->query($query2, array(
			'useridsec' => $userid_sec,
			'userid' => $userid,
		));

		// print_r(array(
		// 	'useridsec' => $userid_sec,
		// 	'userid' => $userid,
		// ));
		// $result = $this->db->applyBatch();

		// echo "Done. Results are "; print_r($result); echo "\n\n";

	}

	function removeUserIDsec($userid, $userid_sec) {

		$query  = 'UPDATE "users" SET userid_sec = userid_sec - :useridsec  WHERE userid = :userid';
		$query2 = 'DELETE FROM "userid_sec" WHERE (userid_sec = :useridsec)';

		$this->db->beginBatch();
		$this->db->query($query, array(
			'userid' => $userid,
			'useridsec' => array($userid_sec)
		));
		$this->db->query($query2, array(
			'useridsec' => $userid_sec,
			// 'userid' => $userid,
		));
		$result = $this->db->applyBatch();
	}

	function deleteUser(\FeideConnect\Data\Models\User $user) {
		$query1 = 'DELETE FROM "users" WHERE (userid = :userid)';
		$query2 = 'DELETE FROM "userid_sec" WHERE (userid_sec IN :userid_secs) ';

		// $this->db->beginBatch();
		$this->db->query($query1, ['userid' => $user->userid]);
		if (!empty($user->userid_sec)) {
			$this->db->query($query2, ['useridsec' => $user->userid_sec]);
		}
		// $result = $this->db->applyBatch();

	}

	function getUserByUserID($userid) {

		//$query = 'SELECT userid, created, email, name, profilephoto, userid_sec, userid_sec_seen, selectedsource FROM "users" WHERE "userid" = :userid';
		$query = 'SELECT * FROM "users" WHERE "userid" = :userid';
		$params = ['userid' => $userid];

		Logger::debug('Query cassandra getUserByUserID()', array(
			'query' => $query,
			'params' => $params
		));

		$data = $this->db->query($query, $params);
		Logger::debug('Query ----');
		if (empty($data)) return null;
		return new \FeideConnect\Data\Models\User($this, $data[0]);
	}




	function getUserByUserIDsec($useridsec) {

		$query = 'SELECT * FROM "userid_sec" WHERE "userid_sec" = :userid_sec';
		$params = ['userid_sec' => $useridsec];

		Logger::debug('Query cassandra getUserByUserIDsec()', array(
			'query' => $query,
			'params' => $params
		));


		$data = $this->db->query($query, $params);
		Logger::debug('Query ----');

		if (empty($data)) return null;
		return $this->getUserByUserID($data[0]['userid']);
	}


	/**
	 * Lookup a list of secondary userids, and get returned a list of users.
	 * Zero, one or multiple users.
	 * If no users found, returns null.
	 * If one or more users, returns an array.
	 * 
	 * @param  [type] $useridsec [description]
	 * @return [type]            [description]
	 */
	function getUserByUserIDsecList($useridsec) {

		$query = 'SELECT * FROM "userid_sec" WHERE "userid_sec" IN :userid_sec';
		$params = ['userid_sec' => $useridsec];

		Logger::debug('Query cassandra getUserByUserIDsecList()', array(
			'query' => $query,
			'params' => $params
		));

		$data = $this->db->query($query, $params);
		Logger::debug('Query ----');


		if (empty($data)) return null;

		$func = function ($a) {
			// echo "picking user id " . var_export($a, true);
			return $a['userid'];
		};
		$userids = array_unique(array_map($func, $data));

		// header('content-type: text/plain');
		// echo "query multiple" ; print_r($useridsec); 
		// echo "\n\nresult is \n\n"; print_r($data);
		// echo "\n\nresult is \n\n"; print_r($userids);
		// exit;

		$users = array();
		foreach($userids AS $userid) {
			$user = $this->getUserByUserID($userid);
			if ($user !== null) {
				$users[] = $user;
			}
		}
		if (empty($users)) {
			Logger::warning('Did get a match on secondary userids, but none of them resolved to real user objects', array(
				'useridsec' => $useridsec,
				'userids' => $userids
			));
			return null;
		}
		return $users;
	}

	function getUsers($count = 100) {

		$users = array();

		$data = $this->db->query('SELECT * FROM "users" LIMIT :count', ['count' => $count]);
		if (empty($data)) return null;

		foreach($data AS $u) {
			$users[] = new \FeideConnect\Data\Models\User($this, $u);
		}
		return $users;
	}



	/* 
	 * --- Database handling of the 'client' column family
	 */
	function getClient( $id) {
		$data = $this->db->query('SELECT * FROM "clients" WHERE "id" = :id', 
			['id' => $id]);
		if (empty($data)) return null;
		return new \FeideConnect\Data\Models\Client($this, $data[0]);
	}

	function saveClient(\FeideConnect\Data\Models\Client $client) {


		$data = $client->getAsArray();
		$query = self::generateInsert('clients', $data);

		// echo $query; exit;

		$this->db->beginBatch();
		$this->db->query($query, $data);
		$result = $this->db->applyBatch();

		// print_r($result);
	}

	function getClients($count = 100) {

		$clients = array();

		$data = $this->db->query('SELECT * FROM "clients" LIMIT :count', ['count' => $count]);
		if (empty($data)) return null;

		foreach($data AS $u) {
			$clients[] = new \FeideConnect\Data\Models\Client($this, $u);
		}
		return $clients;
	}



	function getClientsByOwner( $owner) {
		$data = $this->db->query('SELECT * FROM "clients" WHERE "owner" = :owner', 
			['owner' => $owner]);
		if (empty($data)) return array();
		$a = array();
		foreach($data AS $item) {
			$a[] = new \FeideConnect\Data\Models\Client($this, $item);
		}
		return $a;
	}


}






