<?php

namespace FeideConnect\OAuth\Messages;

use FeideConnect\Data\Models;

/**
*
*/
class AuthorizationResponse extends Message {

    protected function __construct($message) {

        parent::__construct($message);
        $this->code        = Message::prequire($message, 'code');
        $this->state     = Message::optional($message, 'state');

    }


    public static function generate(AuthorizationRequest $request, Models\AuthorizationCode $code) {
        $a = [
            "code" => $code->code
        ];
        if (isset($request->state)) {
            $a["state"] = $request->state;
        }
        $n = new self($a);
        return $n;
    }

}
