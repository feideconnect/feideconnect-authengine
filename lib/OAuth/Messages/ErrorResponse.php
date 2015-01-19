<?php


namespace FeideConnect\OAuth\Messages;


class ErrorResponse extends Message {
	
	function __construct($message) {
		
		parent::__construct($message);

		$this->error 				= Message::prequire($message, 'error', array(
			'invalid_request', 'access_denied', 'invalid_client', 'invalid_grant', 'unauthorized_client', 'unsupported_grant_type', 'invalid_scope'
		));
		$this->error_description	= Message::optional($message, 'error_description');
		$this->error_uri			= Message::optional($message, 'error_uri');
		$this->state				= Message::optional($message, 'state');
	}

	public function sendBodyJSON() {


		if($this->error === 'invalid_client') {

			http_response_code(401);
			header('WWW-Authenticate: Bearer realm="feideconnect", error="' . $this->error . '", error_description="' . urlencode($this->error_description) . '"');

		// } else if ($this->error === 'invalid_client') {
		// 	http_response_code(401);

		} else {
			http_response_code(400);
		}

		parent::sendBodyJSON();

	}


}