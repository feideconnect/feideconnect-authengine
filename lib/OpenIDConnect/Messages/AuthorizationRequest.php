<?php

namespace FeideConnect\OpenIDConnect\Messages;

use FeideConnect\OAuth\Messages\Message;

/**
*
*/
class AuthorizationRequest extends \FeideConnect\OAuth\Messages\AuthorizationRequest {

    protected static $response_types = [ 'code', 'id_token token' ];

    public function __construct($message) {

        parent::__construct($message);

        try {
            $this->redirect_uri        = Message::prequire($message, 'redirect_uri');
            $this->response_mode    = Message::optional($message, 'response_mode');
            $this->nonce            = Message::optional($message, 'nonce');
            $this->display            = Message::optional($message, 'display');
            $this->prompt            = Message::optional($message, 'prompt');
            $this->max_age            = Message::optional($message, 'max_age');
            $this->id_token_hint    = Message::optional($message, 'id_token_hint');
            $this->login_hint        = Message::optional($message, 'login_hint');
            $this->acr_values        = Message::spacelist(Message::optional($message, 'acr_values'));
        } catch (OAuthException $exc) {
            $this->exception = $exc;
        }

        // These parameters are inherited from the OAuth request.

        // $this->response_type    = Message::prequire($message, 'response_type', ['code', 'token'], true);
        // $this->client_id         = Message::prequire($message, 'client_id');
        // $this->scope            = Message::spacelist(Message::optional($message, 'scope'));
        // $this->state            = Message::optional($message, 'state');

    }


    /**
     * Will return true if the prompt parameter is present in the request and includes a value of 'none'.
     * @return boolean [description]
     */
    public function isPassiveRequest() {
        if (empty($this->prompt)) {
            return false;
        }
        $values = explode(' ', $this->prompt);
        return in_array("none", $values);
    }

    public function loginPromptRequested() {
        if (empty($this->prompt)) {
            return false;
        }
        $values = explode(' ', $this->prompt);
        return in_array("login", $values);
    }


}
