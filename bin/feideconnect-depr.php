#!/usr/bin/env php
<?php

require(dirname(dirname(__FILE__)) . '/autoload.php');

$command = new Commando\Command();

$command->option()
    ->describedAs('Command to run: default is update.');

$command->option('u')
	->aka('userid')
	->describedAs('UserID.');

$command->option('s')
	->aka('addUserIDsec')
	->describedAs('Add secondary userkey.');

$command->option('r')
	->aka('removeUserIDsec')
	->boolean()
	->describedAs('Remove secondary userkey.');


$command->option('generate')
	->boolean()
	->describedAs('Generate data and insert into database.');



if ($command[0] === 'termcolor') {
	phpterm_demo();
	exit;
}





$dbConfig = json_decode(file_get_contents(__DIR__ . '/../etc/config.json'), true);
// $c = new \FeideConnect\Data\Repositories\Cassandra($dbConfig);

$c = FeideConnect\Data\StorageProvider::getStorage();



$cli = new FeideConnect\CLI();


// $id = 'a5b9491e-372d-49d9-943c-63d40dcb67f4';
// $client = $c->getClient($id);
// if (!empty($client)) {
// 	$client->debug();	
// } else {
// 	error_log("Could not find client " . $id);
// }


if (!empty($command['client'])) {

	$client = $c->getClient($command['client']);
	$client->debug();

} else if (!empty($command['addUserIDsec'])) {

	

	if ($command['removeUserIDsec']) {
		echo "Removing user " . $command['userid'] . " secondary key " . $command['addUserIDsec'] . "\n";
		$c->removeUserIDsec($command['userid'], $command['addUserIDsec']);
	} else {
		echo "Adding user " . $command['userid'] . " with new secondary key " . $command['addUserIDsec'] . "\n";
		$c->addUserIDsec($command['userid'], $command['addUserIDsec']);
	}
	exit;

} else if (!empty($command['userid'])) {

	echo "Get user by userid " . $command['userid'] . "\n";
	$user = $c->getUserByUserID($command['userid']);

	$user->debug();

} else if (!empty($command['addUserIDsec'])) {

	$user = $c->getUserByUserIDsec($command['addUserIDsec']);
	$user->debug();


}

if ($command[0] === 'authinfo') {

 	$user = $c->getUserByUserID($command[1]);
	$authorizations = $c->getAuthorizationsByUser($user);

	foreach($authorizations AS $a) {
		echo "------------\n";
		echo json_encode($a->getAsArray(), JSON_PRETTY_PRINT);
		echo "\n";
	}

	// print_r($authorizations);




} else if ($command[0] === 'user') {

	$userid = $command[1];

	$cli->header("Fetch information about user ". $userid);
	$cli->getUser($userid);





} else if ($command[0] === 'users') {


} else if ($command[0] === 'clients') {


	$clientlist = $c->getClients();

	echo "\nListing clients\n";
	foreach($clientlist AS $client) {
		echo " " 
			. sprintf("%30s", $client->name) . "  " 
			. sprintf("%30s", $client->id)
			. " \n";
	}

	echo "\n Found " . count($clientlist) . " clients \n\n";



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




} else if ($command[0] === 'generate') {


	$navn = json_decode(file_get_contents(__DIR__ . '/../etc/navn.json'), true);



	$userlist = $c->getUsers();
	$clientlist = $c->getClients();

	function pickUser() {
		global $userlist;
		if (count($userlist) <1) throw new Exception('Cannot generate before we got a list of users generated.');
		$k = array_rand($userlist);
		return $userlist[$k];
	}

	function pickClient() {
		global $clientlist;
		if (count($clientlist) <1) throw new Exception('Cannot generate before we got a list of client generated.');
		$k = array_rand($clientlist);
		return $clientlist[$k];
	}

	// print_r($userlist); print_r($clientlist);

	echo "Found " . count($userlist) . " users \n";
	echo "Found " . count($clientlist) . " clients \n";


	$i = 0; 
	$count = (!empty($command[2])) ? intval($command[2]) : 1;

	if ($command[1] === 'users') {

		$search  = array('æ', 'ø', 'å');
		$replace = array('e', 'o', 'a');

		while (++$i <= $count) {

			$fx = array_rand($navn['fornavn']);
			$ex = array_rand($navn['etternavn']);
			$rx = array_rand($navn['realm']);

			$full = $navn['fornavn'][$fx] . ' ' . $navn['etternavn'][$ex];
			$email = str_replace($search, $replace, strtolower($navn['fornavn'][$fx]) . '.' . strtolower($navn['etternavn'][$ex]) . '@' . $navn['realm'][$rx]);
			$feideid = str_replace($search, $replace, strtolower($navn['fornavn'][$fx]) . rand(1000, 9999) . '@' . $navn['realm'][$rx]);

			$user = new \FeideConnect\Data\Models\User($c);
			$user->userid = \FeideConnect\Data\Model::genUUID();
			// $user->name = $full;
			// $user->email = $email;
			// $user->userid_sec = ['feide:' . $feideid];

			$user->created = time();

			$user->userid_sec = ['feide:' . $feideid];
			$user->setUserInfo('feide:' . $navn['realm'][$rx], $full, $email);
			$user->selectedsource ='feide:' . $navn['realm'][$rx];

			$user->userid_sec_seen = array();
			foreach($user->userid_sec AS $u) {
				$user->userid_sec_seen[$u] = time();
			}

			$user->debug();
			// exit;

			$c->saveUser($user);

		}


	} else if ($command[1] === 'tokens') {

		while (++$i <= $count) {

			$token = new \FeideConnect\Data\Models\AccessToken($c);
			$token->access_token = \FeideConnect\Data\Model::genUUID();
			$token->clientid = pickClient()->id;
			$token->userid = pickUser()->userid;
			$token->scope = array('userinfo', 'longterm');
			$token->token_type = 'bearer';
			$token->validuntil = time() + 3600;
			$token->issued = time();

			$c->saveToken($token);
			
		}


	} else if ($command[1] === 'clients') {

		foreach($navn['tjenester'] AS $t) {

			$client = new \FeideConnect\Data\Models\Client($c);

			$client->id = \FeideConnect\Data\Model::genUUID();
			$client->client_secret = \FeideConnect\Data\Model::genUUID();
			
			$client->name = $t;
			$client->descr = $t . ' - en Feide Connect tjeneste';

			$client->owner = pickUser()->userid;
			$client->redirect_uri = ['https://sp.example.org/callback'];
			$client->scopes = ["userinfo"];
			$client->scopes_requested = ["rest_baluba", "rest_baluba_write"];

			$client->status = ['production'];
			$client->type = 'client';

			$client->created = time();
			// $client->updated = time();

			// $client->debug();

			$c->saveClient($client);

		}


	}



}
