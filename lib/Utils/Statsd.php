<?php

namespace FeideConnect\Utils;

class Statsd {

    private static $instance = null;

    private static function initInstance() {
        $config = \FeideConnect\Config::getInstance();

        $server = $config->get('statsd.server', null);
        if ($server !== null) {
            $connection = new \Domnikl\Statsd\Connection\UdpSocket($server, 8125);
        } else {
            $connection = new \Domnikl\Statsd\Connection\Blackhole();
        }

        $namespace = $config->get('statsd.namespace', 'dataporten.authengine');
        $statsd = new \Domnikl\Statsd\Client($connection, $namespace);
        return $statsd;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = self::initInstance();
        }
        return self::$instance;
    }

}
