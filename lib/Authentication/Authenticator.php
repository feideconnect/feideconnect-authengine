<?php

namespace FeideConnect\Authentication;

use FeideConnect\Authentication;
use FeideConnect\Utils\URL;
use FeideConnect\Config;
use FeideConnect\Logger;


/**
 * This class handles all authentication, and uses SimpleSAMLphp for that task.
 * It will also handle all local user creation. All new users will be stored in the user repository.
 * 
 */
class Authenticator {

	protected $as, $user, $authSource, $authConfig;


	public function __construct($authconfig = null) {




		$this->authTypes = Config::getValue("authTypes");
		$this->authSources = [];
		$this->clientid = null;

		$this->activeAuthType = null;

		foreach($this->authTypes AS $authtype => $authTypeConfig) {
			$this->authSources[$authtype] = new \SimpleSAML_Auth_Simple($authTypeConfig["authSource"]);
			// echo "Creating auth source [" . $authtype . "] using [" . $authTypeConfig["authSource"] . "] ";
		}
		$this->user = null;

	}

	public function setClientID($clientid) {
		$this->clientid = $clientid;
	}

	protected function getIdP() {

		if (isset($this->authConfig["idp"])) {
			return $this->authConfig["idp"];
		}
		return Config::getValue("defaultIdP");

	}


	/**
	 * Assumes the user is not logged in, and performs a isPassive=true login request against the IdP
	 * @return [type] [description]
	 */
	protected function authenticatePassive($as) {

		$as->login([
			'isPassive' => true,
			'saml:idp' => $this->getIdP(),
			'ErrorURL' => \SimpleSAML_Utilities::addURLparameter(\SimpleSAML_Utilities::selfURL(), array(
				"error" => 1,
			)),
		]);

	}


	/**
	 * Require authentication of the user. This is meant to be used with user frontend access.
	 * 
	 * @param  boolean $isPassive     [description]
	 * @param  boolean $allowRedirect Set to false if using on an API where user cannot be redirected.
	 * @param  [type]  $return        URL to return to after login.
	 * @return void
	 */
	public function requireAuthentication($isPassive = false, $allowRedirect = false, $return = null, $maxage = null) {

		$accountchooser = new Authentication\AccountChooserProtocol();



		$accountchooser->setClientID($this->clientid);
		// $accountchooser->debug();
		if (!$accountchooser->hasResponse()) {
			$requestURL = $accountchooser->getRequest();
			URL::redirect($requestURL);
		}
		$authconfig = $accountchooser->getAuthConfig();

		if (!isset($this->authSources[$authconfig["type"]])) {
			throw new \Exception("Attempting to authenticate using an authentication source that is not initialized.");
		}

		$this->activeAuthType = $authconfig["type"];
		$as = $this->authSources[$this->activeAuthType];

		// echo '<pre>About to authenticate using ' . $this->activeAuthType . "\n\n"; print_r($authconfig); exit;


		$forceauthn = false;

		if ($as->isAuthenticated()) {

			// First check if we are authenticated using the expected IdP, as the user has seleted some.
			if (isset($authconfig['idp'])) {

				$account = $this->getAccount();
				// var_dump($account); var_dump($authconfig); exit;
				if ($account->idp !== $authconfig['idp']) {
					$this->logoutAS($as);
				} else if($authconfig['subid'] && $authconfig['subid'] !== $account->realm) {
					// TODO: This can cause problems for users that does not want to login to the service...
					// $this->logoutAS($as);
				}

			}

		}


		if ($as->isAuthenticated() && ($maxage === null)) {
			return;

		} else if ($as->isAuthenticated()) {


			$now = time();
			$allowSkew = 20; // 20 seconds clock skew accepted.
			$authninstant = $as->getAuthData("AuthnInstant");
			$authAge = $now - $authninstant;

			if ($authAge < ($maxage + $allowSkew)) {

				// Already authenticated with a authnetication session which is sufficiently fresh.
				return;
			}

			$forceauthn = true;

			Logger::info('OAuth Processing authentication. User is authenticated but with a too old authninstant.', array(
				'now' => $now,
				'authninstant' => authninstant,
				'maxage' => $maxage,
				'allowskew' => $allowskew,
				'authage' => $authAge
			));
		}






		// User is not authenticated locally.
		// If allowed, attempt is passive authentiation.
		if ($isPassive && $allowRedirect) {

			// TODO: add info about selected IdP here as well..
			$this->authenticatePassive($as);
			return;
		}

		if ($allowRedirect) {
			if ($return === null) $return = \SimpleSAML_Utilities::selfURL();

			$options = array();
			

			if (isset($authconfig["idp"])) {
				$options["saml:idp"] = $authconfig["idp"];
			}

			// echo '<pre>Options:' ; print_r($options); exit;


			if ($forceauthn) {
				$options['ForceAuthn'] = true;
			}

			// echo "about to auth " . var_export($options, true); exit;

			if ($forceauthn) {
				$as->login($options);
			} else {
				$as->requireAuth($options);
			}

			return;

		}

		throw new Exception('User is not authenticated. Authentication is required for this operation.');



	}


