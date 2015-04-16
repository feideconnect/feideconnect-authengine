<?php

namespace FeideConnect\OpenIDConnect;

use FeideConnect\Utils\URL;
use FeideConnect\Config;



/**
* 
*/
class OpenIDConnect {
	
	protected $trustStore;
	protected $issuer;


	function __construct() {

		$this->trustStore = new TrustStore();
		$this->issuer = Config::getValue('connect.issuer');
		$this->expiration = Config::getValue('connect.expires_in', 3600);

	}

	public function getIDtoken($sub, $aud) {


		//  generate(TrustStore $trustStore, $iss, $sub, $aud, $expires_in) {
		$idtoken = IDToken::generate($this->trustStore, $this->issuer, $sub, $aud, $this->expiration);
		return $idtoken;

	}


	public function getJWKs() {

		

	}

	public function getProviderConfiguration() {

		$base = URL::getBaseURL() . 'oauth/';
		$config = [
			'issuer' => Config::getValue('connect.issuer'),
			'authorization_endpoint' => $base . 'authorization',
			'token_endpoint' => $base . 'token',
			'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
			'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
			'userinfo_endpoint' =>  $base . 'userinfo',
			// 'jwks_uri' => $base . 'jwks'
		];		
		return $config;

	}

}