#!/usr/bin/env php
<?php

namespace FeideConnect;



require(dirname(dirname(__FILE__)) . '/autoload.php');

$command = new \Commando\Command();
$cli = new CLI();


if ($command[0] === 'user') {

	$userid = $command[1];
	$user = $cli->getUser($userid);


	if (isset($command[2]) && $command[2] === "delete") {

		$cli->info("Deleting user " . $user->userid);
		$cli->deleteUser($user);

	}

} else if ($command[0] === 'users') {

	$cli->getUsers();

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



 } else if ($command[0] === 'consistency') {


 	$users = $c->getUsers();
 	
	$cql = '';

	foreach($users AS $u) {


		$userinfo = $u->getUserInfo();
		echo "[" . $userinfo['name'] . "] is having these secondary keys " . json_encode($u->userid_sec) . "\n";

		$keys = $u->userid_sec;
		foreach($keys AS $key) {
			$check = $c->getUserByUserIDsec($key);
			if (isset($check) && $check->userid === $u->userid) {
				echo " OK    " . $key . " \n";
			} else {
				echo " ERROR " . $key . " \n";
				$cql .= " INSERT INTO userid_sec (userid, userid_sec) VALUES (" . $u->userid . ", '" . $key . "');\n";
			}
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

