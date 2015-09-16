<?php

namespace FeideConnect\Exceptions;
use FeideConnect\HTTP\Redirect;

/**
* 
*/
class RedirectException extends \Exception {
	
	function __construct($url) {
		parent::__construct($url);
		$this->response = new Redirect($url);
	}

	function getHTTPResponse() {
		return $this->response;
	}

}
