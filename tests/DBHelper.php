<?php

namespace tests;

use FeideConnect\Data\Types\Timestamp;
use FeideConnect\Data\Models;

class DBHelper extends \PHPUnit_Framework_TestCase {


    protected $db, $_SERVER, $_REQUEST;

    public function __construct() {
        $this->db = new RawCassandra2();
        $this->_SERVER = $_SERVER;
        $this->_REQUEST = $_REQUEST;
    }

    public function setUp() {
        $_SERVER = $this->_SERVER;
        $_REQUEST = $this->_REQUEST;
    }

    public function tearDown() {
        $_SERVER = $this->_SERVER;
        $_REQUEST = $this->_REQUEST;
    }

    public function clearUsers() {
        $this->db->rawExecute('TRUNCATE users', []);
        $this->db->rawExecute('TRUNCATE userid_sec', []);
    }

    public function client() {
        $clientid = Models\Client::genUUID();

        $client = new Models\Client();
        $client->id = $clientid;
        $client->client_secret = Models\Client::genUUID();
        $client->created = new \FeideConnect\Data\Types\Timestamp();
        $client->name = 'name';
        $client->descr = 'descr';
        $client->owner = null;
        $client->redirect_uri = ['http://example.org'];
        $client->scopes = ['userinfo', 'groups'];
        $client->client_secret = Models\Client::genUUID();
        $client->orgauthorizations = array();
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

    public function apigk() {
        $apiid = 'test';
        $user = $this->user('feide:gkowner@example.org');
        $apigk = new Models\APIGK(array(
            'id' => $apiid,
            'name' => 'Test API',
            'owner' => $user->userid,
            'scopes' => ['userid', 'name', 'email', 'userid-feide'],
            'scopedef' => json_encode(array(
                'title' => 'Basic',
                'descr' => 'Test api',
                'policy' => array('auto' => true),
                'subscopes' => array(
                    'a' => array(
                        'title' => 'scope a',
                        'descr' => 'test scope a',
                        'policy' => array('auto' => true),
                    ),
                    'b' => array(
                        'title' => 'scope b',
                        'descr' => 'test scope b',
                        'policy' => array('auto' => false),
                    ),
                    'moderated' => array(
                        'title' => 'scope moderated',
                        'descr' => 'a org moderated scope',
                        'policy' => array(
                            'auto' => true,
                            'orgadmin' => array(
                                'moderate' => true,
                            ),
                        ),
                    ),
                ),
            )),
        ));
        $this->db->saveAPIGK($apigk);
        return $apigk;
    }

    public function org() {
        $org = new Models\Organization(array(
            'id' => 'fc:org:example.org',
            'name' => array(
                'nb' => 'Testorganisasjon',
                'en' => 'Test organization',
            ),
            'type' => ['higher_education'],
        ));
        $this->db->saveOrganization($org);
        return $org;
    }

    public function token($client, $user, $scopes, $expire_in) {
        $validUntil = (new Timestamp())->addSeconds($expire_in);
        $a = Models\AccessToken::generate($client, $user, null, $scopes, $validUntil);
        $this->db->saveToken($a);
        return $a;
    }

    public function authorization($client, $user, $scopes = [], $apigk_scopes = []) {
        $authz = new Models\Authorization([
            "clientid" => $client->id,
            "userid" => $user->userid,
            "scopes" => $scopes,
            "apigk_scopes" => $apigk_scopes,
        ]);
        $this->db->saveAuthorization($authz);
        return $authz;
    }
}
