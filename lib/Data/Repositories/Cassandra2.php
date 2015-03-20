<?php


/*
 * A cassandra repository implementation
 * Using this cassandra binding library: 
 * 	https://github.com/duoshuo/php-cassandra
 */

namespace FeideConnect\Data\Repositories;

use FeideConnect\Logger;
use FeideConnect\Data\Models;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Exceptions\StorageException;

use Cassandra\Type\Uuid;
use Cassandra\Type\Timestamp;
use Cassandra\Type\Blob;
use Cassandra\Type\CollectionMap;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;



// use duoshuo\php-cassandra\Connection;

// use \Exception;

class Cassandra2 extends \FeideConnect\Data\Repository {

	protected $db;

	function __construct() {

		$config = \FeideConnect\Config::getValue('storage');
		
		if (empty($config['keyspace'])) throw new FeideConnectException('Required config not set');
		if (empty($config['nodes'])) throw new FeideConnectException('Required config not set');

		// $this->db = new \evseevnn\Cassandra\Database($config['nodes'], $config['keyspace']);
		$this->db = new \Cassandra\Connection($config['nodes'], $config['keyspace']);
		$this->db->connect();

		// Set consistency level for farther requests (default is CONSISTENCY_ONE)
		// $connection->setConsistency(Request::CONSISTENCY_QUORUM);

	}

	protected function getTTLskew(\FeideConnect\Data\Types\Timestamp $validuntil) {
		
		if ($validuntil->inPast()) {
			throw new StorageException('Invalid timestamp (in the past) for expiration provided');
		}
		$skew = \FeideConnect\Config::getValue('storage.ttlskew', 60); 
		// echo "ABOUT TO WIPE IN " . ($validuntil->getInSeconds() + $skew) . "\n\n"; exit;
		return $validuntil->getInSeconds() + $skew;
	}

	protected static function generateInsert($table, $data, $ttl = null) {

		$keys = array_keys($data);

		$keystr = join(', ', array_map(function($a) {
			return '"' . $a . '"';
		}, $keys));
		$keyval = join(', ', array_map(function($a) {
			return ':' . $a . '';
		}, $keys));

		$ttltext = '';
		if ($ttl !== null && is_int($ttl)) {
			$ttltext = ' USING TTL ' . $ttl;
		}
		


		$query = 'INSERT INTO "' . $table . '" (' . $keystr . ') VALUES (' . $keyval . ')' . $ttltext . "\n\n";

		return $query;
	}


	/**
	 * Insert data into cassandra with wrapper
	 * 
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @param  string $title [description]
	 * @return [type]        [description]
	 */
	protected function execute($query, $data, $title = '') {


		Logger::debug('Cassandra insert (' . $title . ')', array(
			'query' => $query,
			'params' => $data
		));

		try {

			// $this->db->beginBatch();
			$this->db->querySync($query, $data,
				\Cassandra\Request\Request::CONSISTENCY_QUORUM,
			    [
					'names_for_values' => true
			    ]);
			// $result = $this->db->applyBatch();


		} catch (StorageException $e) {
			// TODO catch only Cassandras own exception type

			
			Logger::error('Cassandra insert (' . $title . ') FAILED ' . $e->getMessage(), array(
				'query' => $query,
				'params' => $data,
				// 'message' => $e->getMessage(),
			));

			// print_r($e); 

			throw new StorageException('Error executing operation on storage: ' . $title);

		}



	}


	/**
	 * Query data from cassandra, within wrapper. This function deals with errors, etc.
	 * 
	 * @param  [type]  $query    [description]
	 * @param  [type]  $params   [description]
	 * @param  string  $title    [description]
	 * @param  [type]  $model    [description]
	 * @param  boolean $multiple [description]
	 * @return [type]            [description]
	 */
	protected function query($query, $params, $title = '', $model = null, $multiple = false) {

		Logger::debug('Cassandra query (' . $title . ')', array(
			'query' => $query,
			'params' => $params
		));

		$data = null;

		try {	
			
			$response = $this->db->querySync($query, $params,
				\Cassandra\Request\Request::CONSISTENCY_QUORUM,
			    [
					'names_for_values' => true
			    ]);
			$data = $response->fetchAll();

		} catch (\Exception $e) {
			// TODO catch only Cassandras own exception type

			Logger::error('Cassandra query (' . $title . ') FAILED ' . $e->getMessage(), array(
				'query' => $query,
				'params' => $params,
				// 'message' => $e->getMessage(),
			));
			// echo "QUERY IS ". $query;
			// print_r($query);
			throw new StorageException('Error quering storage: ' . $title . " " . $e->getMessage());
		}

		if ($data === null) {
			return null;
		}

		// $data should be an SplFixedArray
		assert('$data instanceof SplFixedArray');

		if ($multiple) {

			$res = [];
			foreach($data AS $i) {
				if ($model !== null) {
					$res[] = $this->getObject($model, $i);
				} else {
					$res[] = $i;
				}
			}
			return $res;

		} else {

			// echo var_export($data, true); exit;

			if ($data->count() < 1) {
				return null;
			}

			if ($model !== null) {
					// echo "Multiple objects first is " . var_export($data[0]); exit;
				return $this->getObject($model, $data[0]);
			} else {
				return $data[0];
			}
		}

	}

