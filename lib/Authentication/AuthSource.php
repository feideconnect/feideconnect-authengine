<?php

namespace FeideConnect\Authentication;


/**
 * This class handles all authentication, and uses SimpleSAMLphp for that task.
 * It will also handle all local user creation. All new users will be stored in the user repository.
 * 
 */
class AuthSource {
	protected static $instance;
	protected $factory;

	protected function __construct() {
		$this->factory = function($type) {
			return new \SimpleSAML_Auth_Simple($type);
		};
	}

	protected static function instance() {
		if (self::$instance === null) {
			self::$instance = new AuthSource();
		}
		return self::$instance;
	}

	public static function create($type) {
		$f = self::instance()->getFactory();
		return $f($type);
	}

	public static function setFactory($factory) {
		self::instance()->factory = $factory;
	}

	protected function getFactory() {
		return $this->factory;
	}
}
