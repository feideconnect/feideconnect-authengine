<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;
use FeideConnect\Exceptions\Exception;

class Account {

	public $userids;
	public $realm, $name, $mail, $org, $yob, $sourceID;
	public $photo = null;

	public $attributes;

	function __construct($attributes, $accountMapRules) {
		$this->attributes = $attributes;
		$this->accountMapRules = $accountMapRules;


		if (empty($attributes)) {
			throw new Exception("Loading an account with an empty set of attributes");
		}
		if (empty($accountMapRules)) {
			throw new Exception("Loading an account with an empty attribute map ruleset");
		}


		

		$this->userids = $this->obtainUserIDs();


		$this->realm  = $this->get("realm");
		$this->org    = $this->get("org");
		$this->name   = $this->get("name", '');
		$this->mail   = $this->get("mail", '');
		$this->yob    = $this->get("yob");
		$this->sourceID = $this->get("sourceID", null, true);

		$this->photo  = $this->obtainPhoto();


		// echo '<pre>We got this account map rules: ' . "\n";
		// // print_r($accountMapRules); 
		// // print_r($this->attributes);
		// echo var_dump($this);
		// echo "Age: " . $this->aboveAgeLimit(35) . ".";
		// exit;


	}	


	protected function getComplexRealm($attrname) {


		$value = $this->getValue($attrname, "");
		if (strpos($value, '@') === false) {
			return null;
		}
		// echo "abot to get realm from " . $attrname; exit;
		if (preg_match('/^.+?@(.+?)$/', $value, $matches)) {
			return $matches[1];
		}
		return null;
	}

	protected function getComplexAttrnames($attrnames, $default = null, $required = false) {
		foreach($attrnames AS $attr) {
			if (isset($this->attributes[$attr])) {
				return $this->attributes[$attr][0];
			}
		}
		if ($required) {
			throw new Exception("Missing required attribute [" . join(',', $attrnames) . "]");
		}
		return $default;
	}

	protected function getComplexSourceID($def) {
		$value = '';
		if (!isset($def["prefix"])) {
			throw new Exception("Missing sourceID prefix for attribute map ruleset");
		}

		$value .= $def["prefix"];

		if ($def["realm"]) {
			$value .= ':' . $this->requireRealm();
		}
		return $value;
	}

	protected function getComplex($def, $default = null, $required = false) {

		// If definition contains type = realm.
		if (isset($def["type"]) && $def["type"] === "realm") {

			if (!isset($def["attrname"])) {
				throw new Exception("Missing [attrname] on complex attribute definition");
			}
			$attrname = $def["attrname"];
			return $this->getComplexRealm($attrname);

		// If definition contains type = realm.
		} else if (isset($def["type"]) && $def["type"] === "sourceID") {

			return $this->getComplexSourceID($def);

		} else if (isset($def["attrnames"]) && is_array($def["attrnames"])) {

			$attrnames = $def["attrnames"];
			return $this->getComplexAttrnames($attrnames, $default, $required);

		} else if (isset($def["type"]) && $def["type"] === "fixed") {

			if (!isset($def["value"])) {
				throw new Exception("Missing [value] on complex attribute definition");
			}
			return $def["value"];

		}

		throw new Exception("Unreckognized complex attribute mapping ruleset");

	}


	protected function getValue($property, $default = null, $required = false) {
		if (isset($this->attributes[$property])) {
			return $this->attributes[$property][0];
		}
		if ($required) {
			throw new Exception("Missing required attribute [" . $property . "]");
		}
		return $default;
	}

	protected function get($property, $default = null, $required = false) {

		if (!array_key_exists($property, $this->accountMapRules)) {
			throw new Exception("No defined attribute account map rule for property [" . $property . "]");
		}

		if (is_string($this->accountMapRules[$property])) {
			return $this->getValue($this->accountMapRules[$property], $default, $required);
		} else if(is_array($this->accountMapRules[$property])) {
			return $this->getComplex($this->accountMapRules[$property], $default, $required);
		}
		
		if ($required) {
			throw new Exception("Missing required attribute [" . $property . "]");
		}
		return $default;

	}

	protected function obtainUserIDs() {
		$property = "userid";
		if (!isset($this->accountMapRules[$property])) {
			throw new Exception("No defined attribute account map rule for property [" . $property . "]");
		}

		$useridMap = $this->accountMapRules[$property];
		$userids = [];

		foreach($useridMap AS $prefix => $attrname) {
			if (isset($this->attributes[$attrname])) {
				$userids[] = $prefix . ':' . $this->attributes[$attrname][0];
			}
		}

		return $userids;
	}

	protected function obtainPhoto() {
		$property = "photo";
		if (!array_key_exists($property, $this->accountMapRules)) {
			throw new Exception("No defined attribute account map rule for property [" . $property . "]");
		}
		if ($this->accountMapRules[$property] === null) {
			return null;
		}

		if (isset($this->attributes[$this->accountMapRules[$property]])) {
			$value = $this->attributes[$this->accountMapRules[$property]][0];
			return new AccountPhoto($value);
		}


	}







	public function aboveAgeLimit($ageLimit = 13) {

		$res = true;
		if (isset($this->attributes['feideYearOfBirth']) && 
			is_array($this->attributes['feideYearOfBirth'])
			) {

			$year = intval($this->attributes['feideYearOfBirth'][0]);
			$dayofyear = date("z");
			$thisyear = date("Y");

			$requiredAge = $ageLimit;
			if ($dayofyear < 175) {
				$requiredAge = $ageLimit + 1;
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

	public function getUserIDs() {
		return $this->userids;
	}
	public function getOrg() {
		return $this->org;
	}

	// 	$userids = array();
	// 	foreach($this->accountmap AS $prefix => $attrname) {
	// 		if (isset($this->attributes[$attrname])) {
	// 			$userids[] = $prefix . ':' . $this->attributes[$attrname][0];
	// 		}
	// 	}
	// 	if (count($userids) == 0) {
	// 		throw new Exception('Could not get any userids from this authenticated account.');
	// 	}
	// 	return $userids;
	// }

	public function getRealm() {
		return $this->realm;
	}
	public function getPhoto() {
		return $this->photo;
	}

	public function requireRealm() {
		$realm = $this->getRealm();
		if ($realm === null) {
			throw new Exception('Could not obtain the realm part of this authenticated account.');
		}
		return $realm;
	}




	function getSourceID() {
		return $this->sourceID;
	}

	function getName() {
		return $this->name;
	}

	function getMail() {
		return $this->mail;
	}

}
