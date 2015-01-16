<?php


namespace FeideConnect\Utils;

class Validator {

	public static function validateID($id) {
		if (preg_match('/^([a-zA-Z0-9\-]+)$/', $id, $matches)) {
			return true;
		}
		return false;
	}

}
