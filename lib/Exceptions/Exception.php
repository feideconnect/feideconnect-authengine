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
            'stacktrace' => $this->formatStackTrace($this->getTrace()),
            'errordetails' => $data,
        ));

        $response->setStatus($this->httpcode);
        return $response;
    }

    // Credits to http://makandracards.com/magento/8123-pretty-backtrace-stack-trace
    public static function formatStackTrace($traceback) {
        // echo '<pre>'; print_r($traceback);

        $out = '';
        $c1width = strlen(count($traceback) + 1);
        $c2width = 0;
        foreach ($traceback as &$f) {
            if (!isset($f['file'])) {
                $f['file'] = '';
            }
            if (!isset($f['line'])) {
                $f['line'] = '';
            }
            if (!isset($f['class'])) {
                $f['class'] = '';
            }
            if (!isset($f['type'])) {
                $f['type'] = '';
            }
            // $f['file_rel'] = str_replace(BP . DS, '', $f['file']);
            $thisLen = strlen($f['file'] . ':' . $f['line']);
            if ($c2width < $thisLen) {
                $c2width = $thisLen;
            }
        }
        foreach ($traceback as $i => $f) {
            $args = '';
            if (isset($f['args'])) {
                $args = array();
                foreach ($f['args'] as $arg) {
                    if (is_object($arg)) {
                        $str = get_class($arg);
                    } elseif (is_array($arg)) {
                        $str = 'Array';
                    } elseif (is_numeric($arg)) {
                        $str = $arg;
                    } else {
                        $str = "'$arg'";
                    }
                    $args[] = $str;
                }
                $args = implode(', ', $args);
            }
            $out .= sprintf(
                "[%{$c1width}s] %-{$c2width}s %s%s%s(%s)\n",
                $i,
                $f['file'] . ':' . $f['line'],
                $f['class'],
                $f['type'],
                $f['function'],
                $args
            );
        }
        return $out;
    }
}
