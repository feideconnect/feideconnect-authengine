<?php

namespace FeideConnect\Exceptions;

use FeideConnect\HTTP\Redirect;

/**
*
*/
class RedirectException extends \Exception {

    public function __construct($url) {
        parent::__construct($url);
        $this->response = new Redirect($url);
    }

    public function getHTTPResponse() {
        return $this->response;
    }

}
