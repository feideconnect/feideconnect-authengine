<?php


namespace FeideConnect\Authentication;

use FeideConnect\Config;
use FeideConnect\Utils\URL;
use FeideConnect\Exceptions\Exception;


class AccountChooserProtocol {

	protected $selfURL;
	protected $baseURL;
	protected $response = null;

	function __construct() { 

		$this->selfURL = URL::selfURL();
		$this->baseURL = URL::getBaseURL() . 'accountchooser';

		if (isset($_REQUEST['acresponse'])) {
			$r = json_decode($_REQUEST['acresponse'], true);
			if (is_array($r)) {
				$this->response = $r;
			}
		}

	}

	public function getAuthConfig() {
		$ac = [
			"type" => "saml"
		];
		if (isset($this->response["type"])) {
			$ac["type"] = $this->response["type"];
		}

		if (isset($this->response["id"])) {
			$ac["idp"] = $this->response["id"];
		}
		if (isset($this->response["subid"])) {
			$ac["subid"] = $this->response["subid"];
		}
		return $ac;
	}

	public function hasResponse() {
		return ($this->response !== null);
	}

	public function getRequest() {
		$ro = [
			"return" => $this->selfURL,
		];
		return $this->baseURL . '?request=' . rawurlencode(json_encode($ro));
	}

	public function debug() {

		echo '<pre>' . "accountchooser: \n";
		echo "Base URL " . $this->baseURL . "\n";
		echo "Self URL " . $this->selfURL . "\n";
		print_r($this->response);

		echo "\n-----\n Request url \n   " . $this->getRequest();
		exit;

	}


}