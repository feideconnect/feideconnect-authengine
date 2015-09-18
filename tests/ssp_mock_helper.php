<?php

class SimpleSAML_Auth_Simple {
	public function __construct($ignore) {
	}
	public function requireAuth() {
		return;
	}
	public function getAttributes() {
		return array(
			'eduPersonPrincipalName' => array('testuser@example.org'),
			'displayNAme' => 'Test User',
			'attr' => array('val1', 'val2'),
		);
	}
	public function isAuthenticated() {
		return true;
	}
	public function getAuthData($type) {
		$auth_data = array(
			"saml:sp:IdP" => "https://idp.feide.no",
			"AuthnInstant" => "ugle",
		);
		return $auth_data[$type];
	}
}
