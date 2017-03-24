<?php

namespace FeideConnect\Data\Models;

use FeideConnect\Data\StorageProvider;
use FeideConnect\OpenIDConnect\IDToken;

class AuthorizationCode extends \FeideConnect\Data\Model {

    public $code, $clientid, $userid, $scope, $token_type, $redirect_uri, $idtoken, $issued, $validuntil, $apigk_scopes, $acr;

    protected static $_properties = [
        'code' => 'uuid',
        'clientid' => 'uuid',
        'userid' => 'uuid',
        'scope' => 'set<text>',
        'token_type' => 'text',
        'redirect_uri' => 'text',
        'idtoken' => 'text',
        'issued' => 'timestamp',
        'validuntil' => 'timestamp',
        'apigk_scopes' => 'map<text,set<text>>',
        'acr' => 'text',
    ];

    public function stillValid() {
        return (!($this->validuntil->inPast()));
    }



    public static function generate(Client $client, User $user, $redirect_uri, $scope, $apigkScopes, IDToken $idtoken = null) {

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
        $n->apigk_scopes = $apigkScopes;

        return $n;
    }


}
