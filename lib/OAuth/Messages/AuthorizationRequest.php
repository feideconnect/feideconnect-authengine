<?php


namespace FeideConnect\OAuth\Messages;

use FeideConnect\OAuth\Exceptions\OAuthException;

/**
*
*/
class AuthorizationRequest extends Message {

    protected static $response_types = ['code', 'token'];
    protected $exception;

    public function __construct($message) {

        parent::__construct($message);
        try {
            $this->client_id        = Message::prequire($message, 'client_id');
            $this->redirect_uri     = Message::optional($message, 'redirect_uri');
            $this->scope            = Message::spacelist(Message::optional($message, 'scope'));
            $this->response_type    = Message::prequire($message, 'response_type', static::$response_types, false);
            $this->state            = Message::optional($message, 'state');
            $this->exception = null;
        } catch (OAuthException $exc) {
            $this->exception = $exc;
        }
    }

    public function validate() {
        if ($this->exception) {
            throw $this->exception;
        }
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