	function getObject($model, $data) {
		$transformed = [];
		foreach($data AS $k => $v) {
			$transformed[$k] = $model::fromDB($k, $v);
		}
		
		return new $model($transformed);
	}






	/* 
	 * --- Database handling of the 'users' and 'userid_sec' column family
	 */
	function saveUser(Models\User $user) {
		$data = $user->getStorableArray();
		$query = self::generateInsert('users', $data);
		$this->execute($query, $data, __FUNCTION__);

		// We also need to populate the userid_sec table with the corresponding secondary keys.
		// TODO; Make this a transaction
		if (isset($user->userid_sec) && is_array($user->userid_sec)) {
			foreach($user->userid_sec AS $sec) {
				$this->addUserIDsec($user->userid, $sec);
			}
		}
	}

	function updateUserInfo(Models\User $user, $sourceID, $props = []) {


		$assignments = [];
		$assignments[] = 'updated = :updated';

		$userinfo = $user->getUserInfo($sourceID);
		$params = [
			'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
			'userid' => new Uuid($user->userid),
			'name' => $userinfo['name'],
			'email' => $userinfo['email'],
		];


		foreach($props AS $key) {

			if ($userinfo[$key] !== null) {
				$assignments[] = $key . '[\'' . $sourceID .'\'] = :' . $key; 
			}

		}

		$query = 'UPDATE "users" SET ' . join(', ', $assignments) . " WHERE userid = :userid";
		// echo "QUERY IS " . $query; 
		// print_r($params);

		$this->execute($query, $params, __FUNCTION__);

	}

	function updateProfilePhoto(Models\User $user, $sourceID) {


		$userinfo = $user->getUserInfo($sourceID);

		if (empty($userinfo['profilephoto'])) throw new Exception('Missing profilephoto');
		if (empty($userinfo['profilephotohash'])) throw new Exception('Missing profilephotohash');

		$query = 'UPDATE "users" SET updated = :updated, ' . 
			'profilephoto[\'' . $sourceID  . '\'] = :profilephoto, ' . 
			'profilephotohash[\'' . $sourceID  . '\'] = :profilephotohash ' . 
			'WHERE userid = :userid';
		$params = [
			'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
			'userid' => new Uuid($user->userid),
			'profilephoto' => new Blob($userinfo['profilephoto']),
			'profilephotohash' => $userinfo['profilephotohash'],
		];
		$this->execute($query, $params, __FUNCTION__);

	}



	function updateClientLogo(Models\Client $client, $logo) {

		$query = 'UPDATE "clients" SET logo = :logo, updated = :updated ' . 
			'WHERE id = :id';

		$params = [
			'id' => $client->id,
			'logo' => $logo,
			'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
		];
		$this->execute($query, $params, __FUNCTION__);

	}


	function updateClientScopes(Models\Client $client, $scopes_requested, $scopes) {

		$query = 'UPDATE "clients" SET ' . 
			'scopes = :scopes, scopes_requested = :scopes_requested, updated = :updated ' . 
			'WHERE id = :id';

		$params = [
			'id' => $client->id,
			'scopes' => $scopes,
			'scopes_requested' => $scopes_requested,
			'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
		];
		$this->execute($query, $params, __FUNCTION__);

	}


	function addUserIDsec($userid, $userid_sec) {
		$query  = 'UPDATE "users" SET userid_sec = userid_sec + :useridsec  WHERE userid = :userid';
		$query2 = 'INSERT INTO "userid_sec" (userid_sec, userid) VALUES (:useridsec, :userid)';
		$this->execute($query, [
			'userid' => new Uuid($userid),
			'useridsec' => new CollectionSet([$userid_sec], Base::ASCII)
		], __FUNCTION__);

		$this->execute($query2, [
			'useridsec' => $userid_sec,
			'userid' => new Uuid($userid),
		], __FUNCTION__);
	}

