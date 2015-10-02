<?php


namespace FeideConnect;

use FeideConnect\Authentication\UserID;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Types\Timestamp;
use FeideConnect\Data\Models\AccessToken;
use FeideConnect\OAuth\AccessTokenPool;

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
            return null;
        }



        $info = $user->getAsArray();
        $info["profilephoto"] = '----';

        // echo var_export($info, true);

        $this->oneEntry($user);

        $this->info();
        $this->info("looking up reverse entries");

        $cql = '';

        // echo var_export($info, true); exit;

        if (!empty($user->userid_sec)) {
            foreach ($user->userid_sec as $k) {
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
        }



        echo "\n\n" . $cql . "\n\n";

        return $user;

    }

    function addUserIDsec($userid, $useridSec) {
        $this->storage->addUserIDsec($userid, $useridSec);
    }


    function getAPIGK($apigkid) {

        $this->header("Fetch information about API Gatekeeper " . $apigkid);
        $apigk = $this->storage->getAPIGK($apigkid);
        $this->oneEntry($apigk);
        return $apigk;

    }

    function getClient($clientid) {

        $this->header("Fetch information about client " . $clientid);
        $client = $this->storage->getClient($clientid);
        if ($client !== null) {
            $client->logo = '----';
            $this->oneEntry($client);

        }
        return $client;

    }

    function deleteClient($client) {
        $this->header("Deleting client " . $client->id);
        $this->storage->removeClient($client);
    }

    function deleteAPIGK($apigk) {
        $this->header("Deleting apigk " . $apigk->id);
        $this->storage->removeAPIGK($apigk);
    }


    function setScopes($client, $scopes_requested, $scopes) {
        $this->storage->updateClientScopes($client, $scopes_requested, $scopes);
        return $this->getClient($client->id);
    }

    function getTokensClient($client) {
        $this->header("Fetch list of tokens for client " . $client->id);

        $pool = new AccessTokenPool($client);
        $tokens = $pool->getAllTokens();

        // echo "TOKENS IS \n"; print_r($tokens); exit;
        // print_r($tokens);

        $c = 0;
        foreach ($tokens as $token) {
            $t = $token->getAsArray(true, true);
            // print_r($t);
            $t["c"] = ++$c;
            echo $this->l($t, [
                "c" => ["%3d", "red"],
                "access_token" => ["%38s", "black", 38 ],
                "clientid" => ["%-30s", "blue", 38],
                "userid" => ["%-30s", "red", 38],
                "issued" => ["%-30s", "blue", 30],
                "validuntil" => ["%-30s", "cyan", 30],
                "scope" => ["%-22s", "purple"],
            ]);

        }


    }

    function getTokensUser($client, $user) {
        $this->header("Fetch list of tokens for client " . $client->id . " and user " . $user->userid);

        $pool = new AccessTokenPool($client, $user);
        $tokens = $pool->getAllTokens();

        $c = 0;
        foreach ($tokens as $token) {
            $t = $token->getAsArray(true, true);
            $t["c"] = ++$c;
            echo $this->l($t, [
                "c" => ["%3d", "red"],
                "access_token" => ["%38s", "black", 38 ],
                "clientid" => ["%-30s", "blue"],
                "userid" => ["%-30s", "red"],
                "issued" => ["%-30s", "blue", 20],
                "validuntil" => ["%-30s", "cyan", 25],
                "scope" => ["%-22s", "purple"],
            ]);

        }

    }


    function getToken($token) {

        $this->header("Fetch information about token " . $token);

        $token = $this->storage->getAccessToken($token);

        // print_r($token);

        $this->oneEntry($token);


        if (isset($token->userid)) {
            $this->getUser('uuid:' . $token->userid);
        }

        if (isset($token->clientid)) {
            $this->getClient($token->clientid);
        }


    }


    function deleteUser($user) {
        $this->header("Deleting user " . $user->userid);
        $this->storage->deleteUser($user);
    }


    function getUsers($count) {

        $users = $this->storage->getUsers($count);
        $this->header("List users");
        $c = 0;
        foreach ($users as $user) {
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

        return $users;

    }


    public function getUserIDsec($count = 100) {
        $users = $this->storage->getUserIDsecList($count);
        $c = 0;
        foreach ($users as $user) {
            // $uinfo = $user->getBasicUserInfo(true, true);
            $uinfo = $user;
            $uinfo["c"] = ++$c;
            echo $this->l($uinfo, [
                "c" => ["%3d", "red"],
                "userid" => ["%38s", "black", 38 ],
                "userid_sec" => ["%-40s", "cyan", 40],
            ]);

        }
        return $users;
    }


    public function getClients() {

        $clients = $this->storage->getClients(200);
        $this->header("List clients");
        $c = 0;
        foreach ($clients as $client) {
            $cinfo = $client->getAsArray();
            $cinfo["c"] = ++$c;

            // $this->oneEntry($cinfo);

            echo $this->l($cinfo, [
                "c" => ["%3d", "red"],

                "name" => ["%30s", "green", 30],
                "owner" => ["%30s", "green", 30],
                "id" => ["%38s", "black", 38 ],
                "redirect_uri" => ["%-45s", "blue", 45],
                "scopes" => ["%-90s", "purple", 90],
                "organization" => ["%-30s", "green", 30]
            ]);

        }


    }


    public function getOrgs() {

        $orgs = $this->storage->getOrgs();
        $this->header("List organizations");
        $c = 0;
        foreach ($orgs as $org) {
            $cinfo = $org->getAsArray();
            $cinfo["c"] = ++$c;

            // $this->oneEntry($cinfo);

            echo $this->l($cinfo, [
                "c" => ["%3d", "red"],

                "id" => ["%30s", "green", 30],
                "name" => ["%78s", "black", 78 ],
                "type" => ["%60s", "green", 60],

            ]);

        }


    }

    function getOrg($orgid) {

        $this->header("Fetch information about org " . $orgid);
        $org = $this->storage->getOrg($orgid);
        $this->oneEntry($org);
        return $org;

    }

    function updateOrgLogo($org, $logo) {


        $this->storage->updateOrgLogo($org, $logo);

    }




    public function getAPIGKs() {

        $apigks = $this->storage->getAPIGKs(200);
        $this->header("List APIGKs");
        $c = 0;
        foreach ($apigks as $apigk) {
            $cinfo = $apigk->getAsArray();
            $cinfo["c"] = ++$c;

            // $this->oneEntry($cinfo);

            echo $this->l($cinfo, [
                "c" => ["%3d", "red"],
                "id" => ["%38s", "black", 38 ],
                "name" => ["%30s", "green", 30],
                "owner" => ["%38s", "green", 38],
                "created" => ["%-45s", "blue", 45],
                "organization" => ["%-45s", "cyan", 45],
                // "scopes" => ["%-90s", "purple", 90],
            ]);

        }





    }


    public function l($data, $fmt) {

        $s = '';


        foreach ($fmt as $k => $f) {
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

    function oneEntry($object) {

        $data = $object->getAsArray();
        if (isset($data['profilephoto'])) {
            $data['profilephoto'] = '----';
        }

        // echo var_export($data, true);

        foreach ($data as $k => $v) {
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
