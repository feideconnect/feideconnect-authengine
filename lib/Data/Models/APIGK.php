<?php

namespace FeideConnect\Data\Models;

class APIGK extends \FeideConnect\Data\Model {


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

		if (isset($props["scopedef"])) {
			$this->scopedef = json_decode($props["scopedef"], true);
			unset ($props["scopedef"]);
		}

		parent::__construct($props);
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