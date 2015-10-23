<?php
namespace tests;

class MockAuthSource {
    static $sources;
    public $authenticated;
    public static $attributes = array(
        'eduPersonPrincipalName' => array('testuser@example.org'),
        'displayNAme' => 'Test User',
        'attr' => array('val1', 'val2'),
    );

    public function __construct($type) {
        $this->authenticated = true;
    }

    public function requireAuth() {
        return;
    }

    public function login($config) {
        return;
    }

    public function getAttributes() {
        return self::$attributes;
    }
    public function isAuthenticated() {
        return $this->authenticated;
    }

    public function getAuthData($type) {
        $auth_data = array(
            "saml:sp:IdP" => "https://idp.feide.no",
            "AuthnInstant" => "ugle",
        );
        return $auth_data[$type];
    }

    public static function set($type, $obj) {
        if (self::$sources === null) {
            self::$sources = array();
        }
        self::$sources[$type] = $obj;
    }

    public static function create($type) {
        if (self::$sources === null) {
            self::$sources = array();
        }
        if (isset(self::$sources[$type])) {
            return self::$sources[$type];
        }
        self::set($type, new MockAuthSource($type));
        return self::$sources[$type];
    }

    public static function clear() {
        self::$sources = array();
    }
}
