<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionList;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;
use Cassandra\Type\Blob;


/*
CREATE TABLE feideconnect.organizations (
    id text PRIMARY KEY,
    kindid int,
    logo blob,
    logo_updated timestamp,
    name map<text, text>,
    organization_number text,
    realm text,
    type set<text>
)
 */

class Organization extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"id", "name", "realm", "type"
	);
	protected static $_types = [
	];




	public function getStorableArray() {

		$prepared = parent::getStorableArray();

		// if (isset($this->redirect_uri)) {
		// 	$prepared["redirect_uri"] =  new CollectionList($this->redirect_uri, Base::ASCII);
		// }
		// if (isset($this->scopes)) {
		// 	$prepared["scopes"] =  new CollectionSet($this->scopes, Base::ASCII);
		// }
		// if (isset($this->scopes_requested)) {
		// 	$prepared["scopes_requested"] =  new CollectionSet($this->scopes_requested, Base::ASCII);
		// }
		// if (isset($this->status)) {
		// 	$prepared["status"] =  new CollectionSet($this->status, Base::ASCII);
		// }
		

		return $prepared;
	}



}