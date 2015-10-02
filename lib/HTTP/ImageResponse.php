<?php


namespace FeideConnect\HTTP;

use FeideConnect\HTTP\HTTPResponse;

class ImageResponse extends HTTPResponse {


    protected $data;

    function __construct() {
        parent::__construct();
        $this->setCachable(true);
    }


    public function setImage($data, $type = null) {

        $this->data = $data;

        if ($type === 'jpeg') {
            $this->setHeader("Content-Type", "image/jpeg");
        } else if ($type === 'png') {
            $this->setHeader("Content-Type", "image/png");
        }

        return $this;
    }

    public function setImageFile($filename, $type = null) {

        $fullpath = dirname(dirname(__DIR__)) . '/' . $filename;
        if (!file_exists($fullpath)) {
            throw new \Exception('Could not find file ' . $filename);
        }
        $imagecontent = file_get_contents($fullpath);
        $this->setImage($imagecontent, $type);
        return $this;
    }




    protected function sendBody() {

        echo $this->data;


    }


}