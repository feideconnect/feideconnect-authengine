<?php


namespace tests;

use FeideConnect\OpenIDConnect\IDToken;
use FeideConnect\OpenIDConnect\TrustStore;
use FeideConnect\Config;

// use FeideConnect\Router;
// use FeideConnect\HTTP\JSONResponse;
// use FeideConnect\Data\StorageProvider;
// use FeideConnect\Data\Models;

class IDTokenTest extends \PHPUnit_Framework_TestCase {

	protected $truststore;


	function __construct() {


		$this->truststore = new TrustStore();

	}

    public function testIDToken() {


    	$iss = Config::getValue('connect.issuer');
    	// TrustStore $trustStore, $iss, $sub, $aud, $expiresIn, $auth_time = null) {
    	$idtoken = IDToken::generate($this->truststore, $iss, 'http://sp.example.org', 3600, null);
    	$idtxt = $idtoken->getEncoded();

    	// echo "IDToken is " . $idtxt . "\n";

    	$this->assertInternalType('string', $idtxt, 'ID TOken string is text');
    	$this->assertGreaterThan(80, strlen($idtxt), 'ID Token string is more than 80 character long');

    	// var_dump($idtoken);


    }




}