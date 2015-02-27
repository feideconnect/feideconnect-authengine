<?php


namespace FeideConnect\OAuth;



use FeideConnect\Logger;
use FeideConnect\OAuth\Exceptions;
use FeideConnect\Data\Models\Authorization;

/**
* 
*/
class AuthorizationEvaluator {
	
	protected $storage, $client, $request, $user;
	protected $authorization = null;
	protected $scopesInQuestion = null;
	protected $scopesRemaining = null;

	function __construct($storage, $client, $request, $user) {
		$this->storage = $storage;
		$this->client = $client;
		$this->request = $request;
		$this->user = $user;

		$this->authorization = null;
		if ($user !== null) {
			$this->authorization = $this->storage->getAuthorization($this->user->userid, $this->client->id);	
		}

		$this->evaluateScopes();
	}

	public function getUpdatedAuthorization() {
		$a = $this->authorization;
		if ($a === null) {
			$a = new Authorization([
				"clientid" => $this->client->id,
				"userid" => $this->user->userid,
				"issued" => time(),
				"scopes" => [],
			]);
		}
		$q = $this->getScopesInQuestion();
		$a->addScopes($q);
		return $a;
	}

	private function evaluateScopes() {

		$this->scopesInQuestion = $this->client->getScopeList();
		if (!empty($this->request->scope)) {
			// Only consider scopes that the client is authorized to ask for.
			$this->scopesInQuestion = array_intersect($this->request->getScopeList(), $this->scopesInQuestion);
		}

		if ($this->authorization === null) {
			$this->scopesRemaining = $this->scopesInQuestion;
		} else {
			$this->scopesRemaining = $this->authorization->remainingScopes($this->scopesInQuestion);
		}

		Logger::info('OAuth AuthorizationEvaluator evaluateScopes()', array(
			'scopeEvaluation' => [
				'clientScopes' => $this->client->getScopeList(),
				'requestedScopes' => $this->request->getScopeList(),
				'requestedScopesStr' => $this->request->scope,
				'authorization' => ($this->authorization === null ? null : $this->authorization->getAsArray()),
				'scopesInQuestion' => $this->scopesInQuestion,
				'scopesRemaining' => $this->scopesRemaining,
				'needsAuthorization' => $this->needsAuthorization()
			]
		));


	}

	public function getScopesInQuestion() {
		return $this->scopesInQuestion;
	}

	public function getRemainingScopes() {
		return $this->scopesRemaining;
	}


	/**
	 * Does the user needs to perform a new authorization?
	 * @return [type] [description]
	 */
	public function needsAuthorization() {

		if ($this->authorization === null) return true;
		if (!empty($this->scopesRemaining)) return true;

		return false;

	}

	public function getValidatedRedirectURI() {



		$configuredRedirectURI = $this->client->redirect_uri;
		$requestedRedirectURI = $this->request->redirect_uri;


		Logger::info('OAuth AuthorizationEvaluator getValidatedRedirectURI()', array(
			'configuredRedirectURI' => $configuredRedirectURI,
			'requestedRedirectURI' => $requestedRedirectURI,
		));

		if (empty($this->request->redirect_uri)) {
			return $configuredRedirectURI;
		}


		if (in_array($requestedRedirectURI, $configuredRedirectURI)) {
			return $requestedRedirectURI;
		}

		/**
		 * Old code, doing prefix matching instead on the clients requested redirect uris.
		 */
		
		// if (is_array($configuredRedirectURI)) {
		// 	if (empty($request->redirect_uri)) {
		// 		// url not specified in request, returning first entry from config
		// 		return $configuredRedirectURI[0];
				
		// 	} else {
		// 		// url specified in request, returning if is substring match any of the entries in config
		// 		foreach($configuredRedirectURI AS $su) {
		// 			if (strpos($request->redirect_uri, $su) === 0) {
		// 				return $request->redirect_uri;
		// 			}
		// 		}
		// 	}
		// } else if (!empty($configuredRedirectURI)) {
		// 	if (empty($request->redirect_uri)) {
		// 		// url not specified in request, returning the only entry from config
		// 		return $configuredRedirectURI;
				
		// 	} else {
		// 		// url specified in request, returning if is substring match the entry in config
		// 		if (strpos($request->redirect_uri, $configuredRedirectURI) === 0) {
		// 			return $request->redirect_uri;
		// 		}	
		// 	}
		// }
		
		Logger::error('OAuth AuthorizationEvaluator not able to resolve a valid redirect_uri for client', array(
			'configuredRedirectURI' => $configuredRedirectURI,
			'requestedRedirectURI' => $requestedRedirectURI,
		));
		throw new Exceptions\OAuthException('invalid_request', 'Not able to resolve a valid redirect_uri for client');


	}


}