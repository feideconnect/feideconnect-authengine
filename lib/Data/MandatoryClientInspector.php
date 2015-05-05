<?php

namespace FeideConnect\Data;

class MandatoryClientInspector {


	function __construct() {

	}



	public static function isClientMandatory($account, $client) {

		$realm = $account->getRealm();



		$clientGlobalMandatory = $client->hasStatus("Mandatory");

		if ($clientGlobalMandatory) {
			return true;
		}


		// If there is no realm associated with the user, then 
		if ($realm === null) { 
			return false; 
		}

		$storage = StorageProvider::getStorage();

		$res = $storage->checkMandatory($realm, $client);

		if ($res !== null) {
			return true;

		} else {
			// Did not find any mandatory clients configuration for this realm and client.
			return false;
		}


	}


}