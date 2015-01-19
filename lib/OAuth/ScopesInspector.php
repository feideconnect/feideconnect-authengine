<?php


namespace FeideConnect\OAuth;

/**
*
* ScopesInspector
*/
class ScopesInspector {
	
	protected $client;
	protected $scopes;

	protected $globalScopes;

	public function __construct($client, $scopes) {
		$this->client = $client;
		$this->scopes = $scopes;

		$this->globalScopes = array(
			'userinfo' => array(
				'type' => 'userinfo'
			),
			'longterm' => array(
				'type' => ''
			)
		);
	}


	public function getInfo() {


		$apps = [];
		$data = [
			"globale" => [],
			"apps" => []
		];

		foreach($this->scopes AS $scope) {


			if (preg_match('/gk_([a-z0-9\-]+)(_([a-z0-9\-]+))?/', $scope, $matches)) {

				$appid = $matches[1];
				// Utils::validateID($appid);

				if (!isset($data['apps'][$appid])) {
					$apps[$appid] = array('localScopes' => array());
				}

				if (isset($matches[2])) {
					$apps[$appid]['localScopes'][] = $matches[2];
				}

				// $proxy = APIProxy::getByID($appid);
				// $localScope = (isset($matches[2]) ? $matches[2] : null);


			} else {

				$data['global'][$scope] = 1; 

			}

		}


		foreach($apps AS $appid => $v) {


			throw new \Exception('Not yet implemented support for getting information about scopes for gatekeeper apis');

			// Utils::validateID($appid);
			// $proxy = APIProxy::getByID($appid);
			// $proxy = [];
			// $ainfo = array(
			// 	'id' => $appid,
			// 	// 'title' => $proxy->get('name'),
			// 	// 'descr' => $proxy->get('descr'),
			// 	// 'perms'	=> $proxy->getScopeInfo()
			// );

			// if ($proxy->has('owner-descr')) {
			// 	$ainfo['owner-descr'] = $proxy->get('owner-descr');
			// } else {
			// 	$owner = User::getByID($proxy->get('uwap-userid'));
			// 	$ainfo['owner'] = $owner->getJSON(array('type' => 'basic'));
			// }

			// $data['apps'][] = $ainfo;

		}

		return $data;

	}



}