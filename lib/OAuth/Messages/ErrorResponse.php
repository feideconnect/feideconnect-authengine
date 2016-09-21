<?php

namespace FeideConnect\OAuth\Messages;

use FeideConnect\HTTP\JSONResponse;

class ErrorResponse extends Message {

    public function __construct($message) {

        parent::__construct($message);

        $this->error                 = Message::prequire($message, 'error', array(
            'invalid_request', 'access_denied', 'invalid_client', 'invalid_grant', 'unauthorized_client', 'unsupported_grant_type', 'invalid_scope'
        ));
        $this->error_description    = Message::optional($message, 'error_description');
        $this->error_uri            = Message::optional($message, 'error_uri');
        $this->state                = Message::optional($message, 'state');
    }

    public function getJSONResponse($httpcode = 200) {
        $response = parent::getJSONResponse();

        if ($httpcode === 401) {
            $response->setHeader("WWW-Authenticate", 'Basic realm="feideconnect", error="' . $this->error . '", error_description="' . urlencode($this->error_description));
        }
        $response->setStatus($httpcode);

        return $response;

    }


}
