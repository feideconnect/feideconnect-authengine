<?php


namespace FeideConnect\HTTP;

use FeideConnect\Config;
use FeideConnect\HTTP\HTTPResponse;

class TemplatedHTMLResponse extends HTTPResponse {


	protected $template;
	protected $data;

	function __construct($templateName) {
		parent::__construct();

		$templateDir = Config::dir('templates');
		$mustache = new \Mustache_Engine(array(
			'loader' => new \Mustache_Loader_FilesystemLoader($templateDir),
			// 'cache' => '/tmp/uwap-mustache',
			// 'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
		));
		$this->template = $mustache->loadTemplate($templateName);

		$this->setCORS(false);

		$this->data = null;
	}


	public function setData($data) {
		$this->data = $data;
		return $this;
	}


	protected function sendBody() {

		echo $this->template->render($this->data);

	}


}