<?php


namespace FeideConnect\HTTP;

use FeideConnect\Localization;
use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\Config;

class LocalizedTemplatedHTMLResponse extends TemplatedHTMLResponse {


    protected $template;
    protected $data;

    protected $dictionary;

    protected $replacementIndexes, $replacementData;


    public function __construct($templateName) {
        parent::__construct($templateName);

        $this->replacementIndexes = [];
        $this->replacementData = [];
        // Localization::debug();
        $this->dictionary = Localization::getDictionary();
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

        return $this;
    }

}
