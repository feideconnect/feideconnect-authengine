<?php

namespace FeideConnect\OAuth\Messages;

/**
*
*/
class TokenRequest extends Message {

    public function __construct($message) {

        parent::__construct($message);

        $this->grant_type = Message::prequire($message, 'grant_type', array('authorization_code', 'refresh_token', 'client_credentials', 'password'));
        $this->code = Message::optional($message, 'code');
        $this->redirect_uri = Message::optional($message, 'redirect_uri');
        $this->client_id = Message::optional($message, 'client_id');
        $this->client_secret = Message::optional($message, 'client_secret');

        $this->username = Message::optional($message, 'username');
        $this->password = Message::optional($message, 'password');

        // Used in Client Credentials Grant flow.
        $this->scope = Message::spacelist(Message::optional($message, 'scope'));

    }


    public function getScopeList() {
        if (empty($this->scope)) {
            return [];
        }
        return $this->scope;
    }

    public function toLog() {
        $data = parent::toLog();
        foreach (['client_secret', 'username', 'password'] as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
