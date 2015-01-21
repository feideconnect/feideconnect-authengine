<?php

namespace FeideConnect\Authentication;

class Account {

	// TODO All attribute names and things needs to be put into config object, 
	// or plugin arch.
	// 
	// Will wait and decide later how this is best implemented. Some parts are not that 
	// easy to put in config files. Such as extraction of realms.
	// 
	// Will depend on both idp and authsource or one of them.

	public $realm, $name, $mail;
	public $photo = null;

	public $attributes;

	function __construct($attributes) {
		$this->attributes = $attributes;

		$this->accountmap = [
			"feide" => "eduPersonPrincipalName",
			"mail" => "mail",
		];
		$this->realm = "eduPersonPrincipalName";
		$this->name = "displayName";
		$this->mail = "mail"; 

		if (isset($this->attributes['jpegPhoto']) && is_array($this->attributes['jpegPhoto'])) {
			$this->photo = new AccountPhoto($this->attributes['jpegPhoto'][0]);
		}

	}

	function getUserIDs() {
		$userids = array();
		foreach($this->accountmap AS $prefix => $attrname) {
			if (isset($this->attributes[$attrname])) {
				$userids[] = $prefix . ':' . $this->attributes[$attrname][0];
			}
		}
		if (count($userids) == 0) {
			throw new Exception('Could not get any userids from this authenticated account.');
		}
		return $userids;
	}

	protected function getRealm() {
		if (isset($this->attributes[$this->realm])) {
			if (preg_match('/^.+?@(.+?)$/', $this->attributes[$this->realm][0], $matches)) {
				return $matches[1];
			}
		}
		throw new Exception('Could not obtain the realm part of this authenticated account.');
	}

	function getPhoto() {
		if ($this->photo === null) return null;
		return $this->photo->getPhoto();
	}

	function getSourceID() {
		return "feide:" . $this->getRealm();
	}

	function getName() {
		if (isset($this->attributes[$this->name])) {
			return $this->attributes[$this->name][0];
		}
		return null;
	}

	function getMail() {
		if (isset($this->attributes[$this->mail])) {
			return $this->attributes[$this->mail][0];
		}
		return null;
	}

}