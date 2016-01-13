<?php

namespace FeideConnect\Data\Models;

use FeideConnect\Data\StorageProvider;
use FeideConnect\OpenIDConnect\IDToken;

use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionMap;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;

class AuthorizationCode extends \FeideConnect\Data\Model {

    public $code, $clientid, $userid, $scope, $token_type, $redirect_uri, $idtoken, $issued, $validuntil;

    protected static $_properties = array(
        "code", "clientid", "userid",
        "scope", "token_type", "redirect_uri",
        "idtoken",
        "issued", "validuntil"
    );
    protected static $_types = [
        "issued" => "timestamp",
        "validuntil" => "timestamp"
    ];


    public function getStorableArray() {

        $prepared = parent::getStorableArray();


        if (isset($this->code)) {
            $prepared["code"] = new Uuid($this->code);
        }
        if (isset($this->clientid)) {
            $prepared["clientid"] = new Uuid($this->clientid);
        }
        if (isset($this->userid)) {
            $prepared["userid"] = new Uuid($this->userid);
        }

        if (isset($this->scope)) {
            $prepared["scope"] = new CollectionSet($this->scope, Base::ASCII);
        }


        return $prepared;
    }


    public function stillValid() {
        return (!($this->validuntil->inPast()));
    }



    public static function generate(Client $client, User $user, $redirect_uri, $scope, IDToken $idtoken = null) {

        $expires_in = \FeideConnect\Config::getValue('oauth.code.lifetime', 5*60);

        $n = new self();

        $n->code = self::genUUID();

        $n->clientid = $client->id;
        $n->userid = $user->userid;

        $n->issued = new \FeideConnect\Data\Types\Timestamp();
        $n->validuntil = (new \FeideConnect\Data\Types\Timestamp())->addSeconds($expires_in);

        $n->token_type = 'Bearer';

        if (isset($idtoken) && $idtoken !== null) {
            $n->idtoken = $idtoken->getEncoded();
        }

        $n->redirect_uri = $redirect_uri;

        $n->scope = $scope;

        return $n;
    }


}