	/**
	 * Require authentication of the user. This is meant to be used with user frontend access.
	 * @deprecated [<version>] [<description>]
	 * @param  boolean $isPassive     [description]
	 * @param  boolean $allowRedirect Set to false if using on an API where user cannot be redirected.
	 * @param  [type]  $return        URL to return to after login.
	 * @return void
	 */
	public function req($isPassive = false, $allowRedirect = false, $return = null, $maxage = null) {

		throw new \Exception("using the deprecated Authenticator->req()");

		$forceauthn = false;
		if ($this->as->isAuthenticated() && ($maxage === null)) {
			return;

		} else if ($this->as->isAuthenticated()) {


			$now = time();
			$allowSkew = 20; // 20 seconds clock skew accepted.
			$authninstant = $this->as->getAuthData("AuthnInstant");
			$authAge = $now - $authninstant;

			if ($authAge < ($maxage + $allowSkew)) {

				// Already authenticated with a authnetication session which is sufficiently fresh.
				return;
			}

			$forceauthn = true;

			Logger::info('OAuth Processing authentication. User is authenticated but with a too old authninstant.', array(
				'now' => $now,
				'authninstant' => authninstant,
				'maxage' => $maxage,
				'allowskew' => $allowskew,
				'authage' => $authAge
			));


			// $attributes = $this->as->getAttributes();
			// if (isset($attributes) && isset($attributes["forcedAuthN"])) {
			// 	return;
			// }
		}

		// $session = \SimpleSAML_Session::getSessionFromRequest();
		// echo '<pre>'; print_r($session); exit;

		// User is not authenticated locally.
		// If allowed, attempt is passive authentiation.
		if ($isPassive && $allowRedirect) {

			$this->authenticatePassive();
			return;
		}

		if ($allowRedirect) {
			if ($return === null) $return = \SimpleSAML_Utilities::selfURL();

			$options = array();
			$options["saml:idp"] = $this->getIdP();

			if ($forceauthn) {
				$options['ForceAuthn'] = true;
			}

			// echo "about to auth " . var_export($options, true); exit;

			if ($forceauthn) {
				$this->as->login($options);
			} else {
				$this->as->requireAuth($options);
			}

			return;

		}

		throw new Exception('User is not authenticated. Authentication is required for this operation.');
	}

	public function logoutAS($as) {
		//$as->logout('/loggedout');
		$as->logout();
	}

	public function logout() {


		// $this->authTypes = Config::getValue("authTypes");
		// $this->authSources = [];

		foreach($this->authSources AS $type => $authSource) {
			if ($authSource->isAuthenticated()) {
				$this->logoutAS($authSource);
			}
		}

	}

	// public static function logoutAll() {


	// 	throw new \Exception("Deprecated");

	// 	$authsources = ["saml"]; //, "google", "twitter", "facebook"];
	// 	foreach($authsources AS $as) {
	// 		$authsource = new \SimpleSAML_Auth_Simple($as);
	// 		$authsource->logout('/loggedout');
	// 	}
	// 	echo 'Logout is not yet fully implemented as part of the accountchooser branch'; exit;
	// }


	public function getAccount() {



		if (empty($this->activeAuthType) || !isset($this->authSources[$this->activeAuthType])) {
			throw new \Exception("Attempting to getAccount() when there is no active auth source");
		}
		$as = $this->authSources[$this->activeAuthType];

		$attributes = $as->getAttributes();
		$attributes['idp'] = $as->getAuthData('saml:sp:IdP');
		$attributes['authSource'] = $this->authTypes[$this->activeAuthType]["authSource"];
		$attributes['AuthnInstant'] = $as->getAuthData("AuthnInstant");


		$attributeMapper = new AttributeMapper();




		// print_r($as); exit; 
		$account = $attributeMapper->getAccount($attributes);


		return $account;

	}


}
