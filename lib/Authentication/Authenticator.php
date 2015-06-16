<?php

namespace FeideConnect\Authentication;

use FeideConnect\Config;

use FeideConnect\Logger;


/**
 * This class handles all authentication, and uses SimpleSAMLphp for that task.
 * It will also handle all local user creation. All new users will be stored in the user repository.
 * 
 */
class Authenticator {

	protected $as, $user, $authSource;

	public function __construct() {

		$this->authSource = 'default-sp';

		$this->as = new \SimpleSAML_Auth_Simple($this->authSource);
		$this->user = null;

	}


	/**
	 * Assumes the user is not logged in, and performs a isPassive=true login request against the IdP
	 * @return [type] [description]
	 */
	protected function authenticatePassive() {

		

		$this->as->login([
			'isPassive' => true,
			'saml:idp' => Config::getValue("defaultIdP"),
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
	public function req($isPassive = false, $allowRedirect = false, $return = null, $maxage = null) {


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

			$defaultidp = \FeideConnect\Config::getValue('idp', false);
			$options = array();

			if ($defaultidp !== false) {
				$options['saml:idp'] = $defaultidp;
			}
			if (isset($_COOKIE['idp'])) {
				$options['saml:idp'] = $_COOKIE['idp'];
			}

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

	public function logout() {
		$this->as->logout('/loggedout');
	}



	public function getAccount() {

		$attributes = $this->as->getAttributes();
		$attributes['idp'] = $this->as->getAuthData('saml:sp:IdP');
		$attributes['authSource'] = $this->authSource;
		$attributes['AuthnInstant'] = $this->as->getAuthData("AuthnInstant");


		$attributeMapper = new AttributeMapper();


		// print_r($this->as); exit; 
		$account = $attributeMapper->getAccount($attributes);

		return $account;

	}


}
