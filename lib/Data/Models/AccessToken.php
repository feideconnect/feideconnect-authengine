<?php

namespace FeideConnect\Data\Models;

use FeideConnect\Data\StorageProvider;
use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionMap;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;

class AccessToken extends \FeideConnect\Data\Model {

    public $access_token, $clientid, $userid, $issued, $scope, $token_type, $validuntil, $lastuse;

    protected static $_properties = array(
        "access_token", "clientid", "userid", "issued",
        "scope", "token_type", "validuntil", "lastuse"
    );
    protected static $_types = [
        "issued" => "timestamp",
        "validuntil" => "timestamp",
        "lastuse" => "timestamp"
    ];


    public function getStorableArray() {

        $prepared = parent::getStorableArray();

        if (isset($this->access_token)) {
            $prepared["access_token"] = new Uuid($this->access_token);
        }
        if (isset($this->clientid)) {
            $prepared["clientid"] = new Uuid($this->clientid);
        }

        if (empty($this->userid)) {
            $prepared["userid"] = new Uuid('00000000-0000-0000-0000-000000000000');
        } else {
            $prepared["userid"] = new Uuid($this->userid);
        }
        if (isset($this->scope)) {
            $prepared["scope"] = new CollectionSet($this->scope, Base::ASCII);
        }


        return $prepared;
    }



    public static function generateFromCode(Models\AuthorizationCode $code) {



    }


    public function hasExactScopes($scopes) {
        assert('is_array($scopes)');

        if (empty($scopes) && empty($this->scope)) {
            return true;
        }
        if (empty($scopes)) {
            return false;
        }
        if (empty($this->scope)) {
            return false;
        }

        $r1 = array_diff($scopes, $this->scope);
        if (!empty($r1)) {
            return false;
        }

        $r2 = array_diff($this->scope, $scopes);
        if (!empty($r2)) {
            return false;
        }

        return true;
    }



    public function hasScopes($scopes) {

        if (empty($scopes)) {
            return true;
        }
        if (empty($this->scope)) {
            return false;
        }

        foreach ($scopes as $scope) {
            if (!in_array($scope, $this->scope)) {
                return false;
            }
        }
        return true;
    }


    public function stillValid() {
        return (!($this->validuntil->inPast()));
    }



    public static function generate($client, $user, $scope = null, $refreshtoken = true, $expires_in = 3600) {

        // $expires_in = \FeideConnect\Config::getValue('oauth.token.lifetime', 3600);


        $n = new self();

        $n->clientid = $client->id;

        $n->userid = null;
        if ($user !== null) {
            $n->userid = $user->userid;
        }

        $n->issued = new \FeideConnect\Data\Types\Timestamp();
        $n->validuntil = (new \FeideConnect\Data\Types\Timestamp())->addSeconds($expires_in);

        $n->access_token = self::genUUID();

        if ($refreshtoken) {
            $n->refresh_token = self::genUUID();
        }

        $n->token_type = 'Bearer';

        if ($scope !== null) {
            $n->scope = $scope;
        }
        return $n;
    }

    public function toLog() {
        return md5($this->access_token);
    }
}
