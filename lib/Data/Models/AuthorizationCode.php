<?php

namespace FeideConnect\Data\Models;
use FeideConnect\Data\StorageProvider;

class AuthorizationCode extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"code", "clientid", "userid", 
		"scope", "token_type", "redirect_uri",
		"issued", "validuntil"
	);


	public function stillValid() {
		$now = time();
		return ($this->validuntil > $now);
	}



	public static function generate(Client $client, User $user, $redirect_uri, $scope = null) {

		$expires_in = \FeideConnect\Config::getValue('oauth.code.lifetime', 5*60);

		$storage = StorageProvider::getStorage();
		$n = new self($storage);

		$n->code = self::genUUID();
		
		$n->clientid = $client->id;
		$n->userid = $user->userid;

		$n->issued = time();
		$n->validuntil = time() + $expires_in;

		$n->token_type = 'Bearer';

		$n->redirect_uri = $redirect_uri;
		
		if ($scope !== null) {
			$n->scope = $scope;
		}
		return $n;
	}


}