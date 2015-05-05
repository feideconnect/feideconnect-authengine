<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;

class Account {


	public $realm, $name, $mail;
	public $photo = null;

	public $attributes;

	function __construct($attributes) {
		$this->attributes = $attributes;


		$this->accountmap = Config::getValue('account.useridmap', [
			"feide" => "eduPersonPrincipalName",
			"mail" => "mail",
			"nin" => "norEduPersonNIN"
		]);

		$this->realm = Config::getValue('account.realm', "eduPersonPrincipalName");
		$this->name = Config::getValue('account.name', ["displayName", "cn"]);
		$this->mail = Config::getValue('account.mail', "mail");

		if (isset($this->attributes['jpegPhoto']) && is_array($this->attributes['jpegPhoto'])) {
			$this->photo = new AccountPhoto($this->attributes['jpegPhoto'][0]);
		}

	}


	public function getOrg() {
		if (isset($this->attributes['o']) && 
			is_array($this->attributes['o'])
			) {
			return $this->attributes['o'][0];
		}
		return null;
	}


	public function aboveAgeLimit() {

		$res = true;
		if (isset($this->attributes['feideYearOfBirth']) && 
			is_array($this->attributes['feideYearOfBirth'])
			) {

			$year = intval($this->attributes['feideYearOfBirth'][0]);
			$dayofyear = date("z");
			$thisyear = date("Y");

			$requiredAge = 13;
			if ($dayofyear < 175) {
				$requiredAge = 14;
			}
			
			$age = $thisyear - $year;
			
			// echo "Reqired age is  " . $requiredAge . " ";
			// echo "Age is " . $age;
			if ($age >= $requiredAge) {
				$res = true;
			} else {
				$res = false;
			}
		
		}
		return $res;

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

	public function getRealm() {
		if (isset($this->attributes[$this->realm])) {
			if (preg_match('/^.+?@(.+?)$/', $this->attributes[$this->realm][0], $matches)) {
				return $matches[1];
			}
		}
		return null;

	}

	public function requireRealm() {
		$realm = $this->getRealm();
		if ($realm === null) {
			throw new Exception('Could not obtain the realm part of this authenticated account.');
		}
		return $realm;
	}


	function getPhoto() {
		if ($this->photo === null) return null;
		return $this->photo->getPhoto();
	}

	function getSourceID() {
		return "feide:" . $this->requireRealm();
	}

	function getName() {
		foreach($this->name AS $nattr) {
			if (isset($this->attributes[$nattr])) {
				return $this->attributes[$nattr][0];
			}			
		}
		throw new \Exception('Could not get a name for the authenticated user');
	}

	function getMail() {
		if (isset($this->attributes[$this->mail])) {
			return $this->attributes[$this->mail][0];
		}
		return '';
	}

}
