<?php


namespace FeideConnect\HTTP;

use FeideConnect\Config;
use FeideConnect\HTTP\HTTPResponse;

class Redirect extends HTTPResponse {


	protected $template;
	protected $url;

	function __construct($url = null) {
		parent::__construct();

		$this->url = $url;

		/* Set the HTTP result code. This is either 303 See Other or
		 * 302 Found. HTTP 303 See Other is sent if the HTTP version
		 * is HTTP/1.1 and the request type was a POST request.
		 */
		if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
			$_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->setStatus(303);
		} else {
			$this->setStatus(302);
		}

		$this->setCachable(false);
		$this->setHeader('Location', $url);


	}

	public function setURL($url) {
		$this->url = $url;
	}

	public function getURL() {
		return $this->url;
	}

	protected function sendBody() {

		// Copied from SimplesAMLphp::Utilties.
		assert('is_string($this->url)');
		assert('!empty($this->url)');
		// assert('is_array($parameters)');
		// if (!empty($parameters)) {
		// 	$url = self::addURLparameter($url, $parameters);
		// }

		if (strlen($this->url) > 2048) {
			error_log('Redirecting to a URL longer than 2048 bytes.');
		}


		/* Show a minimal web page with a clickable link to the URL. */
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' .
			' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml">';
		echo '<head>
					<meta http-equiv="content-type" content="text/html; charset=utf-8">
					<title>Redirect</title>
				</head>';
		echo '<body>';
		echo '<h1>Redirect</h1>';
		echo '<p>';
		echo 'You were redirected to: ';
		echo '<a id="redirlink" href="' .
			htmlspecialchars($this->url) . '">' . htmlspecialchars($this->url) . '</a>';
		echo '<script type="text/javascript">document.getElementById("redirlink").focus();</script>';
		echo '</p>';
		echo '</body>';
		echo '</html>';

	}


}