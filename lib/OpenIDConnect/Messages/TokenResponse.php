<?php

namespace FeideConnect\OpenIDConnect\Messages;

use FeideConnect\Data\Models;
use FeideConnect\OAuth\Messages\Message;
use FeideConnect\OAuth\Messages;
use FeideConnect\OpenIDConnect\IDToken;


/**
* 
*/
class TokenResponse extends Messages\TokenResponse {
	
	function __construct($message) {
		
		parent::__construct($message);
		// $this->access_token		= Message::prequire($message, 'access_token');		
		// $this->token_type 		= Message::prequire($message, 'token_type');
		// $this->expires_in		= Message::optional($message, 'expires_in');
		// $this->refresh_token	= Message::optional($message, 'refresh_token');
		// $this->scope			= Message::optional($message, 'scope');
		// $this->state			= Message::optional($message, 'state');

		$this->idtoken			= Message::optional($message, 'idtoken');
	}


	public static function generateFromAccessToken(Models\AccessToken $accesstoken) {
		$a = [
			"access_token" => $accesstoken->access_token,
			"token_type" => $accesstoken->token_type,
		];

		if (isset($accesstoken->validuntil)) $a["expires_in"] = $accesstoken->validuntil->getInSeconds();
		if (isset($accesstoken->refresh_token)) $a["refresh_token"] = $accesstoken->refresh_token;
		if (isset($accesstoken->scope)) $a["scope"] = join(' ', $accesstoken->scope);

		$n = new self($a);
		return $n;

	}


	public static function generateWithIDtoken(AuthorizationRequest $request, Models\AccessToken $accesstoken, IDToken $idtoken) {

		$a = [
			"access_token" => $accesstoken->access_token,
			"token_type" => $accesstoken->token_type,
			"idtoken" => $idtoken->getEncoded(),
		];

		if (isset($accesstoken->validuntil)) $a["expires_in"] = $accesstoken->validuntil->getInSeconds();
		if (isset($accesstoken->refresh_token)) $a["refresh_token"] = $accesstoken->refresh_token;
		if (isset($accesstoken->scope)) $a["scope"] = join(' ', $accesstoken->scope);

		if (isset($request->state)) {
			$a["state"] = $request->state;
		}


		$n = new self($a);
		return $n;


	}

	public static function generate(AuthorizationRequest $request, Models\AccessToken $accesstoken) {
		$a = [
			"access_token" => $accesstoken->access_token,
			"token_type" => $accesstoken->token_type,
		];

		if (isset($accesstoken->validuntil)) $a["expires_in"] = $accesstoken->validuntil->getInSeconds();
		if (isset($accesstoken->refresh_token)) $a["refresh_token"] = $accesstoken->refresh_token;
		if (isset($accesstoken->scope)) $a["scope"] = join(' ', $accesstoken->scope);

		if (isset($request->state)) {
			$a["state"] = $request->state;
		}


		$n = new self($a);
		return $n;
	}

}