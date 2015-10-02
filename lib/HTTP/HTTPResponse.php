<?php

namespace FeideConnect\HTTP;

/**
*
*/
class HTTPResponse {

    protected $headers;
    protected $status;

    protected $cors;
    protected $cachable;

    function __construct(){
        $this->headers = [];
        $this->status = 200;

        $this->cors = false;
        $this->cachable = false;
    }

    protected function preprocess() {

        if ($this->cors) {
            $this->setHeader('Access-Control-Allow-Origin', '*');
            $this->setHeader('Access-Control-Allow-Methods', 'HEAD, GET, OPTIONS, POST, PATCH, DELETE');
            $this->setHeader('Access-Control-Allow-Headers', 'Authorization, X-Requested-With, Origin, Accept, Content-Type');
            $this->setHeader('Access-Control-Expose-Headers', 'Authorization, X-Requested-With, Origin, Accept, Content-Type');
        }

        if (!$this->cachable) {
            $this->setHeader('Cache-Control', 'no-cache, must-revalidate');
            $this->setHeader('Expires', 'Fri, 10 Oct 1980 04:00:00 GMT');
        }

    }

    protected function sendStatus() {
        http_response_code($this->status);
    }

    protected function sendHeaders() {

        foreach($this->headers as $k => $v) {
            header($k . ": " . $v);
        }

    }

    protected function sendBody() {



    }

    public function setCORS($enable) {
        $this->cors = $enable;
        return $this;
    }

    public function setCachable($enable) {
        $this->cachable = $enable;
        return $this;
    }

    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setHeader($attr, $value) {
        $this->headers[$attr] = $value;
        return $this;
    }


    public function send() {


        $this->preprocess();


        $this->sendHeaders();
        $this->sendStatus();
        $this->sendBody();


    }

}
