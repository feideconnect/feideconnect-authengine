<?php

namespace FeideConnect\OAuth\Exceptions;

use FeideConnect\Exceptions;

/**
*
*/
class UserCannotAuthorizeException extends Exceptions\Exception {

    public function __construct() {
        parent::__construct("User can not authorize");
    }

}
