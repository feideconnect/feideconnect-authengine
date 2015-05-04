<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\Utils\URL;
use FeideConnect\Data\StorageProvider;
use FeideConnect\OAuth\ScopesInspector;
use FeideConnect\Logger;
use FeideConnect\Config;

class POCgrant {

	static function run($clientid, $userid) {

		$storage = StorageProvider::getStorage();
		$user = $storage->getUserByUserID($userid);
		$client = $storage->getClient($clientid);

		$firsttime = false;
		$simpleView = true;

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

		$scopesInspector = new ScopesInspector($client, $scopesInQuestion);

		$userinfo = $user->getBasicUserInfo(true);
		$userinfo['userid'] = $user->userid;
		$userinfo['p'] = $user->getProfileAccess();

		$data = [
			'perms' => $scopesInspector->getInfo(),
			'user' => $userinfo,
			// 'posturl_' => URL::selfURLNoQuery(), // Did not work with php-fpm, needs to check out.
			'posturl' => URL::selfURLhost() . '/oauth/authorization',
			'postdata' => $postdata,
			'client' => $client->getAsArray(),
			'HOST' => URL::selfURLhost(),
		];


		// echo '<pre>'; print_r($data); exit;


		$data['client']['host'] = URL::getURLhostPart($redirect_uri);
		$data['client']['isSecure'] = URL::isSecure($redirect_uri); // $oauthclient->isRedirectURISecured();


		$data['bodyclass'] = '';
		if ($simpleView) {
			$data['bodyclass'] = 'simpleGrant';
		}
		$data['firsttime'] = $firsttime;
		$data['validated'] = false;

		$data['organization'] = "UNINETT AS";


		if ($client->has('organization')) {

			$org = $storage->getOrg($client->organization);
			if ($org !== null) {
				$orginfo = $org->getAsArray();
				$orginfo["logoURL"] = Config::dir("orgs/" . $org->id . "/logo", "", "core");
				$data['ownerOrg'] = true;
				$data['org'] = $orginfo;
			}

		} else  if ($client->has('owner')) {

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