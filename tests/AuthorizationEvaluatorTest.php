<?php

namespace tests;

use FeideConnect\OAuth\AuthorizationEvaluator;
use FeideConnect\Data\StorageProvider;
use FeideConnect\OAuth\Messages\AuthorizationRequest;

class AuthorizationEvaluatorTest extends \PHPUnit_Framework_TestCase {

	protected $db, $dbhelper;
	protected $user, $client, $aevaluator;

	function __construct() {

		// $config = json_decode(file_get_contents(__DIR__ . '/../etc/ci/config.json'), true);
		$this->db = StorageProvider::getStorage();
		$this->dbhelper = new DBHelper();

		$this->user = $this->dbhelper->user();
		$this->client = $this->dbhelper->client();
	}

	function getRequest($redirect_uri = null) {

		$req = [
			"response_type" => "code",
			"scope" => "userinfo-mail userinfo",
			"client_id" => "f1343f3a-79cc-424f-9233-5fe33f8bbd56"
		];
		if ($redirect_uri !== null) {
			$req['redirect_uri'] = $redirect_uri;
		}
		return new AuthorizationRequest($req);
	}

	function testAuthorizationEvaluatorNoURLinRequest() {


		$request = $this->getRequest();
		$this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request);	
		$redirect_uri = $this->aevaluator->getValidatedRedirectURI();
		$this->assertEquals($redirect_uri, 'http://example.org', 'When request does not contain any redirect_uri, return first preconfigured one.');

	}

	function testAuthorizationEvaluatorCorrectURLinRequest() {


		$request = $this->getRequest('http://example.org');
		$this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request);	
		$redirect_uri = $this->aevaluator->getValidatedRedirectURI();
		$this->assertEquals($request->redirect_uri, 'http://example.org', 'Request contains redirect_uri');
		$this->assertEquals($redirect_uri, 'http://example.org', 'When request does contain correct rediret_uri it should be accepted. ');

	}


	function testAuthorizationEvaluatorBadURLinRequest() {

		$this->setExpectedException('FeideConnect\OAuth\Exceptions\OAuthException');
		$request = $this->getRequest('http://bad.example.org');
		$this->aevaluator = new AuthorizationEvaluator($this->db, $this->client, $request);	
		$redirect_uri = $this->aevaluator->getValidatedRedirectURI();

	}


}
