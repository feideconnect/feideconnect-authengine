<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\ImageResponse;
use FeideConnect\Config;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Authentication\UserID;
use FeideConnect\Exceptions\Exception;

class Data {


	static function getUserProfilephoto($useridstr) {

		$storage = StorageProvider::getStorage();
		$userid = new UserID($useridstr);

		if ($userid->prefix !== 'p') {
			throw new Exception('You may only lookup users by p: keys');
		}

		$user = $storage->getUserByUserIDsec($useridstr);

		$response = new ImageResponse();

		if (!empty($user)) {
			$userinfo = $user->getUserInfo();
			if (!empty($userinfo['profilephoto'])) {
				$response->setImage($userinfo['profilephoto'], 'jpeg');
			} else {
				$response->setImageFile('www/static/media/default-profile.jpg', 'jpeg');
			}
		} else {
			$response->setImageFile('www/static/media/default-profile.jpg', 'jpeg');
		}


		return $response;
	}

	static function getClientLogo($clientid) {

		$storage = StorageProvider::getStorage();
		$client = $storage->getClient($clientid);
		$response = new ImageResponse();

		if (!empty($client->logo)) {
			$response->setImage($client->logo, 'jpeg');
		} else {
			$response->setImageFile('www/static/media/default-client.png', 'png');
		}
		return $response;
	}



}