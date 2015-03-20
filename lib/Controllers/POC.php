<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\Authentication;
use FeideConnect\Data\StorageProvider;

class POC {

	static function process($paramUser, $paramClient) {

		$user = null;
		$client = null;


		$storage = StorageProvider::getStorage();

		if ($paramUser === '@me') {

			$auth = new Authentication\Authenticator();
			$auth->req(false, true); // require($isPassive = false, $allowRedirect = false, $return = null
			$account = $auth->getAccount();
			
			$usermapper = new Authentication\UserMapper($storage);
			$user = $usermapper->getUser($account, false, true, false);

			if ($user === null) {
				throw new \Exception('User not found');
			}
			
		} else if ($paramUser === '@random') {


			$userlist = $storage->getUsers();
			function pickUser($userlist) {
				if (count($userlist) <1) throw new Exception('Cannot generate before we got a list of users generated.');
				$k = array_rand($userlist);
				return $userlist[$k];
			}
			$user = pickUser($userlist);

		} else if ($paramUser === '@none') {


		} else {

			$user = $storage->getUserByUserID($paramUser);
			if ($user === null) {
				throw new \Exception('User not found');
			}
		}





		if ($paramClient === '@random') {

			$clientlist = $storage->getClients();
			function pickClient($clientlist) {
				if (count($clientlist) <1) throw new Exception('Cannot generate before we got a list of client generated.');
				$k = array_rand($clientlist);
				return $clientlist[$k];
			}
			$client = pickClient($clientlist);


		} else {

			$client = $storage->getClient($paramClient);


		}


		if ($client === null) {
			throw new \Exception('Client not found');
		}





		$token = new \FeideConnect\Data\Models\AccessToken($storage);
		$token->access_token = \FeideConnect\Data\Model::genUUID();
		$token->clientid = $client->id;
		if ($user !== null) {
			$token->userid = $user->userid;	
		}
		
		$token->scope = $client->scopes;
		$token->token_type = 'bearer';
		
		$token->issued = new \FeideConnect\Data\Types\Timestamp();
		$token->validuntil = (new \FeideConnect\Data\Types\Timestamp())->addSeconds(3600);

		$storage->saveToken($token);




		$data = [];
		$rawdata = [];
		if (!empty($user)) {
			$data['user'] = json_encode($user, JSON_PRETTY_PRINT);
			$rawdata["user"] = $user;
 		}
		if (!empty($client)) {
			$data['client'] = json_encode($client, JSON_PRETTY_PRINT);
			$rawdata["client"] = $client;
		}
		if (!empty($token)) {
			$data['token'] = json_encode($token, JSON_PRETTY_PRINT);
			$rawdata["token"] = $token;
		}


		

		if (isset($_REQUEST['output']) && $_REQUEST['output'] === 'json') {

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($rawdata, JSON_PRETTY_PRINT);
			exit;

		}


		$response = new TemplatedHTMLResponse('poc');
		$response->setData($data);
		return $response;



	}



}