	function removeUserIDsec($userid, $userid_sec) {

		$query  = 'UPDATE "users" SET userid_sec = userid_sec - :useridsec  WHERE userid = :userid';
		$query2 = 'DELETE FROM "userid_sec" WHERE (userid_sec = :useridsec)';

		$this->execute($query, [
			'userid' => $userid,
			'useridsec' => [$userid_sec]
		], __FUNCTION__);

		$this->execute($query2, [
			'useridsec' => $userid_sec,
			'userid' => $userid,
		], __FUNCTION__);

	}

	function deleteUser(Models\User $user) {
		$query1 = 'DELETE FROM "users" WHERE (userid = :userid)';
		$query2 = 'DELETE FROM "userid_sec" WHERE (userid_sec IN :userid_secs) ';

		$this->execute($query1, ['userid' => new Uuid($user->userid)], __FUNCTION__);
		if (!empty($user->userid_sec)) {
			$this->execute($query2, [
				'useridsec' => new CollectionSet($user->userid_sec, Base::ASCII)
			], __FUNCTION__);
		}

	}

	function getUserByUserID($userid) {
		//$query = 'SELECT userid, created, email, name, profilephoto, userid_sec, userid_sec_seen, selectedsource FROM "users" WHERE "userid" = :userid';
		$query = 'SELECT * FROM "users" WHERE "userid" = :userid';
		$params = ['userid' => new Uuid($userid)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\User', false);
	}




	function getUserByUserIDsec($useridsec) {
		$query = 'SELECT * FROM "userid_sec" WHERE "userid_sec" = :userid_sec';
		$params = ['userid_sec' => $useridsec];
		$result = $this->query($query, $params, __FUNCTION__, null, false);
		if ($result === null) return null;

		return $this->getUserByUserID($result['userid']);
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
		$params = ['userid_sec' => new CollectionSet($useridsec, Base::ASCII)];

		// echo var_export($params, true);

		$data = $this->query($query, $params, __FUNCTION__, null, true);
		if (empty($data)) return null;


		// Helper function to get an array with a list of userids
		$func = function ($item) {
			return $item['userid'];
		};
		$userids = array_unique(array_map($func, $data));



		// echo '<pre>About to lookup userids';
		// print_r($userids);

		// Retrieve the userids from the user table...
		$query2 = 'SELECT userid,created,email,name,profilephoto,profilephotohash,userid_sec,userid_sec_seen,selectedsource FROM "users" WHERE ("userid" IN :userids)';
		$params2 = ['userids' => new CollectionSet($userids, Base::UUID)];
		// echo var_export($params2, true);
		$res = $this->query($query2, $params2, __FUNCTION__, 'FeideConnect\Data\Models\User', true);
		
		if (empty($res)) return null;
		return $res;
	}

	function getUsers($count = 100) {
		$query = 'SELECT * FROM "users" LIMIT :count';
		$params = ['count' => $count];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\User', true);
	}

	function getUserIDsecList($count = 100) {
		$query = 'SELECT * FROM "userid_sec" LIMIT :count';
		$params = ['count' => $count];
		return $this->query($query, $params, __FUNCTION__, null, true);
	}



	/* 
	 * --- Database handling of the 'apigk' column family
	 * 
	 * TABLE feideconnect.apigk :
     *   id text PRIMARY KEY,
     *   created timestamp,
     *   descr text,
     *   endpoints list<text>,
     *   expose text,
     *   httpscertpinned text,
     *   logo blob,
     *   name text,
     *   owner uuid,
     *   requireuser boolean,
     *   scopedef text,
     *   status set<text>,
     *   trust text,
     *   updated timestamp
	 */
	function getAPIGK( $id) {
		$query = 'SELECT id, descr, endpoints, expose, httpscertpinned, name, owner, requireuser, scopedef, status, created, updated FROM "apigk" WHERE "id" = :id';
		$params = ['id' => $id];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\APIGK', false);
	}



	/* 
	 * --- Database handling of the 'client' column family
	 */
	function getClient( $id) {
		$query = 'SELECT id, client_secret, created, descr, name, owner, logo, redirect_uri, scopes, scopes_requested, status, type, updated FROM "clients" WHERE "id" = :id';
		$params = ['id' => new Uuid($id)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Client', false);
	}

	function saveClient(Models\Client $client) {
		$data = $client->getStorableArray();
		$query = self::generateInsert('clients', $data);
		// echo $query . "\n\n";
		// echo var_export($data, true); exit;
		$this->execute($query, $data, __FUNCTION__);
	}

	function getClients($count = 100) {
		$query = 'SELECT * FROM "clients" LIMIT :count';
		$params = ['count' => $count];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Client', true);
	}

	function removeClient(Models\Client $client) {
		$query = 'DELETE FROM "clients" WHERE "id" = :id';
		$params = ['id' => new Uuid($client->id)];
		$this->execute($query, $params, __FUNCTION__);
	}

	function getClientsByOwner(Models\User $owner) {
		$query = 'SELECT * FROM "clients" WHERE "owner" = :owner';
		$params = ['owner' => new Uuid($owner->userid)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Client', true);
	}






	/* 
	 * --- Database handling of the 'accesstoken' column family
	 */
	function getAccessToken($accesstoken) {
		$query = 'SELECT * FROM "oauth_tokens" WHERE "access_token" = :access_token';
		$params = ['access_token' => new Uuid($accesstoken)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\AccessToken', false);
	}

	function getAccessTokens($userid, $clientid) {

		$query = 'SELECT * FROM "oauth_tokens" WHERE "userid" = :userid AND "clientid" = :clientid ALLOW FILTERING';
		$params = ['userid' => new Uuid($userid), 'clientid' => new Uuid($clientid)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\AccessToken', true);
	}

	function saveToken(Models\AccessToken $token) {
		$data = $token->getStorableArray();

		if (!($token->validuntil instanceof \FeideConnect\Data\Types\Timestamp)) {
			throw new StorageException('Could not store an access token without a properly set valid until timestamp.');
		}
		$query = self::generateInsert('oauth_tokens', $data, $this->getTTLskew($token->validuntil));
		$this->execute($query, $data, __FUNCTION__);

		$query = 'UPDATE "clients_counters" SET count_tokens = count_tokens + 1 WHERE "id" = :id';
		$params = ['id' => new Uuid($token->clientid)];
		$this->execute($query, $params, __FUNCTION__);
	}
	function removeAccessToken(Models\AccessToken $token) {
		$query = 'DELETE FROM "oauth_tokens" WHERE "access_token" = :access_token';
		$params = ['access_token' => new Uuid($token->access_token)];
		$this->execute($query, $params, __FUNCTION__);
	}
	


	/* 
	 * --- Database handling of the 'oauth_authorizations' column family
	 */
	function getAuthorization($userid, $clientid) {
		$query = 'SELECT * FROM "oauth_authorizations" WHERE "userid" = :userid AND "clientid" = :clientid';
		$params = ['userid' => new Uuid($userid), 'clientid' => new Uuid($clientid)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Authorization', false);
	}

	function getAuthorizationsByUser(Models\User $user) {
		$query = 'SELECT * FROM "oauth_authorizations" WHERE "userid" = :userid';
		$params = ['userid' => new Uuid($user->userid)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Authorization', true);
	}

	function saveAuthorization(Models\Authorization $authorization) {
		$data = $authorization->getStorableArray();
		$query = self::generateInsert('oauth_authorizations', $data);
		$this->execute($query, $data, __FUNCTION__);

		$query = 'UPDATE "clients_counters" SET count_users = count_users + 1 WHERE "id" = :id';
		$params = ['id' => new Uuid($authorization->clientid)];
		return $this->execute($query, $params, __FUNCTION__);
	}

	function removeAuthorizations($userid, $clientid) {
		$query = 'DELETE FROM "oauth_authorizations" WHERE "userid" = :userid AND "clientid" = :clientid';
		$params = [
			'userid' => new Uuid($userid),
			'clientid' => new Uuid($clientid)
		];
		$this->execute($query, $params, __FUNCTION__);

		$query = 'UPDATE "clients_counters" SET count_users = count_users - 1 WHERE "id" = :id';
		$params = ['id' => new Uuid($authorization->clientid)];
		return $this->execute($query, $params, __FUNCTION__);

	}

	

	/* 
	 * --- Database handling of the 'oauth_codes' column family
	 */
	function getAuthorizationCode($code) {
		$query = 'SELECT * FROM "oauth_codes" WHERE "code" = :code';
		$params = ['code' => new Uuid($code)];
		return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\AuthorizationCode', false);
	}

	function saveAuthorizationCode(Models\AuthorizationCode $code) {
		$data = $code->getStorableArray();

		if (!($code->validuntil instanceof \FeideConnect\Data\Types\Timestamp)) {
			throw new StorageException('Could not store an authorization code without a properly set valid until timestamp.');
		}
		$query = self::generateInsert('oauth_codes', $data, $this->getTTLskew($code->validuntil));
		$this->execute($query, $data, __FUNCTION__);
	}

	function removeAuthorizationCode(Models\AuthorizationCode $code) {
		$query = 'DELETE FROM "oauth_codes" WHERE "code" = :code';
		$params = ['code' => new Uuid($code->code)];
		$this->execute($query, $params, __FUNCTION__);
	}





}






