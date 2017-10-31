<?php
namespace FeideConnect\HTTP;

use FeideConnect\Localization;
use FeideConnect\Config;
use FeideConnect\Utils\Misc;

class LocalizedTemplatedHTMLResponse extends TemplatedHTMLResponse {

    protected $template;
    protected $data;
    protected $dictionary;
    protected $language;
    protected $replacementIndexes, $replacementData;

    public function __construct($templateName) {
        parent::__construct($templateName);
        $this->replacementIndexes = [];
        $this->replacementData = [];
        $this->language = Misc::getBrowserLanguage(Config::getValue('availableLanguages', ['en']));
        $this->dictionary = Localization::getDictionary($this->language);
        $this->setHeader('Vary', 'Accept-Encoding,Accept-Language');
    }

    /*
     * Needs to be called before setData.
     * USe this to add replacedment set for in example:
     * {ORG}  => UNINETT
     * {LOCATION}  => Trondheim
     *
     * Will only be applied to the dictionary keys provided at indexes.
     */
    public function setReplacements($indexes, $data) {
        $this->replacementIndexes = $indexes;
        $this->replacementData = $data;
        return $this;
    }

    public function setData($data) {
        parent::setData($data);

        $this->data['_'] = $this->dictionary;

        // Apply replacementsets if available. Should be set with setReplacements
        foreach($this->replacementIndexes AS $idx) {
            if (isset($this->data['_'][$idx])) {
                foreach($this->replacementData AS $from => $to) {
                    $this->data['_'][$idx] = str_replace('{' . $from . '}', $to, $this->data['_'][$idx]);
                }
            }

        }
        $this->data = array_merge($this->data, Config::getTemplateConfig());
        $this->data['currentLanguage'] = $this->language;
        $this->data['languageParameterName'] = Localization::LANGUAGE_PARAM_NAME;
        if (isset($this->data['queryParams'][$this->data['languageParameterName']])) {
            unset($this->data['queryParams'][$this->data['languageParameterName']]);
        }
        return $this;
    }

}
