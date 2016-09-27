<?php
/*
 * A cassandra repository implementation
 * Using this cassandra binding library:
 *     https://github.com/duoshuo/php-cassandra
 */

namespace FeideConnect\Data\Repositories;

use FeideConnect\Logger;
use FeideConnect\Data\Models;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Exceptions\StorageException;

// use \Exception;

class Cassandra2 extends \FeideConnect\Data\Repository {

    protected $db;

    public function __construct() {

        $config = \FeideConnect\Config::getValue('storage');

        if (empty($config['keyspace'])) {
            throw new Exception('Required config [keyspace] not set');
        }
        if (empty($config['nodes'])) {
            throw new Exception('Required config [nodes] not set');
        }
        list($nodes, $port) = self::parseNodes($config);
        $username = getenv('CASSANDRA_USERNAME');
        $password = getenv('CASSANDRA_PASSWORD');

        $cluster = \Cassandra::cluster()
                 ->withContactPoints(implode(',', $nodes))
                 ->withPort($port)
                 ->withDefaultConsistency(\Cassandra::CONSISTENCY_LOCAL_QUORUM);
        if (!empty($username) && !empty($password)) {
            $cluster = $cluster->withCredentials($username, $password);
        }
        if (!empty($config['use_ssl']) and $config['use_ssl']) {
            $ssl = \Cassandra::ssl()
                 ->withVerifyFlags(\Cassandra::VERIFY_PEER_CERT)
                 ->withTrustedCerts('/etc/ssl/certs/cassandraca.pem')
                 ->build();
            $cluster = $cluster->withSSL($ssl);
        }
        $cluster = $cluster->build();
        $this->db = $cluster->connect($config['keyspace']);
    }

    private static function parseNodes($config) {
        $nodes = [];
        $ports = [];
        foreach ($config['nodes'] as $node) {
            if (strpos($node, ':') !== false) {
                list($node, $port) = explode(':', $node, 2);
                $port = (int)$port;
            } else {
                $port = 9042;
            }
            $nodes[] = $node;
            $ports[] = $port;
        }
        $ports = array_unique($ports);
        if (count($ports) > 1) {
            throw new Exception('The DataStax PHP driver does not allow connections to different nodes on different ports.');
        }
        $port = $ports[0];
        return [$nodes, $port];
    }

    protected function getTTLskew(\FeideConnect\Data\Types\Timestamp $validuntil) {

        if ($validuntil->inPast()) {
            throw new StorageException('Invalid timestamp (in the past) for expiration provided');
        }
        $skew = \FeideConnect\Config::getValue('storage.ttlskew', 60);
        return intval($validuntil->getInSeconds() + $skew);
    }

    /*
     * A helper function that generates a INSERT statement based upon an incomming array.
     * The $ttl parameter is optional, and may be a number of seconds before the object should expire
     */
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


        Logger::debug('Cassandra execute (' . $title . ')', array(
            'query' => $query,
            'params' => $data
        ));




