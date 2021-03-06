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

    public function getValue() {
        return $this->_value;
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
        $secs = (int)$this->_value;
        $usecs = (int)(($this->_value - $secs) * 1000000);
        return new \Cassandra\Timestamp($secs, $usecs);
    }

    public static function fromCassandraTimestamp($input) {
        return new self($input->microtime(true));
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
