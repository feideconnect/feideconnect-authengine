<?php

namespace tests;

use FeideConnect\Data\Models;

class Cassandra2Test extends DBHelper {

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::saveUser
     */
    public function testSaveUser() {
        $userid = Models\User::genUUID();
        $userid_sec = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';

        $user = new Models\User($this->db);
        $user->userid = $userid;
        $user->userid_sec = [$userid_sec];
        $this->db->saveUser($user);

        $results = $this->db->rawQuery('SELECT userid, updated, userid_sec FROM "users" WHERE userid = :userid', ['userid' => new \Cassandra\Type\Uuid($userid)]);
        $this->assertCount(1, $results);
        $storedUser = $results[0];
        $this->assertEquals($userid, $storedUser['userid']);
        $this->assertNotNull($storedUser['updated']);
        $this->assertEquals([$userid_sec], $storedUser['userid_sec']);

        $results = $this->db->rawQuery('SELECT userid_sec, userid FROM "userid_sec" WHERE userid_sec = :userid_sec', ['userid_sec' => $userid_sec]);
        $this->assertCount(1, $results);
        $storedUserIDSec = $results[0];
        $this->assertEquals($userid_sec, $storedUserIDSec['userid_sec']);
        $this->assertEquals(new \Cassandra\Type\Uuid($userid), $storedUserIDSec['userid']);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::updateUserBasics
     */
    public function testUpdateUserBasics() {
        $userid = Models\User::genUUID();

        $this->db->rawExecute('INSERT INTO "users" ("userid", "aboveagelimit", "usageterms") VALUES(:userid, FALSE, FALSE)', ['userid' => new \Cassandra\Type\Uuid($userid)]);

        $user = $this->db->getUserByUserid($userid);
        $user->aboveagelimit = true;
        $user->usageterms = true;
        $this->db->updateUserBasics($user);

        $results = $this->db->rawQuery('SELECT updated, aboveagelimit, usageterms FROM "users" WHERE userid = :userid', ['userid' => new \Cassandra\Type\Uuid($userid)]);
        $this->assertCount(1, $results);
        $storedUser = $results[0];
        $this->assertNotNull($storedUser['updated']);
        $this->assertEquals(true, $storedUser['aboveagelimit']);
        $this->assertEquals(true, $storedUser['usageterms']);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::updateUserIDsecLastSeen
     */
    public function testUpdateUserIDsecLastSeen() {
        $userid = Models\User::genUUID();

        $this->db->rawExecute('INSERT INTO "users" ("userid") VALUES(:userid)', ['userid' => new \Cassandra\Type\Uuid($userid)]);

        $user = $this->db->getUserByUserid($userid);
        $this->db->updateUserIDsecLastSeen($user, 'foo');

        $results = $this->db->rawQuery('SELECT userid_sec_seen FROM "users" WHERE userid = :userid', ['userid' => new \Cassandra\Type\Uuid($userid)]);
        $this->assertCount(1, $results);
        $storedUser = $results[0];
        $this->assertArrayHasKey('foo', $storedUser['userid_sec_seen']);

        /* Make sure that userid_sec_seen is close to "now". */
        $currentTime = (int)(microtime(true)*1000);
        $this->assertGreaterThan($currentTime-5000, $storedUser['userid_sec_seen']['foo']);
        $this->assertLessThan($currentTime+5000, $storedUser['userid_sec_seen']['foo']);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::updateUserInfo
     */
    public function testUpdateUserInfo() {
        $userid = Models\User::genUUID();

        $this->db->rawExecute('INSERT INTO "users" ("userid", "name", "email") VALUES(:userid, :name, :email)', [
            'userid' => new \Cassandra\Type\Uuid($userid),
            'name' => new \Cassandra\Type\CollectionMap([ 'foo' => 'Foo Testesen', 'bar' => 'Bar Testesen' ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII),
            'email' => new \Cassandra\Type\CollectionMap([ 'foo' => 'foo@example.org', 'bar' => 'bar@example.org' ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII),
        ]);

        $user = $this->db->getUserByUserid($userid);
        $user->setUserInfo('foo', 'Test Testesen', 'test@example.org', null, null);
        $user->selectedsource = 'foo';
        $this->db->updateUserInfo($user, 'foo', ['name', 'email']);

        $results = $this->db->rawQuery('SELECT updated, name, email, selectedsource FROM "users" WHERE userid = :userid', ['userid' => new \Cassandra\Type\Uuid($userid)]);
        $this->assertCount(1, $results);
        $storedUser = $results[0];
        $this->assertNotNull($storedUser['updated']);
        $this->assertArrayHasKey('foo', $storedUser['name']);
        $this->assertEquals('Test Testesen', $storedUser['name']['foo']);
        $this->assertArrayHasKey('foo', $storedUser['email']);
        $this->assertEquals('test@example.org', $storedUser['email']['foo']);
        $this->assertArrayHasKey('bar', $storedUser['name']);
        $this->assertEquals('Bar Testesen', $storedUser['name']['bar']);
        $this->assertArrayHasKey('bar', $storedUser['email']);
        $this->assertEquals('bar@example.org', $storedUser['email']['bar']);
        $this->assertEquals('foo', $storedUser['selectedsource']);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::updateProfilePhoto
     */
    public function testUpdateProfilePhoto() {
        $userid = Models\User::genUUID();

        $this->db->rawExecute('INSERT INTO "users" ("userid", "profilephoto", "profilephotohash") VALUES(:userid, :profilephoto, :profilephotohash)', [
            'userid' => new \Cassandra\Type\Uuid($userid),
            'profilephoto' => new \Cassandra\Type\CollectionMap([ 'foo' => 'fooimg', 'bar' => 'barimg' ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::BLOB),
            'profilephotohash' => new \Cassandra\Type\CollectionMap([ 'foo' => 'foohash', 'bar' => 'barhash' ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII),
        ]);

        $user = $this->db->getUserByUserid($userid);
        $user->setUserInfo('foo', null, null, 'newfooimg', 'newfoohash');
        $this->db->updateProfilePhoto($user, 'foo');

        $results = $this->db->rawQuery('SELECT updated, profilephoto, profilephotohash FROM "users" WHERE userid = :userid', ['userid' => new \Cassandra\Type\Uuid($userid)]);
        $this->assertCount(1, $results);
        $storedUser = $results[0];
        $this->assertNotNull($storedUser['updated']);
        $this->assertArrayHasKey('foo', $storedUser['profilephoto']);
        $this->assertEquals('newfooimg', $storedUser['profilephoto']['foo']);
        $this->assertArrayHasKey('foo', $storedUser['profilephotohash']);
        $this->assertEquals('newfoohash', $storedUser['profilephotohash']['foo']);
        $this->assertArrayHasKey('bar', $storedUser['profilephoto']);
        $this->assertEquals('barimg', $storedUser['profilephoto']['bar']);
        $this->assertArrayHasKey('bar', $storedUser['profilephotohash']);
        $this->assertEquals('barhash', $storedUser['profilephotohash']['bar']);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::addUserIDsec
     */
    public function testAddUserIDsec() {
        $userid = Models\User::genUUID();
        $userid_sec_orig = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';
        $userid_sec_new = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';

        $this->db->rawExecute('INSERT INTO "users" ("userid", "userid_sec") VALUES(:userid, :userid_sec)', [
            'userid' => new \Cassandra\Type\Uuid($userid),
            'userid_sec' => new \Cassandra\Type\CollectionSet([ $userid_sec_orig ], \Cassandra\Type\Base::ASCII),
        ]);

        $this->db->addUserIDsec($userid, $userid_sec_new);

        $results = $this->db->rawQuery('SELECT userid_sec FROM "users" WHERE userid = :userid', ['userid' => new \Cassandra\Type\Uuid($userid)]);
        $this->assertCount(1, $results);
        $storedUser = $results[0];
        $this->assertContains($userid_sec_orig, $storedUser['userid_sec']);
        $this->assertContains($userid_sec_new, $storedUser['userid_sec']);

        $results = $this->db->rawQuery('SELECT userid_sec, userid FROM "userid_sec" WHERE userid_sec = :userid_sec', ['userid_sec' => $userid_sec_new]);
        $this->assertCount(1, $results);
        $storedUserIDSec = $results[0];
        $this->assertEquals($userid_sec_new, $storedUserIDSec['userid_sec']);
        $this->assertEquals(new \Cassandra\Type\Uuid($userid), $storedUserIDSec['userid']);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::deleteUser
     */
    public function testDeleteUser() {
        $userid = Models\User::genUUID();
        $userid_sec_a = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';
        $userid_sec_b = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';

        $this->db->rawExecute('INSERT INTO "users" ("userid", "userid_sec") VALUES(:userid, :userid_sec)', [
            'userid' => new \Cassandra\Type\Uuid($userid),
            'userid_sec' => new \Cassandra\Type\CollectionSet([ $userid_sec_a, $userid_sec_b ], \Cassandra\Type\Base::ASCII),
        ]);
        $this->db->rawExecute('INSERT INTO "userid_sec" ("userid_sec", "userid") VALUES(:userid_sec, :userid)', [
            'userid_sec' => $userid_sec_a,
            'userid' => new \Cassandra\Type\Uuid($userid),
        ]);
        $this->db->rawExecute('INSERT INTO "userid_sec" ("userid_sec", "userid") VALUES(:userid_sec, :userid)', [
            'userid_sec' => $userid_sec_b,
            'userid' => new \Cassandra\Type\Uuid($userid),
        ]);

        $user = $this->db->getUserByUserid($userid);
        $this->db->deleteUser($user);

        $results = $this->db->rawQuery('SELECT userid FROM "users" WHERE userid = :userid', ['userid' => new \Cassandra\Type\Uuid($userid)]);
        $this->assertCount(0, $results);
        $results = $this->db->rawQuery('SELECT userid_sec, userid FROM "userid_sec" WHERE userid_sec = :userid_sec', ['userid_sec' => $userid_sec_a]);
        $this->assertCount(0, $results);
        $results = $this->db->rawQuery('SELECT userid_sec, userid FROM "userid_sec" WHERE userid_sec = :userid_sec', ['userid_sec' => $userid_sec_b]);
        $this->assertCount(0, $results);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::getUserByUserID
     */
    public function testGetUserByUserID() {
        $userid = Models\User::genUUID();
        $userid_sec = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';

        $this->db->rawExecute('INSERT INTO "users" ("userid", "created", "updated", "name", "email", "profilephoto", "profilephotohash", "selectedsource", "aboveagelimit", "usageterms", "userid_sec", "userid_sec_seen") VALUES(:userid, :created, :updated, :name, :email, :profilephoto, :profilephotohash, :selectedsource, :aboveagelimit, :usageterms, :userid_sec, :userid_sec_seen)', [
            'userid' => new \Cassandra\Type\Uuid($userid),
            'created' => new \Cassandra\Type\Timestamp(1122334455667),
            'updated' => new \Cassandra\Type\Timestamp(1234567890123),
            'name' => new \Cassandra\Type\CollectionMap([
                'foo' => 'Foo Testesen',
                'bar' => 'Bar Testesen',
            ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII),
            'email' => new \Cassandra\Type\CollectionMap([
                'foo' => 'foo@example.org',
                'bar' => 'bar@example.org',
            ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII),
            'profilephoto' => new \Cassandra\Type\CollectionMap([
                'foo' => 'fooimg',
                'bar' => 'barimg',
            ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::BLOB),
            'profilephotohash' => new \Cassandra\Type\CollectionMap([
                'foo' => 'foohash',
                'bar' => 'barhash',
            ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::ASCII),
            'selectedsource' => 'foo',
            'aboveagelimit' => false,
            'usageterms' => false,
            'userid_sec' => new \Cassandra\Type\CollectionSet([ $userid_sec ], \Cassandra\Type\Base::ASCII),
            'userid_sec_seen' => new \Cassandra\Type\CollectionMap([
                $userid_sec => 1234567890123,
            ], \Cassandra\Type\Base::ASCII, \Cassandra\Type\Base::TIMESTAMP),
        ]);

        $user = $this->db->getUserByUserID($userid);

        $this->assertNotNull($user);
        $this->assertEquals($userid, $user->userid);
        $this->assertArrayHasKey('foo', $user->name);
        $this->assertEquals('Foo Testesen', $user->name['foo']);
        $this->assertArrayHasKey('bar', $user->name);
        $this->assertEquals('Bar Testesen', $user->name['bar']);
        $this->assertArrayHasKey('foo', $user->email);
        $this->assertEquals('foo@example.org', $user->email['foo']);
        $this->assertArrayHasKey('bar', $user->email);
        $this->assertEquals('bar@example.org', $user->email['bar']);
        $this->assertArrayHasKey('foo', $user->profilephoto);
        $this->assertEquals('fooimg', $user->profilephoto['foo']);
        $this->assertArrayHasKey('bar', $user->profilephoto);
        $this->assertEquals('barimg', $user->profilephoto['bar']);
        $this->assertArrayHasKey('foo', $user->profilephotohash);
        $this->assertEquals('foohash', $user->profilephotohash['foo']);
        $this->assertArrayHasKey('bar', $user->profilephotohash);
        $this->assertEquals('barhash', $user->profilephotohash['bar']);
        $this->assertEquals([$userid_sec], $user->userid_sec);
        $this->assertEquals([$userid_sec => 1234567890123], $user->userid_sec_seen);
        $this->assertEquals('foo', $user->selectedsource);
        $this->assertEquals(false, $user->aboveagelimit);
        $this->assertEquals(false, $user->usageterms);
        $this->assertInstanceOf(\FeideConnect\Data\Types\Timestamp::class, $user->created);
        $this->assertEquals(1122334455667, (int)($user->created->getValue()*1000));
        $this->assertInstanceOf(\FeideConnect\Data\Types\Timestamp::class, $user->updated);
        $this->assertEquals(1234567890123, (int)($user->updated->getValue()*1000));
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::getUserByUserIDsec
     */
    public function testGetUserByUserIDsec() {
        $userid = Models\User::genUUID();
        $userid_sec = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';

        $this->db->rawExecute('INSERT INTO "users" ("userid", "userid_sec") VALUES(:userid, :userid_sec)', [
            'userid' => new \Cassandra\Type\Uuid($userid),
            'userid_sec' => new \Cassandra\Type\CollectionSet([ $userid_sec ], \Cassandra\Type\Base::ASCII),
        ]);
        $this->db->rawExecute('INSERT INTO "userid_sec" ("userid_sec", "userid") VALUES(:userid_sec, :userid)', [
            'userid_sec' => $userid_sec,
            'userid' => new \Cassandra\Type\Uuid($userid),
        ]);

        $user = $this->db->getUserByUserIDsec($userid_sec);
        $this->assertNotNull($user);
        $this->assertEquals($userid, $user->userid);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::getUserByUserIDsecList
     */
    public function testGetUserByUserIDsecList() {
        $userid_a = Models\User::genUUID();
        $userid_sec_a = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';
        $userid_b = Models\User::genUUID();
        $userid_sec_b = bin2hex(openssl_random_pseudo_bytes(8)) . '@example.org';

        $this->db->rawExecute('INSERT INTO "users" ("userid", "userid_sec") VALUES(:userid, :userid_sec)', [
            'userid' => new \Cassandra\Type\Uuid($userid_a),
            'userid_sec' => new \Cassandra\Type\CollectionSet([ $userid_sec_a ], \Cassandra\Type\Base::ASCII),
        ]);
        $this->db->rawExecute('INSERT INTO "userid_sec" ("userid_sec", "userid") VALUES(:userid_sec, :userid)', [
            'userid_sec' => $userid_sec_a,
            'userid' => new \Cassandra\Type\Uuid($userid_a),
        ]);
        $this->db->rawExecute('INSERT INTO "users" ("userid", "userid_sec") VALUES(:userid, :userid_sec)', [
            'userid' => new \Cassandra\Type\Uuid($userid_b),
            'userid_sec' => new \Cassandra\Type\CollectionSet([ $userid_sec_b ], \Cassandra\Type\Base::ASCII),
        ]);
        $this->db->rawExecute('INSERT INTO "userid_sec" ("userid_sec", "userid") VALUES(:userid_sec, :userid)', [
            'userid_sec' => $userid_sec_b,
            'userid' => new \Cassandra\Type\Uuid($userid_b),
        ]);

        $users = $this->db->getUserByUserIDsecList([$userid_sec_a, $userid_sec_b]);
        $this->assertCount(2, $users);
        $this->assertTrue($users[0]->userid === $userid_a || $users[1]->userid === $userid_a);
        $this->assertTrue($users[0]->userid === $userid_b || $users[1]->userid === $userid_b);

        $users = $this->db->getUserByUserIDsecList(['incorrect_userid_here']);
        $this->assertNull($users);

        $users = $this->db->getUserByUserIDsecList([$userid_sec_a, 'incorrect_userid_here']);
        $this->assertCount(1, $users);
        $this->assertEquals($userid_a, $users[0]->userid);
    }

    /**
     * @covers \FeideConnect\Data\Repositories\Cassandra2::getAPIGK
     */
    public function testGetAPIGK() {
        $id = bin2hex(openssl_random_pseudo_bytes(8));
        $owner_uuid = \FeideConnect\Data\Model::genUUID();

        $this->db->rawExecute('INSERT INTO "apigk" ("id", "descr", "endpoints", "expose", "httpscertpinned", "name", "owner", "organization", "scopes", "requireuser", "scopedef", "privacypolicyurl", "status", "created", "updated") VALUES(:id, :descr, :endpoints, :expose, :httpscertpinned, :name, :owner, :organization, :scopes, :requireuser, :scopedef, :privacypolicyurl, :status, :created, :updated)', [
            'id' => $id,
            'descr' => 'Test API GK description',
            'endpoints' => new \Cassandra\Type\CollectionList([
                'https://foo.example.org/bar'
            ], \Cassandra\Type\Base::ASCII),
            'expose' => 'expose-value',
            'httpscertpinned' => 'httpscertpinned-value',
            'name' => 'Test API GK name',
            'owner' => new \Cassandra\Type\Uuid($owner_uuid),
            'organization' => 'Foo Inc.',
            'scopes' => new \Cassandra\Type\CollectionSet([ 'foo-scope' ], \Cassandra\Type\Base::ASCII),
            'requireuser' => true,
            'scopedef' => json_encode([ 'scopedef-key' => 'scopedef-value' ]),
            'privacypolicyurl' => 'https://foo.example.org/privacy-policy',
            'status' => new \Cassandra\Type\CollectionSet([ 'status-value' ], \Cassandra\Type\Base::ASCII),
            'created' => new \Cassandra\Type\Timestamp(1122334455667),
            'updated' => new \Cassandra\Type\Timestamp(1234567890123),
        ]);

        $gk = $this->db->getAPIGK($id);
        $this->assertNotNull($gk);
        $this->assertEquals('Test API GK name', $gk->name);
        $this->assertEquals('Test API GK description', $gk->descr);
        $this->assertEquals($owner_uuid, $gk->owner);
        $this->assertEquals('Foo Inc.', $gk->organization);
        $this->assertEquals(['https://foo.example.org/bar'], $gk->endpoints);
        $this->assertEquals('expose-value', $gk->expose);
        $this->assertEquals('httpscertpinned-value', $gk->httpscertpinned);
        $this->assertTrue($gk->requireuser);
        $this->assertArrayHasKey('scopedef-key', $gk->scopedef);
        $this->assertEquals('scopedef-value', $gk->scopedef['scopedef-key']);
        $this->assertEquals(['foo-scope'], $gk->scopes);
        $this->assertEquals('https://foo.example.org/privacy-policy', $gk->privacypolicyurl);
        $this->assertEquals(['status-value'], $gk->status);
        $this->assertInstanceOf(\FeideConnect\Data\Types\Timestamp::class, $gk->created);
        $this->assertEquals(1122334455667, (int)($gk->created->getValue()*1000));
        $this->assertInstanceOf(\FeideConnect\Data\Types\Timestamp::class, $gk->updated);
        $this->assertEquals(1234567890123, (int)($gk->updated->getValue()*1000));
    }

    /*

    function getAuthorization($userid, $clientid) {
    function getAuthorizationsByUser(Models\User $user) {
    function saveAuthorization(Models\Authorization $authorization) {
    function removeAuthorization($userid, $clientid) {
     */
    public function testAuthorizations() {

        $clientid = Models\Client::genUUID();
        $userid = Models\User::genUUID();

        $user = new Models\User();
        $user->userid = $userid;


        $authorization = new Models\Authorization();
        $authorization->issued = new \FeideConnect\Data\Types\Timestamp();

        $authorization->clientid = $clientid;
        $authorization->userid = $userid;
        $authorization->scopes = ['userinfo', 'groups'];

        $this->db->saveAuthorization($authorization);


        $authorization2 = $this->db->getAuthorization($userid, $clientid);

        $this->assertTrue($authorization2->includeScopes(['userinfo', 'groups']), 'Retrieved stored item, check scopes');
        $this->assertFalse($authorization2->includeScopes(['xxxxx']), 'Retrieved stored item, check scopes');



        $authorization4 = $this->db->getAuthorizationsByUser($user);
        $this->assertTrue(count($authorization4) === 1, 'Should find authorizations for this user');


        $this->db->removeAuthorizations($userid, $clientid);

        $authorization3 = $this->db->getAuthorization($userid, $clientid);
        $this->assertNull($authorization3, 'Authorization should now have been deleted');





    }


    /*

    function getAccessToken($accesstoken) {
    function getAccessTokens($userid, $clientid) {
    function saveToken(Models\AccessToken $token) {
     */
    public function testTokens() {

        $clientid = Models\Client::genUUID();
        $userid = Models\User::genUUID();

        $user = new Models\User();
        $user->userid = $userid;


        $token = new Models\AccessToken();
        $token->access_token = Models\AccessToken::genUUID();
        $token->clientid = $clientid;
        $token->userid = $userid;
        $token->scope = ['userinfo', 'groups'];
        $token->token_type = 'Bearer';

        $token->issued = new \FeideConnect\Data\Types\Timestamp();
        $token->validuntil = (new \FeideConnect\Data\Types\Timestamp())->addSeconds(3600);
        $token->lastuse = (new \FeideConnect\Data\Types\Timestamp())->addSeconds(5);


        $this->db->saveToken($token);


        $token2 = $this->db->getAccessToken($token->access_token);
        $this->assertTrue($token2->hasExactScopes(['userinfo', 'groups']), 'Retrieved stored item, check scopes');
        $this->assertFalse($token2->hasExactScopes(['userinfo']), 'Retrieved stored item, check scopes');
        $this->assertTrue($token2->hasScopes(['userinfo', 'groups']), 'Retrieved stored item, check scopes');
        $this->assertTrue($token2->stillValid(), 'Retrieved stored item, check scopes');


        $tokenSearched = $this->db->getAccessTokens($userid, $clientid);
        $this->assertTrue(count($tokenSearched) === 1, 'Should find tokens for this user');


        $this->db->removeAccessToken($token);
        $token3 = $this->db->getAccessToken($token->access_token);
        $this->assertNull($token3, 'Token should now have been deleted');


    }


    /*
    function getAuthorizationCode($code) {
    function saveAuthorizationCode(Models\AuthorizationCode $code) {
    function removeAuthorizationCode(Models\AuthorizationCode $code) {
     */
    public function testCodes() {

        $cid = Models\AuthorizationCode::genUUID();
        $code = new Models\AuthorizationCode();

        $code->code = $cid;
        $code->clientid = Models\AuthorizationCode::genUUID();
        $code->userid = Models\AuthorizationCode::genUUID();

        $code->issued = new \FeideConnect\Data\Types\Timestamp();
        $code->validuntil = (new \FeideConnect\Data\Types\Timestamp())->addSeconds(3600);

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

        $user = new Models\User();
        $user->userid = $userid;

        $client = new Models\Client();
        $client->id = $c1;
        $client->client_secret = Models\Client::genUUID();
        $client->created = new \FeideConnect\Data\Types\Timestamp();
        $client->name = 'name';
        $client->descr = 'descr';
        $client->owner = $userid;
        $client->redirect_uri = ['http://example.org'];
        $client->scopes = ['userinfo', 'groups'];

        $client2 = new Models\Client();
        $client2->id = $c2;
        $client2->name = 'name2';
        $client2->authoptions['requireInteraction'] = false;


        $this->db->saveClient($client);
        $this->db->saveClient($client2);


        $c = $this->db->getClient($c1);
        $this->assertInstanceOf('FeideConnect\Data\Models\Client', $c, 'Returned client from storage query is instance of correct class');
        $this->assertTrue($c->id === $c1, 'Read client has same ID as written');


        $c = $this->db->getClient($c2);
        $this->assertInstanceOf('FeideConnect\Data\Models\Client', $c, 'Returned client from storage query is instance of correct class');
        $this->assertTrue($c->id === $c2, 'Read client has same ID as written');
        $this->assertFalse($c->requireInteraction());


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

}
