<?php

namespace FeideConnect\OAuth\Messages;

use FeideConnect\Data\Models;


/**
* 
*/
class TokenResponse extends Message {
    
    function __construct($message) {
        
        parent::__construct($message);
        $this->access_token        = Message::prequire($message, 'access_token');        
        $this->token_type         = Message::prequire($message, 'token_type');
        $this->expires_in        = Message::optional($message, 'expires_in');
        $this->refresh_token    = Message::optional($message, 'refresh_token');
        $this->scope            = Message::optional($message, 'scope');
        $this->state            = Message::optional($message, 'state');

        // Used with OpenID Connect
        $this->idtoken            = Message::optional($message, 'idtoken');

    }


    public static function generate(Models\AccessToken $accesstoken, $state = null, $idtoken = null) {
        $a = [
            "access_token" => $accesstoken->access_token,
            "token_type" => $accesstoken->token_type,
        ];

        if (isset($accesstoken->validuntil)) $a["expires_in"] = $accesstoken->validuntil->getInSeconds();
        if (isset($accesstoken->refresh_token)) $a["refresh_token"] = $accesstoken->refresh_token;
        if (isset($accesstoken->scope)) $a["scope"] = join(' ', $accesstoken->scope);

        if (!empty($state)) {
            $a["state"] = $state;
        }
        if ($idtoken !== null) {
            $a["idtoken"] = $idtoken->getEncoded();
        }

        $n = new self($a);
        return $n;
    }

    public function toLog() {
        $log = parent::toLog();
        unset($log['access_token']);
        return $log;
    }
}