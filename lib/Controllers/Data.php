<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\ImageResponse;
use FeideConnect\HTTP\JSONResponse;
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
				// $response->setImage(substr($userinfo['profilephoto'], 4), 'jpeg');
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

	static function scmp($a, $b) {

		$ax = ($a["distance"] === null ? 9999 : $a["distance"]);
		$bx = ($b["distance"] === null ? 9999 : $b["distance"]);


		return ($ax < $bx) ? -1 : 1;
	}

	static function getOrgs() {

		$lat = $_REQUEST['lat']; 
		$lon = $_REQUEST['lon'];

		$storage = StorageProvider::getStorage();
		$orgs = $storage->getOrgs();
		$data = [];
		$subscribers = [
            "fjellhaug.no",
            "hioa.no",
            "uninett.no",
            "uib.no",
            "ntnu.no",
            "feide.egms.no",
            "hin.no",
            "hihm.no",
            "hiof.no",
            "skole.fredrikstad.no"
        ];
		foreach($orgs AS $org) {
			if (!$org->isHomeOrg()) { continue; }
			if (!in_array($org->realm, $subscribers)) { continue; }
			$di = $org->getOrgInfo($lat, $lon);
			$data[] = $di;
		}


		usort($data, ["\FeideConnect\Controllers\Data", "scmp"]);


		// echo '<pre>';
		// foreach($data AS $d) {
		// 	echo join(',', $d["type"]) . "\n";
		// }

		// echo '<pre>Data:'; print_r($data); exit;
		return new JSONResponse($data);
	}


	static function accountchooserExtra() {


		$data = Config::readJSONfile("disco2.json");
		return new JSONResponse($data);

	}




}