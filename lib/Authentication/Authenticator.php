<?php

namespace FeideConnect\Authentication;

/**
 * This class handles all authentication, and uses SimpleSAMLphp for that task.
 * It will also handle all local user creation. All new users will be stored in the user repository.
 * 
 */
class Authenticator {

	protected $as, $user;

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

		error_log("Perform passive authentication..");

		$this->as->login(array(
			'isPassive' => true,
			'ErrorURL' => \SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), array(
				"error" => 1,
			)),
		));

	}


	/**
	 * Require authentication of the user. This is meant to be used with user frontend access.
	 * 
	 * @param  boolean $isPassive     [description]
	 * @param  boolean $allowRedirect Set to false if using on an API where user cannot be redirected.
	 * @param  [type]  $return        URL to return to after login.
	 * @return void
	 */
	public function req($isPassive = false, $allowRedirect = false, $return = null) {

		if ($this->as->isAuthenticated()) {
			return;
		}

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

			// echo "about to require authentication "; print_r($options); print_r($_COOKIE); exit;
			$this->as->requireAuth($options);

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

		// print_r($this->as); exit; 
		$account = new Account($attributes);

		return $account;

	}


}
