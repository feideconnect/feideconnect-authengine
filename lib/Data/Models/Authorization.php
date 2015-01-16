<?php

namespace FeideConnect\Data\Models;

class Authorization extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"clientid", "userid", "scopes", "issued"
	);

	public function getScopeList() {
		if (empty($this->scopes)) return [];
		return $this->scopes;
	}

	public function addScopes($scopes) {
		if (empty($this->scopes)) $this->scopes = [];
		foreach($scopes AS $s) {
			if (!in_array($s, $this->scopes)) {
				$this->scopes[] = $s;
			}
		}
	}

	function includeScopes($requiredscopes) {

		$myScopes = $this->getScopeList();

		if ($requiredscopes === null) return true;
		// echo '<pre>'; print_r($requiredscopes); exit;
		assert('is_array($requiredscopes)');
		foreach($requiredscopes AS $rs) {
			if (!in_array($rs, $myScopes)) return false;
		}
		return true;
	}
	public function remainingScopes($requiredscopes) {
		$myScopes = $this->getScopeList();
		return array_diff($requiredscopes, $myScopes);
	}


}