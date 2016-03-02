<?php

namespace FeideConnect\Data\Types;

class Timestamp {


    protected $_value;

    public function __construct($input = null) {

        if ($input === null) {
            $this->setNow();
        } else {
            $this->_value = $input;
        }

    }

    private function setNow() {
        $this->_value = microtime(true);
        return $this;
    }


    public function addSeconds($sec) {
        $this->_value += $sec;
        return $this;
    }

    public function inPast () {

        $now = microtime(true);
        return $now > $this->_value;

    }

    public function getInSeconds() {
        return floor($this->_value - microtime(true));
    }


    public function getCassandraTimestamp() {
        return new \Cassandra\Type\Timestamp($this->_value * 1000);
    }

    public static function fromCassandraTimestamp($input) {
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

    public static function cmp(Timestamp $a, Timestamp $b) {
        if ($a->_value == $b->_value) {
            return 0;
        }
        return ($a->_value < $b->_value) ? -1 : 1;
    }

    public function datestring() {
        return date('Y-m-d', $this->_value);
    }

    public function roundseconds($seconds) {
        $value = intval($this->_value);
        $this->_value = $value - $value % $seconds;
    }
}
