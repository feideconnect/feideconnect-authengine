<?php

namespace FeideConnect\Data\Models;

class User extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"userid", "created", "email", "name", 
		"profilephoto", "userid_sec"
	);



	// public function debug() {

		
	// 	print_r(self::props($this));

	// }

	// protected static function props($a) {
	// 	return get_object_vars($a);
	// }


}