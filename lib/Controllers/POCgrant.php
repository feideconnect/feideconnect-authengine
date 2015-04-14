<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\Utils\URL;
use FeideConnect\Data\StorageProvider;
use FeideConnect\OAuth\ScopesInspector;
use FeideConnect\Logger;

class POCgrant {

	static function run($clientid, $userid) {




		$storage = StorageProvider::getStorage();
		$user = $storage->getUserByUserID($userid);
		$client = $storage->getClient($clientid);

		// echo '<pre>';
		// print_r($user);
		// // echo "clineet id ";
		// // print_r($client);
		// exit;
		
		$firsttime = true;



		$postattrs = $_REQUEST;
		$postattrs['client_id'] = $client->id;
		$postattrs['verifier'] = $user->getVerifier();
		if (!$firsttime) {
			$postattrs['bruksvilkar'] = 'yes';
		}

		$postdata = array();
		foreach($postattrs AS $k => $v) {
			$postdata[] = array('key' => $k, 'value' => $v);
		}

		$scopesInQuestion = $client->scopes;


		if (isset($_REQUEST['scopes'])) {
			$scopesInQuestion = explode(',',$_REQUEST['scopes']);
		}

		$redirect_uri = 'https://sp.example.org';


		$si = new ScopesInspector($client, $scopesInQuestion);


		$u = $user->getBasicUserInfo(true);
		$u['userid'] = $user->userid;
		$u['p'] = $user->getProfileAccess();

		$data = [
			'perms' => $si->getInfo(),
			'user' => $u,
			'posturl_' => URL::selfURLNoQuery(), // Did not work with php-fpm, needs to check out.
			'posturl' => URL::selfURLhost() . '/oauth/authorization',
			'postdata' => $postdata,
			'client' => $client->getAsArray(),
			'HOST' => URL::selfURLhost(),
		];



		$data['client']['host'] = URL::getURLhostPart($redirect_uri);
		$data['client']['isSecure'] = URL::isSecure($redirect_uri); // $oauthclient->isRedirectURISecured();


		$data['firsttime'] = $firsttime;
		$data['validated'] = false;

		$data['organization'] = "UNINETT AS";



		if ($client->has('owner')) {

			$owner = $storage->getUserByUserID($client->owner);
			if ($owner !== null) {
				$oinfo = $owner->getBasicUserInfo(true);
				$oinfo['p'] = $owner->getProfileAccess();
				$data['owner'] = $oinfo;
			}
			
		}



		Logger::info('OAuth About to present authorization dialog.', array(
			'authorizationDialogData' => $data
		));



		$response = new TemplatedHTMLResponse('oauthgrant');
		$response->setData($data);
		return $response;

	}


}