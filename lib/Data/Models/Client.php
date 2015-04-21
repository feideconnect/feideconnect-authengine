<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionList;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;
use Cassandra\Type\Blob;

class Client extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"id", "client_secret", "created", "descr", "name", "owner", "organization",
		"logo",
		"redirect_uri", "scopes", "scopes_requested", "status", "type", "updated"
	);
	protected static $_types = [
		"created" => "timestamp",
		"updated" => "timestamp"
	];


	public function getScopeList() {
		if (empty($this->scopes)) return [];
		return $this->scopes;
	}

	public function getStorableArray() {

		$prepared = parent::getStorableArray();


		if (isset($this->id)) {
			$prepared["id"] = new Uuid($this->id);
		}
		if (isset($this->logo)) {
			$prepared["logo"] =  new Blob($this->logo);
		}

		if (isset($this->redirect_uri)) {
			$prepared["redirect_uri"] =  new CollectionList($this->redirect_uri, Base::ASCII);
		}
		if (isset($this->scopes)) {
			$prepared["scopes"] =  new CollectionSet($this->scopes, Base::ASCII);
		}
		if (isset($this->scopes_requested)) {
			$prepared["scopes_requested"] =  new CollectionSet($this->scopes_requested, Base::ASCII);
		}
		if (isset($this->status)) {
			$prepared["status"] =  new CollectionSet($this->status, Base::ASCII);
		}
		
		if (isset($this->owner)) {
			$prepared["owner"] =  new Uuid($this->owner);
		}


		// echo var_export($prepared, true); 

		return $prepared;
	}



}