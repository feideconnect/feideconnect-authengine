<?php

namespace FeideConnect\OpenIDConnect;

use FeideConnect\Config;

class TrustStore {

	protected $key;

	function __construct() {

		$keyfile = Config::dir('etc', '/key.pem');

		$this->key = file_get_contents($keyfile);

		// echo '<pre>File';
		// echo $keyfile;
		//  print_r($this->key); exit;

	}

	function getSigningAlg() {
		return 'RS256';
	}

	function getKey() {
		return $this->key;
	}

}