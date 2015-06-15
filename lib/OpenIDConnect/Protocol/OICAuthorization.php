<?php


namespace FeideConnect\OpenIDConnect\Protocol;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\AccessTokenPool;
use FeideConnect\Data\Models\AuthorizationCode;


use FeideConnect\Logger;

class OICAuthorization extends OAuthAuthorization {


	function __construct(Messages\Message $request) {

		parent::__construct($request);

		if (!($this->request instanceof Messages\AuthorizationRequest)) {
			throw new \Exception("Invalid request object type");
		}

	}

	function evaluateStepUp($aevaluator) {
		return null;
	}


	protected function getIDToken() {
		$openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
		$iat = $this->account->getAuthInstant();
		// echo '<pre>iat'; print_r($iat); exit;
		$idtoken = $openid->getIDtoken($this->user->userid, $this->client->id, $iat);
		return $idtoken;
	}

	protected function processToken() {


		$redirect_uri = $this->aevaluator->getValidatedRedirectURI();
		$expires_in = $this->getTokenDuration();
		$scopesInQuestion = $this->aevaluator->getScopesInQuestion();

		$pool = new AccessTokenPool($this->client, $this->user);
		$accesstoken = $pool->getToken($scopesInQuestion, false, $expires_in);

		// $accesstoken = Models\AccessToken::generate($client, $user, $scopesInQuestion, false, $expires_in);
		// 
		// TODO Verify that this saveToken was successfull before continuing.

		

		$idtoken = $this->getIDToken();
		// $idv = $idtoken->getEncoded();

		// echo $idv; exit;





		$tokenresponse = \FeideConnect\OpenIDConnect\Messages\TokenResponse::generateWithIDtoken($this->request, $accesstoken, $idtoken);

		Logger::info('OAuth Access Token and IDtoken is now issued.', array(
			'user' => $this->user->getAsArray(),
			'client' => $this->client->getAsArray(),
			'accesstoken' => $accesstoken->getAsArray(),
			'tokenresponse' => $tokenresponse->getAsArray(),
			'idtoken' => $idtoken->getObject(),
		));

		return $tokenresponse->sendRedirect($redirect_uri, true);

	}



	protected function processCode() {


		// echo "Processing OpenID Connect Code"; exit;

		$scopesInQuestion = $this->aevaluator->getScopesInQuestion();
		$redirectURI = null;
		if (!empty($this->request->redirect_uri)) {
			$redirectURI = $this->request->redirect_uri;
		}

		$idtoken = $this->getIDToken();
		$code = AuthorizationCode::generate($this->client, $this->user, $redirectURI, $scopesInQuestion, $idtoken);
		$this->storage->saveAuthorizationCode($code);

		$tokenresponse = Messages\AuthorizationResponse::generate($this->request, $code);

		Logger::info('OAuth Authorization Code is now stored, and may be fetched via the token endpoint.', array(
			'user' => $this->user->getAsArrayLimited(["userid", "userid_sec", "name"]),
			'client' => $this->client->getAsArrayLimited(["id", "name", "redirect_uri"]),
			'code' => $code->getAsArray(),
			'tokenresponse' => $tokenresponse->getAsArray(),
		));

		return $tokenresponse->sendRedirect($this->request->redirect_uri);

	}


	public function process() {

		// TODO: Implement strictly require the redirect_uri to be present.
		

		if ($this->request->isPassiveRequest()) {
			$this->isPassive = true;
		}

		// Ensure less duplicate code compared to the parent oauth processing...

		// GENERATE IDtoken and stuff together with code.


		// echo "Processing an OpenID Connect request<pre>";
		// print_r($this->request);

		// sleep(3);

		return parent::process();

	}



}