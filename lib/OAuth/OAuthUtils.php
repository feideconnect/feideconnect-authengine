<?php

namespace FeideConnect\OAuth;

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
}
