<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\Uuid;

class APIGK extends \FeideConnect\Data\Model {

	public $id, $name, $descr, $owner, $organization, $endpoints, $expose, $httpscertpinned, $requireuser, $scopedef, $trust, $logo, $status, $created, $updated;

	protected static $_properties = array(
		"id", "name", "descr", 
		"owner", "organization",  "endpoints", "expose", "httpscertpinned", "requireuser", "scopedef", "trust", "logo",
		"status", "created", "updated"
	);
	protected static $_types = [
		"created" => "timestamp",
		"updated" => "timestamp"
	];


	function __construct($props) {

		parent::__construct($props);

		if (isset($props["scopedef"])) {
			$this->scopedef = json_decode($props["scopedef"], true);
			unset ($props["scopedef"]);
		}


	}

	public function getStorableArray() {
		$data = parent::getStorableArray();
		if (isset($data['scopedef'])) {
			$data['scopedef'] = json_encode($data['scopedef']);
		}
		if (isset($this->owner)) {
			$data["owner"] =  new Uuid($this->owner);
		}
		return $data;
	}

	function getBasicScopeView() {
		$sd = [
			"title" => "Basic access",
			"descr" => "Basic access to this API."
		];
		if (isset($this->scopedef) && isset($this->scopedef->title)) {
			$sd["title"] = $this->scopedef->title;
		}
		if (isset($this->scopedef) && isset($this->scopedef->descr)) {
			$sd["descr"] = $this->scopedef->descr;
		}
		return $sd;
	}

	function getSubScopeView($subscope) {
		$sd = [
			"title" => "Unknown subscope [" . $subscope . "]",
			"descr" => "Unknown subscope [" . $subscope . "]"
		];
		if (isset($this->scopedef) && isset($this->scopedef["subscopes"]) && isset($this->scopedef["subscopes"][$subscope])  && isset($this->scopedef["subscopes"][$subscope])) {
			$sd = $this->scopedef["subscopes"][$subscope];
		}

		return $sd;
	}


}