<?php

namespace FeideConnect\Data\Models;

use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Types\Timestamp;
use FeideConnect\Utils\Misc;

class AccessToken extends \FeideConnect\Data\Model {

    public $access_token, $clientid, $userid, $issued, $scope, $token_type, $validuntil, $lastuse, $apigkid, $subtokens;

    protected static $_properties = [
        'access_token' => 'uuid',
        'clientid' => 'uuid',
        'userid' => 'uuid',
        'issued' => 'timestamp',
        'scope' => 'set<text>',
        'token_type' => 'text',
        'validuntil' => 'timestamp',
        'lastuse' => 'timestamp',
        'apigkid' => 'text',
        'subtokens' => 'map<text,uuid>',
    ];

    public static function lifetimeCmp($a, $b) {
        return -Timestamp::cmp($a->validuntil, $b->validuntil);
    }

    public function hasExactScopes($scopes) {
        assert('is_array($scopes)');

        return Misc::containsSameElements(Misc::ensureArray($this->scope), $scopes);
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



    public static function generate($client, $user, $apigkid, $scope, $validuntil) {

        // $expires_in = \FeideConnect\Config::getValue('oauth.token.lifetime', 3600);


        $n = new self();

        $n->clientid = $client->id;

        $n->userid = '00000000-0000-0000-0000-000000000000';
        if ($user !== null) {
            $n->userid = $user->userid;
        }

        $n->apigkid = $apigkid;
        if (empty($n->apigkid)) {
            $n->apigkid = '';
        }

        $n->issued = new Timestamp();
        $n->validuntil = $validuntil;

        $n->access_token = self::genUUID();

        $n->token_type = 'Bearer';

        $n->scope = $scope;

        return $n;
    }

    public function toLog() {
        return md5($this->access_token);
    }
}
