<?php

namespace FeideConnect\OAuth\Exceptions;

/**
*
*/
class BadRedirectURIException extends OAuthException {

    public $configuredRedirectURI, $requestedRedirectURI;

    public function __construct($message, $configuredRedirectURI, $requestedRedirectURI) {
        $code = 'invalid_request';
        parent::__construct($code, $message);
        $this->configuredRedirectURI = $configuredRedirectURI;
        $this->requestedRedirectURI = $requestedRedirectURI;
    }
}
