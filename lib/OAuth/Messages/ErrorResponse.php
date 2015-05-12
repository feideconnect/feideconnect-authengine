<?php


namespace FeideConnect\OAuth\Messages;

use FeideConnect\HTTP\JSONResponse;


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

		$body = array();
		foreach($this AS $key => $value) {
			if (empty($value)) continue;
			$body[$key] = $value;
		}

		$response = new JSONResponse($body);
		$response->setHeader("WWW-Authenticate", 'Bearer realm="feideconnect", error="' . $this->error . '", error_description="' . urlencode($this->error_description));
		$response->setStatus($this->httpcode);
		// if($this->error === 'invalid_client') {
		// 	$response->setStatus(401);
		// } else {
		// 	$response->setStatus(400);
		// }
		
		return $response;

	}


}