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
$c = new \FeideConnect\Data\Repositories\Cassandra($dbConfig);





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



if ($command[0] === 'generate') {


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
			$user->name = $full;
			$user->email = $email;
			$user->userid_sec = ['feide:' . $feideid];
			$user->debug();

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
