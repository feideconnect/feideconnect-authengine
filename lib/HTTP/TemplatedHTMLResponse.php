<?php


namespace FeideConnect\HTTP;

use FeideConnect\Config;
use JaimePerez\TwigConfigurableI18n\Twig\Environment as Twig_Environment;
use JaimePerez\TwigConfigurableI18n\Twig\Extensions\Extension\I18n as Twig_Extensions_Extension_I18n;

class TemplatedHTMLResponse extends HTTPResponse {


    protected $template;
    protected $data;

    public function __construct($templateName) {
        parent::__construct();

        $templateDir = Config::dir('templates');
        $partialsDir = Config::dir('templates/partials');

        if (Config::getValue('twig.use', false) === true) {
            $loader = new \Twig_Loader_Filesystem();
            $loader->addPath($templateDir);
            $twig = new Twig_Environment(
                $loader,
                array(
                    'cache' => Config::getValue('twig.cacheDir', '/tmp'),
                    'auto_reload' => Config::getValue('twig.autoReload', true),
                    'translation_function' => array('\FeideConnect\Localization', 'translateSingular'),
                )
            );
            $twig->addExtension(new Twig_Extensions_Extension_I18n());
            $this->template = $twig->load($templateName.'.twig');
        } else {
            $mustache = new \Mustache_Engine(array(
                'loader'          => new \Mustache_Loader_FilesystemLoader($templateDir),
                'partials_loader' => new \Mustache_Loader_FilesystemLoader($partialsDir),
            ));
            $this->template = $mustache->loadTemplate($templateName);
        }

        $this->setCORS(false);

        $this->data = Config::getTemplateConfig();
        $this->data['queryParams'] = $_GET;
        $this->data['templateName'] = $templateName;
        $this->denyFrame = true;
    }

    public function setDenyFrame($deny) {
        $this->denyFrame = $deny;
    }

    public function setData($data) {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function getData() {
        return $this->data;
    }

    protected function sendBody() {

        echo $this->template->render($this->data);

    }

    protected function preprocess() {
        parent::preprocess();
        if ($this->denyFrame) {
            $this->setHeader('X-Frame-Options', 'DENY');
        }
    }
}
