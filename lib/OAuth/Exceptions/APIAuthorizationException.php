<?php

namespace FeideConnect\OAuth\Exceptions;
use FeideConnect\Exceptions;

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

	function showJSON() {


		http_response_code($this->httpcode);
		if ($this->type !== null) {
			header('WWW-Authenticate: Bearer realm="feideconnect", error="' . $this->type . '", error_description="' . urlencode($this->getMessage()) . '"');	
		}
		header('Content-Type: application/json; charset=utf-8');
		$data = [
			'error' => $this->head,
			'message' => $this->getMessage(),
		];
		echo json_encode($data, JSON_PRETTY_PRINT);
		exit;

	}


}