<?php


namespace FeideConnect\OAuth\Messages;

/**
*
*/
class AuthorizationRequest extends Message {

    function __construct($message) {

        parent::__construct($message);
        $this->response_type    = Message::prequire($message, 'response_type', ['code', 'token'], true);
        $this->client_id         = Message::prequire($message, 'client_id');
        $this->redirect_uri        = Message::optional($message, 'redirect_uri');
        $this->scope            = Message::spacelist(Message::optional($message, 'scope'));
        $this->state            = Message::optional($message, 'state');

    }

    public function getState() {
        if (isset($this->state) && $this->state !== null) {
            return $this->state;
        }
        return null;
    }

    public function getScopeList() {
        if (empty($this->scope)) {
            return [];
        }
        return $this->scope;
    }

    public function useHashFragment() {
        if (isset($this->response_type) && $this->response_type === 'token') {
            return true;
        }
        return false;
    }

}
