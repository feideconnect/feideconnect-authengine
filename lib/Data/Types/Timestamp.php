<?php

namespace FeideConnect\Data\Types;

class Timestamp {


    protected $_value;

    function __construct($input = null) {

        if ($input === null) {
            $this->setNow();
        } else {
            $this->_value = $input;
        }

    }

    function setNow() {
        $this->_value = microtime(true);
        return $this;
    }


    function addSeconds($sec) {
        $this->_value += $sec;
        return $this;
    }

    function inPast () {

        $now = microtime(true);
        return $now > $this->_value;

    }

    function getInSeconds() {
        return floor($this->_value - microtime(true) );
    }


    function getCassandraTimestamp() {
        return new \Cassandra\Type\Timestamp($this->_value * 1000);
    }

    static function fromCassandraTimestamp($input) {
        return new self($input / 1000.0);
    }

    public function getDBobject() {
        return $this->getCassandraTimestamp();
    }

    public function __toString() {
        return date('c', $this->_value);
    }

    public function format() {
        return date('c', $this->_value);
    }


}