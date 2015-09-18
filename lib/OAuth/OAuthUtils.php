<?php

namespace FeideConnect\OAuth;

use FeideConnect\Logger;

class OAuthUtils {
	/**
	 * Return list ov valid scopes given a client and requested scopes
	 *
	 * @param \FeideConnect\Data\Models\Client $client the client of the request
	 * @params array $requestedScopes the requested scopes
	 * @return array list of valid scopes
	 */
	public static function evaluateScopes($client, $requestedScopes) {
		$scopes = $client->getScopeList();
		if (!empty($requestedScopes)) {
			// Only consider scopes that the client is authorized to ask for.
			$scopes = array_intersect($requestedScopes, $scopes);
		}

		return $scopes;
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
			$logdata['user'] = $user->getAsArray();
		}
		Logger::info('OAuth Access Token is now issued.', $logdata);

		return $tokenresponse;
	}
}
