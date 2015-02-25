<?php

namespace FeideConnect\Data\Models;
use FeideConnect\Data\StorageProvider;

class AccessToken extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"access_token", "clientid", "userid", "issued", 
		"scope", "token_type", "validuntil", "lastuse"
	);


	public static function generateFromCode(Models\AuthorizationCode $code) {



	}	


	public function hasExactScopes($scopes) {
		assert('is_array($scopes)');

		if (empty($scopes) && empty($this->scope)) return true;
		if (empty($scopes)) return false;
		if (empty($this->scope)) return false;

		$r1 = array_diff($scopes, $this->scope);
		if (!empty($r1)) return false;

		$r2 = array_diff($this->scope, $scopes);
		if (!empty($r2)) return false;

		return true;
	}



	public function hasScopes($scopes) {

		if (empty($scopes)) return true;
		if (empty($this->scope)) return false;

		foreach($this->scope AS $scope) {
			if (!in_array($scope, $this->scope)) {
				return false;
			}
		}
		return true;
	}


	public function stillValid() {
		$now = time();
		return ($this->validuntil > $now);
	}



	public static function generate($client, $user, $scope = null, $refreshtoken = true, $expires_in = 3600) {

		// $expires_in = \FeideConnect\Config::getValue('oauth.token.lifetime', 3600);


		$n = new self();

		$n->clientid = $client->id;

		$n->userid = '';
		if ($user !== null) {
			$n->userid = $user->userid;	
		}
	
		$n->issued = time();
		$n->validuntil = time() + $expires_in;
		$n->access_token = self::genUUID();

		if ($refreshtoken) {
			$n->refresh_token = self::genUUID();
		}

		$n->token_type = 'Bearer';
		
		if ($scope !== null) {
			$n->scope = $scope;
		}
		return $n;
	}


}