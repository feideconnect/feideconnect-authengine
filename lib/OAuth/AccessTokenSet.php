<?php


namespace FeideConnect\OAuth;

class AccessTokenSet {

	protected $tokens = [];

	function __construct($tokens) {
		if (!empty($tokens)) {
			foreach ($tokens AS $token) {
				$this->tokens[] = $token;
			}
		}
	}

	public function dup() {
		return new AccessTokenSet($this->tokens);
	}

	public function filter() {



	}



}