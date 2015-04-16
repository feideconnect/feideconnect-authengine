<?php

namespace FeideConnect\OpenIDConnect;

class IDToken {

	protected $trustStore;

	protected $encoded;
	protected $object;


/**
 * 
 * nonce
 * String value used to associate a Client session with an ID Token, and to mitigate replay attacks. 
 * The value is passed through unmodified from the Authentication Request to the ID Token. If present in the ID Token, Clients MUST verify that the nonce Claim Value is equal 
 * to the value of the nonce parameter sent in the Authentication Request. If present in the Authentication Request, Authorization Servers MUST include a nonce Claim in the ID Token 
 * with the Claim Value being the nonce value sent in the Authentication Request. Authorization Servers SHOULD perform no other processing on nonce values used. The nonce value is 
 * a case sensitive string.
 *
 * auth_time
 * Time when the End-User authentication occurred. Its value is a JSON number representing the number of seconds from 1970-01-01T0:0:0Z as measured in UTC until the date/time. 
 * When a max_age request is made or when auth_time is requested as an Essential Claim, then this Claim is REQUIRED; otherwise, its inclusion is OPTIONAL. (The auth_time Claim 
 * semantically corresponds to the OpenID 2.0 PAPE [OpenID.PAPE] auth_time response parameter.)
 * 
 */



/**
 *
 * The contents of the ID Token are as described in Section 2. When using the Authorization Code Flow, these additional requirements for the following ID Token Claims apply:
 * 
 * at_hash
 * OPTIONAL. Access Token hash value. Its value is the base64url encoding of the left-most half of the hash of the octets of the ASCII 
 * representation of the access_token value, where the hash algorithm used is the hash algorithm used in the alg Header Parameter of the 
 * ID Token's JOSE Header. For instance, if the alg is RS256, hash the access_token value with SHA-256, then take the left-most 128 bits 
 * and base64url encode them. The at_hash value is a case sensitive string.
 *
 * 
 */


	function __construct(TrustStore $trustStore) {

		$this->trustStore = $trustStore;
		$this->object = [];

		// $this->object = array(
		// 	"iss" => "http://example.org",
		// 	"aud" => "http://example.com",
		// 	"iat" => 1356999524,
		// 	"nbf" => 1357000000
		// );


		// $decoded = JWT::decode($jwt, $key, array('HS256'));

		// print_r($decoded);

		/*
		 NOTE: This will now be an object instead of an associative array. To get
		 an associative array, you will need to cast it as such:
		*/

		// $decoded_array = (array) $decoded;

		 // echo '<pre>'; print_r($jwt); exit;

	}

	public function set($key, $val) {
		$this->object[$key] = $val;
		return $this;
	}

	function getEncoded() {

		/**
		 * IMPORTANT:
		 * You must specify supported algorithms for your application. See
		 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
		 * for a list of spec-compliant algorithms.
		 */
		$this->encoded = \JWT::encode($this->object, $this->trustStore->getKey(), $this->trustStore->getSigningAlg());

		return $this->encoded;
	}

	function getObject() {
		return $this->object;
	}

	public static function generate(TrustStore $trustStore, $iss, $sub, $aud, $expiresIn) {

		$now = time();

		$idtoken = new IDToken($trustStore);
		$idtoken->set("iss", $iss)
			->set("aud", $aud)
			->set("sub", $sub)
			->set("iat", time())
			->set("exp", $now + $expiresIn);
		return $idtoken;

	}


}