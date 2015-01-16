<?php

namespace FeideConnect\Data\Models;

class Client extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"id", "client_secret", "created", "descr", "name", "owner", 
		"redirect_uri", "scopes", "scopes_requested", "status", "type", "updated"
	);


	public function getScopeList() {
		if (empty($this->scopes)) return [];
		return $this->scopes;
	}

	// public function debug() {

		
	// 	print_r(self::props($this));

	// }

	// protected static function props($a) {
	// 	return get_object_vars($a);
	// }


}