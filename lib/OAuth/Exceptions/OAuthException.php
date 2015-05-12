<?php

namespace FeideConnect\OAuth\Exceptions;
use FeideConnect\Exceptions;

/**
* 
*/
class OAuthException extends Exceptions\Exception {
	
	public $code, $state;
	function __construct($code, $message, $state = null) {


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

	}


	
}