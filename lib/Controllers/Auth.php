<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Authentication;
use FeideConnect\OAuth\APIProtector;
use FeideConnect\Data\StorageProvider;


class Auth {

	static function logout() {

		$auth = new Authentication\Authenticator();
		$auth->logout();

	}

	static function userdebug() {


		$storage = StorageProvider::getStorage();

		$auth = new Authentication\Authenticator();
		$auth->req(false, true); // require($isPassive = false, $allowRedirect = false, $return = null

		$account = $auth->getAccount();

		// $res = $auth->storeUser();
		// 
		// $response = array('account' => $account->getAccountID());
		$response = [
			"account" => [
				"userids" => $account->getUserIDs(),
				"sourceID" => $account->getSourceID(),
				"name" => $account->getName(),
				"mail" => $account->getMail()
			]
		];
		// echo '<pre>';
		// print_r($response); exit;

		
		$usermapper = new Authentication\UserMapper($storage);

		$user = $usermapper->getUser($account, false, true, false);
		// header('Content-type: text/plain');
		// print_r($user); exit;
		if (isset($user)) {
			$response['user'] = $user->getAsArray();	
			$response['userinfo'] = $user->getUserInfo();
		}

		if (isset($response["user"]["profilephoto"]) && is_array($response["user"]["profilephoto"])) {
			$response["user"]["profilephoto"] = array_map("base64_encode", $response["user"]["profilephoto"]);
		}

		if (isset($response["userinfo"]["profilephoto"]) ) {
			$response["userinfo"]["profilephoto"] = base64_encode($response["userinfo"]["profilephoto"]);
		}

		// echo '<pre>'; print_r($response); exit;

		$res = new JSONResponse($response);
		$res->setCORS(false);
		return $res;

	}

	static function userinfo() {



		$apiprotector = new APIProtector();
		$user = $apiprotector
			->requireClient()->requireUser()->requireScopes(['userinfo'])
			->getUser();
		$client = $apiprotector->getClient();


		$hasScopes = $apiprotector->getScopes();


		$allowseckeys = ['userid'];
		$includeEmail = false;

		foreach($hasScopes AS $scope) {
			$data[$scope] = true;
			if ($scope === 'userinfo-feide') {
				$allowseckeys[] = 'feide';
			}
			if ($scope === 'userinfo-photo') {
				$allowseckeys[] = 'p';
			}
			if ($scope === 'userinfo-mail') {
				$includeEmail = true;
			}
		}

		$userinfo = $user->getBasicUserInfo($includeEmail, $allowseckeys);


		$data = [
			'user' => $userinfo,
			'audience' => $client->id,
		];


		return new JSONResponse($data);

	}

	static function authinfo() {




		$storage = StorageProvider::getStorage();

		$apiprotector = new OAuth\APIProtector();
		$user = $apiprotector
			->requireClient()->requireUser()->requireScopes(['userinfo'])
			->getUser();
		// $client = $apiprotector->getClient();

		$allowseckeys = ['userid'];
		$allowseckeys[] = 'p';
		$allowseckeys[] = 'feide';

		// $includeEmail = true;

		$authorizations = $storage->getAuthorizationsByUser($user);

		$data = [
			"authorizations" => [],
			"tokens" => [],
		];
		foreach($authorizations AS $auth) {
			$data["authorizations"][] = $auth->getAsArray();
		}

		return new JSONResponse($data);

	}


}