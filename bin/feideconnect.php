#!/usr/bin/env php
<?php

namespace FeideConnect;
use FeideConnect\Data\StorageProvider;



require(dirname(dirname(__FILE__)) . '/lib/_autoload.php');

$command = new \Commando\Command();
$cli = new CLI();



$storage = StorageProvider::getStorage();

if ($command[0] === 'user') {

	$userid = $command[1];

	$user = $cli->getUser($userid);


	if (isset($command[2]) && $command[2] === "delete") {


		if ($user === null) {
			$cli->info("Cannot delete a user that we cannot find ");			
		} else {
			$cli->info("Deleting user " . $user->userid);
			$cli->deleteUser($user);			
		}

	} else if (isset($command[2]) && $command[2] === "addsec") {

		// $userid = $command[3];
		$useridsec = $command[3];


		$cli->info("User id is " . $user->userid . " and sec is [" . $useridsec . "]");
		$cli->addUserIDsec($user->userid, $useridsec);


	}

} else if ($command[0] === 't') {

	$cli->t();


} else if ($command[0] === 'users') {

	// echo "command " . $command[1];

	$count = 100;
	if (isset($command[1])) {
		$count = intval($command[1]);
	}

	$cli->getUsers($count);

} else if ($command[0] === 'useridsec') {

	// echo "command " . $command[1];

	$count = 100;
	if (isset($command[1])) {
		$count = intval($command[1]);
	}

	$cli->getUserIDsec($count);

} else if ($command[0] === 'apigk') {

	$apigk = $cli->getAPIGK($command[1]);


	if (isset($command[2]) && $command[2] === "delete") {


		if ($apigk === null) {
			$cli->info("Cannot delete a apigk that we cannot find ");			
		} else {
			$cli->info("Deleting apigk " . $apigk->id);
			$cli->deleteAPIGK($apigk);			
		}

	}

	

} else if ($command[0] === 'apigks') {

	$cli->getAPIGKs();


} else if ($command[0] === 'client') {

	$client = $cli->getClient($command[1]);


	if (isset($command[2]) && $command[2] === "delete") {


		$cli->info("Deleting client "); // . $client->id);
		$cli->deleteClient($client);

	}


	if (isset($command[2]) && $command[2] === "scopes") {


		$scopes = [];
		$c = $command->getArgumentValues();
		// for($i = 0; $i < count($c); $i++) {
		// 	// if ($i > 2) $scopes[] = $c[$i]->value();
		// }
		// print_r($c);
		// echo "Count " . count($command) . "\n";
		$scopeitems = array_slice($c, 3);
		$scopes = [];
		foreach($scopeitems AS $si) {
			$scopes[substr($si, 1)] = substr($si, 0, 1);
		}

		$s = [];
		$sr = [];

		foreach($scopes AS $scope => $mod) {
			if ($mod === '+') {
				$sr[] = $scope;
				$s[] = $scope;
			}
			if ($mod === '_') {
				$sr[] = $scope;
			}
		}
		// print_r($s);
		// print_r($sr);
		// exit;

		$cli->info("Dealing with scopes for " . $client->id);
		// $cli->infi("Setting scopes to " . $scopestr);
		$cli->setScopes($client, $sr, $s);

	}




} else if ($command[0] === 'clients') {

	$cli->getClients();

	// $clientlist = $c->getClients();

	// echo "\nListing clients\n";
	// foreach($clientlist AS $client) {
	// 	echo " " 
	// 		. sprintf("%30s", $client->name) . "  " 
	// 		. sprintf("%30s", $client->id)
	// 		. " \n";
	// }

	// echo "\n Found " . count($clientlist) . " clients \n\n";




} else if ($command[0] === 'orgs') {

	$cli->getOrgs();


} else if ($command[0] === 'org') {

	$orgid = $command[1];
	$org = $cli->getOrg($orgid);


	if (isset($command[2]) && $command[2] === 'setlogo' ) {

		$file = $command[3];

		echo "About to set a new logo for org [" . $orgid . "] from file [" . $file . "]\n";

		if (!file_exists($file)) {
			throw new \Exception('Cannot find logo file');
		}

		if ($org === null) {
			throw new \Exception('Client not found');
		}

		// $userinfo = $user->getUserInfo();
		// $sourceID = $user->selectedsource;

		$logo = file_get_contents($file);

		if (empty($logo)) {
			throw new Exception('Logo was not found');
		}
		echo "Logo was " . sha1($logo) . "\n";

		$cli->updateOrgLogo($org, $logo);


	}







 } else if ($command[0] === 'token') {

 	$cli->getToken($command[1]);



 } else if ($command[0] === 'consistency') {


 	$users = $cli->getUsers(10000);
 	
	$cql = '';

	foreach($users AS $u) {


		$userinfo = $u->getUserInfo();
		echo "[" . $userinfo['name'] . "] is having these secondary keys " . json_encode($u->userid_sec) . "\n";

		$keys = $u->userid_sec;
		foreach($keys AS $key) {
			$check = $storage->getUserByUserIDsec($key);
			if (isset($check) && $check->userid === $u->userid) {
				echo " OK    " . $key . " \n";
			} else {
				echo " ERROR " . $key . " \n";
				$cql .= " INSERT INTO userid_sec (userid, userid_sec) VALUES (" . $u->userid . ", '" . $key . "');\n";
			}
		}

	}


 	$useridsec = $cli->getUserIDsec(10000);


	foreach($useridsec AS $u) {

		// echo "Looking up user id " . $u["userid"];
		$user = $storage->getUserByUserID($u["userid"]);

		if ($user === null) {
			echo "[" . $u["userid_sec"] . "] is linked to [" . $u["userid"] . "] but user is missing \n";
			$cql .= " DELETE FROM userid_sec WHERE (userid_sec = '" . $u["userid_sec"] . "');\n";
		}


	}
	echo " ------ \n" . $cql;




} else if ($command[0] === 'setlogo' && $command[1] === 'client') {

	$clientid = $command[2];
	$file = $command[3];

	echo "About to set a new profile photo for client [" . $clientid . "] from file [" . $file . "]\n";

	$client = $c->getClient($clientid);

	if ($client === null) {
		throw new Exception('Client not found');
	}

	// $userinfo = $user->getUserInfo();
	// $sourceID = $user->selectedsource;

	$logo = file_get_contents($file);

	if (empty($logo)) {
		throw new Exception('Logo was not found');
	}
	echo "Logo was " . sha1($logo) . "\n";

	$c->updateClientLogo($client, $logo);


} else if ($command[0] === 'setprofile') {

	$userid = $command[1];
	$file = $command[2];

	echo "About to set a new profile photo for user [" . $userid . "] from file [" . $file . "]\n";

	$user = $c->getUserByUserID($userid);
	$userinfo = $user->getUserInfo();
	$sourceID = $user->selectedsource;


	$photo = file_get_contents($file);
	$hash = sha1($photo);

	$user->setUserInfo($sourceID, null, null, $photo, $hash );
	$c->updateProfilePhoto($user, $sourceID);




} 

