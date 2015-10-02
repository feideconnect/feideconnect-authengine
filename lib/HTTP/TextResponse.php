<?php


namespace FeideConnect\HTTP;

use FeideConnect\HTTP\HTTPResponse;

class TextResponse extends HTTPResponse {



	protected $txt;

	function __construct($txt) {
		parent::__construct();
		$this->txt = $txt;
		$this->setCORS(false);
		$this->setHeader("Content-Type", "text/plain; charset=utf-8");
	}

	protected function sendBody() {
		echo $this->txt;
	}


}