        try {
            $statement = new \Cassandra\SimpleStatement($query);
            $options = new \Cassandra\ExecutionOptions([
                'arguments' => $data,
                'consistency' => \Cassandra::CONSISTENCY_QUORUM,
            ]);
            $response = $this->db->execute($statement, $options);

        } catch (StorageException $e) {
            // TODO catch only Cassandras own exception type


            Logger::error('Cassandra execute (' . $title . ') FAILED ' . $e->getMessage(), array(
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
            $statement = new \Cassandra\SimpleStatement($query);
            $options = new \Cassandra\ExecutionOptions([
                'arguments' => $params,
                'consistency' => \Cassandra::CONSISTENCY_QUORUM,
            ]);
            $data = $this->db->execute($statement, $options);

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

        if ($multiple) {
            $res = [];
            foreach ($data as $i) {
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

    protected function getObject($model, $data) {
        $transformed = [];
        foreach ($data as $k => $v) {
            $transformed[$k] = $model::fromDB($k, $v);
        }

        return new $model($transformed);
    }






    /*
     * --- Database handling of the 'users' and 'userid_sec' column family
     */
    public function saveUser(Models\User $user) {
        $data = $user->getStorableArray();
        if (!isset($data['updated'])) {
            $data['updated'] = (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp();
        }
        $query = self::generateInsert('users', $data);
        $this->execute($query, $data, __FUNCTION__);

        // echo "<pre>About to save user\n"; print_r($data); exit;

        // We also need to populate the userid_sec table with the corresponding secondary keys.
        // TODO; Make this a transaction
        if (isset($user->userid_sec) && is_array($user->userid_sec)) {
            foreach ($user->userid_sec as $sec) {
                $this->addUserIDsec($user->userid, $sec);
            }
        }
    }

    /**
     * Update basic information about the user that does not relate to userids or accountinformation.
     * @param  Models\User $user [description]
     * @return [type]            [description]
     */
    public function updateUserBasics(Models\User $user) {

        $query = 'UPDATE "users" SET updated = :updated, ' .
            'aboveagelimit = :aboveagelimit, ' .
            'usageterms = :usageterms ' .
            'WHERE userid = :userid';
        $params = [
            'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
            'userid' => new \Cassandra\Uuid($user->userid),
            'aboveagelimit' => $user->aboveagelimit,
            'usageterms' => $user->usageterms
        ];
        $this->execute($query, $params, __FUNCTION__);

    }
    

    /*
     * Takes a User object, and a secondary userid that will be updated with a fresh timestamp.
     */
    public function updateUserIDsecLastSeen(Models\User $user, $useridsec) {

        $query = 'UPDATE "users" SET userid_sec_seen[:useridsec] = :updated ' .
            'WHERE userid = :userid';
        $params = [
            'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
            'userid' => new \Cassandra\Uuid($user->userid),
            'useridsec' => $useridsec,
        ];

        // var_dump($query); var_dump($params); exit;
        $this->execute($query, $params, __FUNCTION__);
    }


    public function updateUserInfo(Models\User $user, $sourceID, $props = []) {


        $assignments = [];
        $assignments[] = 'updated = :updated';
        $assignments[] = 'selectedsource = :selectedsource';

        $userinfo = $user->getUserInfo($sourceID);
        $params = [
            'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
            'userid' => new \Cassandra\Uuid($user->userid),
            'name' => $userinfo['name'],
            'email' => $userinfo['email'],
            'selectedsource' => $user->selectedsource
        ];


        foreach ($props as $key) {
            if ($userinfo[$key] !== null) {
                $assignments[] = $key . '[\'' . $sourceID .'\'] = :' . $key;
            }

        }

        $query = 'UPDATE "users" SET ' . join(', ', $assignments) . " WHERE userid = :userid";
        // echo "QUERY IS " . $query;
        // print_r($params);

        $this->execute($query, $params, __FUNCTION__);

    }

    public function updateProfilePhoto(Models\User $user, $sourceID) {

        $userinfo = $user->getUserInfo($sourceID);

        if (empty($userinfo['profilephoto'])) {
            throw new Exception('Missing profilephoto');
        }
        if (empty($userinfo['profilephotohash'])) {
            throw new Exception('Missing profilephotohash');
        }



        $query = 'UPDATE "users" SET updated = :updated, ' .
            'profilephoto[\'' . $sourceID  . '\'] = :profilephoto, ' .
            'profilephotohash[\'' . $sourceID  . '\'] = :profilephotohash ' .
            'WHERE userid = :userid';
        $params = [
            'updated' => (new \FeideConnect\Data\Types\Timestamp())->getCassandraTimestamp(),
            'userid' => new \Cassandra\Uuid($user->userid),
            'profilephoto' => new \Cassandra\Blob($userinfo['profilephoto']),
            'profilephotohash' => $userinfo['profilephotohash'],
        ];
        $this->execute($query, $params, __FUNCTION__);

    }



    public function addUserIDsec($userid, $userid_sec) {
        $query  = 'UPDATE "users" SET userid_sec = userid_sec + :useridsec  WHERE userid = :userid';
        $query2 = 'INSERT INTO "userid_sec" (userid_sec, userid) VALUES (:useridsec, :userid)';
        $this->execute($query, [
            'userid' => new \Cassandra\Uuid($userid),
            'useridsec' => \Cassandra\Type::set(\Cassandra\Type::text())->create($userid_sec),
        ], __FUNCTION__);

        $this->execute($query2, [
            'useridsec' => $userid_sec,
            'userid' => new \Cassandra\Uuid($userid),
        ], __FUNCTION__);
    }

    public function deleteUser(Models\User $user) {

        // Logger::debug('DELETING USER ()', array(
        //     'userid' => $user->userid,
        //     'userid_sec' => $user->userid_sec
        // ));

        $query1 = 'DELETE FROM "users" WHERE ("userid" = :userid)';
        $query2 = 'DELETE FROM "userid_sec" WHERE ("userid_sec" IN :useridsecs) ';

        $this->execute($query1, ['userid' => new \Cassandra\Uuid($user->userid)], __FUNCTION__);
        if (!empty($user->userid_sec)) {
            $useridsecs = \Cassandra\Type::set(\Cassandra\Type::text())->create();
            foreach ($user->userid_sec as $userid_sec) {
                $useridsecs->add($userid_sec);
            }
            $this->execute($query2, [
                'useridsecs' => $useridsecs,
            ], __FUNCTION__);
        }

    }

    public function getUserByUserID($userid) {
        $query = 'SELECT userid, created, updated, name, email, profilephoto, profilephotohash, selectedsource, aboveagelimit, usageterms, userid_sec, userid_sec_seen FROM "users" WHERE "userid" = :userid';
        // $query = 'SELECT * FROM "users" WHERE "userid" = :userid';
        $params = ['userid' => new \Cassandra\Uuid($userid)];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\User', false);
    }




    public function getUserByUserIDsec($useridsec) {
        $query = 'SELECT userid_sec, userid FROM "userid_sec" WHERE "userid_sec" = :userid_sec';
        $params = ['userid_sec' => $useridsec];
        $result = $this->query($query, $params, __FUNCTION__, null, false);
        if ($result === null) {
            return null;
        }

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
    public function getUserByUserIDsecList($useridsec) {

        $useridsec_set = \Cassandra\Type::set(\Cassandra\Type::text())->create();
        foreach ($useridsec as $u) {
            $useridsec_set->add($u);
        }

        $query = 'SELECT userid_sec, userid FROM "userid_sec" WHERE "userid_sec" IN :userid_sec';
        $params = ['userid_sec' => $useridsec_set];

        // echo var_export($params, true);

        $data = $this->query($query, $params, __FUNCTION__, null, true);
        if (empty($data)) {
            return null;
        }

        $userids_set = \Cassandra\Type::set(\Cassandra\Type::uuid())->create();
        foreach ($data as $row) {
            $userids_set->add(new \Cassandra\Uuid($row['userid']));
        }

        // echo '<pre>About to lookup userids';
        // print_r($userids);

        // Retrieve the userids from the user table...
        $query2 = 'SELECT userid, created, updated, name, email, profilephoto, profilephotohash, selectedsource, aboveagelimit, usageterms, userid_sec, userid_sec_seen FROM "users" WHERE ("userid" IN :userids)';
        $params2 = ['userids' => $userids_set];
        // echo var_export($params2, true);
        $res = $this->query($query2, $params2, __FUNCTION__, 'FeideConnect\Data\Models\User', true);

        if (empty($res)) {
            return null;
        }
        return $res;
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
    public function getAPIGK($id) {
        $query = 'SELECT id, descr, endpoints, expose, httpscertpinned, name, owner, organization, scopes, requireuser, scopedef, privacypolicyurl, status, created, updated FROM "apigk" WHERE "id" = :id';
        $params = ['id' => $id];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\APIGK', false);
    }
    public function removeAPIGK(Models\APIGK $apigk) {
        $query = 'DELETE FROM "apigk" WHERE "id" = :id';
        $params = ['id' => $apigk->id];
        $this->execute($query, $params, __FUNCTION__);
    }

    public function saveAPIGK(Models\APIGK $apigk) {
        $data = $apigk->getStorableArray();
        $query = self::generateInsert('apigk', $data);
        // echo $query . "\n\n";
        // echo var_export($data, true); exit;
        $this->execute($query, $data, __FUNCTION__);
    }


    /*
     * --- Database handling of the 'client' column family
     */
    public function getClient($id) {
        $query = 'SELECT id, client_secret, created, descr, name, owner, organization, authproviders, logo, redirect_uri, scopes, scopes_requested, status, type, updated, orgauthorization, authoptions, supporturl, privacypolicyurl, homepageurl FROM "clients" WHERE "id" = :id';
        $params = ['id' => new \Cassandra\Uuid($id)];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Client', false);
    }

    public function saveClient(Models\Client $client) {
        $data = $client->getStorableArray();
        $query = self::generateInsert('clients', $data);
        // echo $query . "\n\n";
        // echo var_export($data, true); exit;
        $this->execute($query, $data, __FUNCTION__);
    }

    public function removeClient(Models\Client $client) {
        $query = 'DELETE FROM "clients" WHERE "id" = :id';
        $params = ['id' => new \Cassandra\Uuid($client->id)];
        $this->execute($query, $params, __FUNCTION__);
    }

    /*
     * ---- MAndatory clients -----
     */

    public function checkMandatory($realm, Models\Client $client) {
        $query = 'SELECT realm, clientid FROM "mandatory_clients" WHERE "realm" = :realm AND "clientid" = :clientid';
        $params = ['realm' => $realm, 'clientid' => new \Cassandra\Uuid($client->id)];
        return $this->query($query, $params, __FUNCTION__);
    }



    /*
     * --- Database handling of the 'organization' column family
     */
    public function getOrgsByService($service = 'auth') {
        $count = 500;
        $query = 'SELECT id,name,realm,type,uiinfo,services FROM "organizations" WHERE (services CONTAINS :service) LIMIT :count';
        $params = ['count' => $count, 'service' => $service];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Organization', true);
    }

    public function getOrg($orgid) {
        $query = 'SELECT id,name,realm,type,uiinfo,services FROM "organizations" WHERE "id" = :orgid';
        $params = ['orgid' => $orgid];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Organization', false);
    }

    public function saveOrganization(Models\Organization $org) {
        $data = $org->getStorableArray();
        $query = self::generateInsert('organizations', $data);
        // echo $query . "\n\n";
        // echo var_export($data, true); exit;
        $this->execute($query, $data, __FUNCTION__);
    }

    public function removeOrganization(Models\Organization $org) {
        $query = 'DELETE FROM "organizations" WHERE "id" = :id';
        $params = ['id' => $org->id];
        $this->execute($query, $params, __FUNCTION__);
    }


    /*
     * --- Database handling of the 'accesstoken' column family
     */
    public function getAccessToken($accesstoken) {
        $query = 'SELECT access_token, apigkid, clientid, issued, lastuse, scope, subtokens, token_type, userid, validuntil FROM "oauth_tokens" WHERE "access_token" = :access_token';
        $params = ['access_token' => new \Cassandra\Uuid($accesstoken)];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\AccessToken', false);
    }

    public function getAccessTokens($userid, $clientid) {

        $query = 'SELECT access_token, apigkid, clientid, issued, lastuse, scope, subtokens, token_type, userid, validuntil FROM "oauth_tokens" WHERE "userid" = :userid AND "clientid" = :clientid  AND "apigkid" = :apigkid ALLOW FILTERING';
        $params = [
            'userid' => new \Cassandra\Uuid($userid),
            'clientid' => new \Cassandra\Uuid($clientid),
            'apigkid' => '',
        ];

        // print_r($params); exit;

        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\AccessToken', true);
    }

    public function saveToken(Models\AccessToken $token) {
        $data = $token->getStorableArray();

        if (!($token->validuntil instanceof \FeideConnect\Data\Types\Timestamp)) {
            throw new StorageException('Could not store an access token without a properly set valid until timestamp.');
        }
        $query = self::generateInsert('oauth_tokens', $data, $this->getTTLskew($token->validuntil));
        $this->execute($query, $data, __FUNCTION__);



        $query = 'UPDATE "clients_counters" SET count_tokens = count_tokens + 1 WHERE "id" = :id';
        $params = ['id' => new \Cassandra\Uuid($token->clientid)];
        $this->execute($query, $params, __FUNCTION__);
    }

    public function rawSaveToken(Models\AccessToken $token) {
        $data = $token->getStorableArray();
        $query = self::generateInsert('oauth_tokens', $data, $token->validuntil->getInSeconds());
        $this->execute($query, $data, __FUNCTION__);
    }


    /*
     * --- Database handling of the 'oauth_authorizations' column family
     */
    public function getAuthorization($userid, $clientid) {
        $query = 'SELECT userid, clientid, apigk_scopes, issued, scopes FROM "oauth_authorizations" WHERE "userid" = :userid AND "clientid" = :clientid';
        $params = ['userid' => new \Cassandra\Uuid($userid), 'clientid' => new \Cassandra\Uuid($clientid)];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Authorization', false);
    }

    public function getAuthorizationsByUser(Models\User $user) {
        $query = 'SELECT userid, clientid, apigk_scopes, issued, scopes FROM "oauth_authorizations" WHERE "userid" = :userid';
        $params = ['userid' => new \Cassandra\Uuid($user->userid)];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\Authorization', true);
    }

    public function saveAuthorization(Models\Authorization $authorization) {
        $data = $authorization->getStorableArray();
        $query = self::generateInsert('oauth_authorizations', $data);
        $this->execute($query, $data, __FUNCTION__);

        $query = 'UPDATE "clients_counters" SET count_users = count_users + 1 WHERE "id" = :id';
        $params = ['id' => new \Cassandra\Uuid($authorization->clientid)];
        return $this->execute($query, $params, __FUNCTION__);
    }


    /*
     * --- Database handling of the 'oauth_codes' column family
     */
    public function getAuthorizationCode($code) {
        $query = 'SELECT code, apigk_scopes, clientid, idtoken, issued, redirect_uri, scope, token_type, userid, validuntil FROM "oauth_codes" WHERE "code" = :code';
        $params = ['code' => new \Cassandra\Uuid($code)];
        return $this->query($query, $params, __FUNCTION__, 'FeideConnect\Data\Models\AuthorizationCode', false);
    }

    public function saveAuthorizationCode(Models\AuthorizationCode $code) {
        $data = $code->getStorableArray();

        if (!($code->validuntil instanceof \FeideConnect\Data\Types\Timestamp)) {
            throw new StorageException('Could not store an authorization code without a properly set valid until timestamp.');
        }
        $query = self::generateInsert('oauth_codes', $data, $this->getTTLskew($code->validuntil));
        $this->execute($query, $data, __FUNCTION__);
    }

    public function removeAuthorizationCode(Models\AuthorizationCode $code) {
        $query = 'DELETE FROM "oauth_codes" WHERE "code" = :code';
        $params = ['code' => new \Cassandra\Uuid($code->code)];
        $this->execute($query, $params, __FUNCTION__);
    }

    public function updateLoginStats(Models\Client $client, $authsource) {
        $timeslot = new \FeideConnect\Data\Types\Timestamp();
        $timeslot->roundseconds(60);
        $date = $timeslot->datestring();
        
        $query = 'UPDATE "logins_stats" SET login_count = login_count + 1 WHERE "clientid" = :clientid';
        $query .= ' AND "date" = :date AND "timeslot" = :timeslot AND "authsource" = :authsource';
        $params = [
            'clientid' => new \Cassandra\Uuid($client->id),
            'date' => $date,
            'timeslot' => $timeslot->getCassandraTimestamp(),
            'authsource' => $authsource,
        ];
        $this->execute($query, $params, __FUNCTION__);
    }





}
