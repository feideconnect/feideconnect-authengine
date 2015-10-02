<?php

namespace FeideConnect\OAuth\Exceptions;
use FeideConnect\Exceptions;
use FeideConnect\HTTP\JSONResponse;

/**
*
*/
class APIAuthorizationException extends Exceptions\Exception {


    protected $type = null;

    // protected $code, $state;
    function __construct($message, $type = null) {

        $this->type = $type;

        $httpcode = 401;
        $head = 'Unauthorized';

        switch($type) {
            case 'invalid_request':
                $httpcode = 400;
                $head = 'Bad Request';
                break;

            case 'invalid_token':
                $httpcode = 401;
                $head = 'Invalid Token';
                break;

            case 'insufficient_scope':
                $httpcode = 403;
                $head = 'Insufficient OAuth Scopes';
                break;
        }

        parent::__construct($message, $httpcode, $head);

    }

    function getData() {
        $data = [
            'error' => $this->head,
            'message' => $this->getMessage(),
        ];
        return $data;
    }

    function getJSONResponse() {

        $response = new JSONResponse($this->getData());
        $response->setStatus($this->httpcode);

        if ($this->type !== null) {
            $response->setHeader('WWW-Authenticate',
                'Bearer realm="feideconnect", error="' . $this->type . '", error_description="' . urlencode($this->getMessage()) . '"');
        }
        return $response;
    }


}
