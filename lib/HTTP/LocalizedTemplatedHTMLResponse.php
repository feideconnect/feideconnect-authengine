<?php


namespace FeideConnect\HTTP;

use FeideConnect\Localization;
use FeideConnect\Config;
use FeideConnect\HTTP\HTTPResponse;

class LocalizedTemplatedHTMLResponse extends TemplatedHTMLResponse {


	protected $template;
	protected $data;

	protected $dictionary;


	function __construct($templateName) {
		parent::__construct($templateName);


		// Localization::debug();

		// echo "availableLanguages: "; var_dump($this->dictionary); exit;

		$this->dictionary = Localization::getDictionary();

		// $templateDir = Config::dir('templates');
		// $partialsDir = Config::dir('templates/partials');
		// $mustache = new \Mustache_Engine(array(
		// 	'loader' => new \Mustache_Loader_FilesystemLoader($templateDir),
		// 	// 'cache' => '/tmp/uwap-mustache',
		// 	'partials_loader' => new \Mustache_Loader_FilesystemLoader($partialsDir),
		// ));
		// $this->template = $mustache->loadTemplate($templateName);

		// $this->setCORS(false);

		// $this->data = null;
	}


	public function setData($data) {
		parent::setData($data);

		$this->data['_'] = $this->dictionary;

		// var_dump($this->data['_']); exit;
		return $this;
	}





	// protected function sendBody() {

	// 	echo "Debug dictionary and data objet: \n\n"; var_dump($this->data); exit;

	// 	echo $this->template->render($this->data);

	// }


}


