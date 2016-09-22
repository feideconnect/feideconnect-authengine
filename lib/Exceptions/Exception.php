<?php

namespace FeideConnect\Exceptions;

use FeideConnect\Logger;
use FeideConnect\HTTP\TemplatedHTMLResponse;

/**
*
*/
class Exception extends \Exception {


    public $httpcode = 500;

    public function __construct($message, $httpcode = 500, $head = 'Internal Error') {
        parent::__construct($message);
        $this->httpcode = $httpcode;
        $this->head = $head;
        $this->classname = get_class($this);
    }

    public static function fromException($e) {
        $n = new self($e->getMessage());
        $n->classname = get_class($e);
        return $n;
    }

    public function prepareErrorMessage() {
        $data = array();
        $data['code'] = $this->httpcode;
        $data['head'] = $this->head;
        $data['message'] = $this->getMessage();
        return $data;

    }

    public function getResponse() {
        $data = $this->prepareErrorMessage();
        $response = (new TemplatedHTMLResponse('exception'))->setData($data);

        Logger::error('Exception: ' . $this->getMessage(), array(
            'exception_class' => $this->classname,
            'stacktrace' => $this->getTrace(),
            'errordetails' => $data,
        ));

        $response->setStatus($this->httpcode);
        return $response;
    }

}
