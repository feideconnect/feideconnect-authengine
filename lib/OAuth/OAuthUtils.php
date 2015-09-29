<?php

namespace FeideConnect\OAuth;

use FeideConnect\Logger;

class OAuthUtils {
	/**
	 * Return list ov valid scopes given a client and requested scopes
	 *
	 * @param \FeideConnect\Data\Models\Client $client the client of the request
	 * @param \FeideConnect\Data\Models\User $user the user of the request (may be null)
	 * @params array $requestedScopes the requested scopes
	 * @return array list of valid scopes
	 */
	public static function evaluateScopes($client, $user, $requestedScopes) {
		$scopelist = $clientscopes = $client->getScopeList();
		if (!empty($requestedScopes)) {
			// Only consider scopes that the client is authorized to ask for.
			$scopelist = array_intersect($requestedScopes, $scopelist);
		}
		$scopes = array();
		foreach($scopelist AS $scope) {
			$scopes[$scope] = true;
		}

		$scopesinspector = new ScopesInspector($scopelist);

		$feideuser = false;
		if ($user !== null) {
			$realms = $user->getFeideRealms();
			if (count($realms) > 0) {
				$feideuser = true;
			}
		}

		$orgmoderatedscopes = array();

		foreach($scopesinspector->getScopeAPIGKs($scopelist) AS $scope => $apigkinfo) {
			$apigk = $apigkinfo['apigk'];
			if ($apigk->isOrgModerated($scope)) {
				$orgmoderatedscopes[] = $scope;
			}
		}
		if (!$feideuser) {
			foreach($orgmoderatedscopes AS $scope) {
				unset($scopes[$scope]);
			}
		} else {
			foreach($orgmoderatedscopes AS $scope) {
				foreach($realms AS $realm) {
					if (array_search($scope, $client->getOrgAuthorization($realm)) === FALSE) {
						unset($scopes[$scope]);
					}
				}
			}
		}

		$result = array_keys($scopes);
		$logdata = array(
			'requested_scopes' => $requestedScopes,
			'client_scopes' => $clientscopes,
			'org_moderated_scopes' => $orgmoderatedscopes,
			'feideuser' => $feideuser,
			'result_scopes' => $result,
		);
		if ($feideuser) {
			$logdata['user_realms'] = $realms;
		}
		Logger::info('Evaluated scopes', $logdata);
		return $result;
	}

	public static function generateTokenResponse($client, $user, $scopes, $flow, $state = null) {

		$expires_in = 3600*8; // 8 hours
		if (in_array('longterm', $scopes, true)) {
			$expires_in = 3600*24*680; // 680 days
		}

		$pool = new AccessTokenPool($client, $user);
		$accesstoken = $pool->getToken($scopes, false, $expires_in);
		// TODO Verify that this saveToken was successfull before continuing.

		$tokenresponse = Messages\TokenResponse::generate($accesstoken, $state);

		$logdata = array(
			'flow' => $flow,
			'client' => $client->getAsArray(),
			'accesstoken' => $accesstoken->getAsArray(),
			'tokenresponse' => $tokenresponse->getAsArray(),
		);
		if ($user !== null) {
			$logdata['user'] = $user->getBasicUserInfo();
		}
		Logger::info('OAuth Access Token is now issued.', $logdata);

		return $tokenresponse;
	}
}
