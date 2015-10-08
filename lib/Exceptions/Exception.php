<?php

namespace FeideConnect\Exceptions;

/**
*
*/
class Exception extends \Exception {


    public $httpcode = 500;

    public function __construct($message, $httpcode = 500, $head = 'Internal Error') {
        parent::__construct($message);
        $this->httpcode = $httpcode;
        $this->head = $head;
    }

    public function setHTTPcode() {
        http_response_code($this->httpcode);
    }

    public function prepareErrorMessage() {

        $this->setHTTPcode();

        $data = array();
        $data['code'] = $this->httpcode;
        $data['head'] = $this->head;
        $data['message'] = $this->getMessage();
        return $data;

    }



}
