<?php


namespace FeideConnect\HTTP;

use FeideConnect\HTTP\HTTPResponse;

class JSONResponse extends HTTPResponse {


    protected $data;
    protected $prettyprint;

    function __construct($data = null) {
        parent::__construct();

        $this->prettyprint = true;
        $this->data = $data;

        $this->setCORS(true);
        $this->setHeader("Content-Type", "application/json; charset=utf-8");
    }


    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    public function getData() {
        return $this->data;
    }

    protected function sendBody() {

        if ($this->prettyprint) {
            echo json_encode($this->data, JSON_PRETTY_PRINT);
        } else {
            echo json_encode($this->data);
        }

    }


}