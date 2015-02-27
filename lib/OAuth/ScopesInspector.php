<?php


namespace FeideConnect\OAuth;

use FeideConnect\Config;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Logger;

/**
 *
 * ScopesInspector
 */
class ScopesInspector {
	
	protected $client;
	protected $scopes;

	protected $globalScopes;

	protected $storage;

	protected $apis = [], $owners = [];

	public function __construct($client, $scopes) {
		$this->client = $client;
		$this->scopes = $scopes;
		$this->globalScopes = Config::readJSONfile('scopedef.json');

		$this->storage = StorageProvider::getStorage();

		$this->apis = [];
	}

	public function getOwner($ownerid) {
		if (isset($this->owners[$ownerid])) {
			return $this->owners[$ownerid];
		}
		$this->owners[$ownerid] = $this->storage->getUserByUserID($ownerid);
		return $this->owners[$ownerid];
	}

	public function getAPI($apigkid) {

		if (isset($this->apis[$apigkid])) {
			if ($this->apis[$apigkid] === null) {
				throw new \Exception("APIGK not found " . $apigkid);
			}
			return $this->apis[$apigkid];
		}

		$this->apis[$apigkid] = $this->storage->getAPIGK($apigkid);

		if ($this->apis[$apigkid] === null) {
			throw new \Exception("APIGK not found " . $apigkid);
		}
		return $this->apis[$apigkid];
	}


	public function getInfo() {


		$apis = [];
		$data = [
			"global" => [],
			"apis" => [],
			"unknown" => [],
			"allScopes" => $this->scopes
		];

		foreach($this->scopes AS $scope) {



			// Basic and subscopes scopes for an APIGK
			if (preg_match('/^gk_([a-z0-9\-]+)(_([a-z0-9\-]+))?$/', $scope, $matches)) {

				$apigkid = $matches[1];
				// Utils::validateID($appid);
				

				try {

					$apigk = $this->getAPI($apigkid);

					if (!isset($apis[$apigkid])) {
						$apis[$apigkid] = ['localScopes' => [] ];
					}

					if (isset($matches[3])) {
						$apis[$apigkid]['localScopes'][] = $matches[3];
					} else {
						$apis[$apigkid]["basic"] = true;
					}

					if (!isset($apis[$apigkid]["info"])) {
						$apis[$apigkid]["apigk"] = $apigk;
					}
					if (!isset($apis[$apigkid]["ownerObj"] )) {
						$apis[$apigkid]["ownerObj"] = $this->getOwner($apigk->owner);
						if ($apis[$apigkid]["ownerObj"] !== null) {
							$apis[$apigkid]["ownerInfo"] = $apis[$apigkid]["ownerObj"]->getBasicUserInfo(true, ["userid", "p"]);
						}
						
					}

				} catch (\Exception $e) {

					Logger::error('Unable to retrieve scope information for an APIGK: ' . $e->getMessage(), [
						'apigkid' => $apigkid
					]);
					$data["unknown"][] = $scope;
				}


			} else {


				if (isset($this->globalScopes[$scope])) {

					$ne = $this->globalScopes[$scope];
					$ne["scope"] = $scope;
					$data['global'][$scope] = $ne;


				} else {

					$data["unknown"][] = $scope;

				}


			}


			

		}


		foreach($apis AS $apigkid => $api) {

			$apiEntry = [
				"info" => $api["apigk"]->getAsArray(),
				"owner" => $api["ownerInfo"],
				"scopes" => []
			];

			$apiEntry["scopes"][] = $api["apigk"]->getBasicScopeView();
			foreach($api["localScopes"] AS $ls) {
				$apiEntry["scopes"][] = $api["apigk"]->getSubScopeView($ls);
			}

			$data["apis"][] = $apiEntry;


		}

		$data["hasAPIs"] = (count($data["apis"]) > 0);

		return $data;

	}



} 