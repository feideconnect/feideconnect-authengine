<?php

namespace FeideConnect\OAuth\Exceptions;

use FeideConnect\Exceptions;

/**
*
*/
class OAuthException extends Exceptions\Exception {

    public $code, $state = null, $redirectURI = null, $useHashFragment = false;

    function __construct($code, $message, $state = null, $redirectURI = null, $useHashFragment = false) {


        // $httpcode = 500;
        // $head = 'Internal Error';

        $httpcode = 400;
        $head = 'Bad Request';

        if ($code === 'invalid_client') {
            $httpcode = 401;
            $head = 'Unauthorized';
        }
        if ($code === 'invalid_request') {
            $httpcode = 400;
            $head = 'Bad Request';
        }



        parent::__construct($message, $httpcode, $head);

        $this->code = $code;
        $this->state = $state;
        $this->redirectURI = $redirectURI;
        $this->useHashFragment = $useHashFragment;

    }






}
