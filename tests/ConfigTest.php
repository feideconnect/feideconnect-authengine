<?php


namespace tests;

use FeideConnect\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase {


    protected $db;

    public function __construct() {

        // $c = [
        //     "foo" => [
        //         "bar" => [
        //             "one" => "two",
        //             "two" => 2,
        //             "three" => false
        //         ]
        //     ],
        //     "ja" => "nei",
        // ];
        // $this->config = new Config($c);

    }


    public function testConfig() {

  //       echo "oooooo";
        // echo("........==> " . Config::getValue('test.foo.lo'));

        $this->assertTrue(Config::getValue('test', true) === true, 'Config picking not existing prop should return default');
        $this->assertTrue(Config::getValue('storage.type') === 'cassandra', 'Config read storage.type === cassandra');
        $this->assertTrue(Config::getValue('test.foo.li', 3) === 3, 'Config read fall back to default param');

        // $this->assertTrue(Config::getValue('test.foo.li', null, true) === 3, 'should throw exceptoin');


    }
}
