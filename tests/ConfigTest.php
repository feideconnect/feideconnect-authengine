<?php


namespace tests;

use FeideConnect\Config;

putenv("AEENV=test");
if (getenv('AEENV') !== "test") { 
	throw new \Exception("Not able to set environmentvariable for test environment."); 
}

class ConfigTest extends \PHPUnit_Framework_TestCase {


	protected $db;

	function __construct() {

		// $c = [
		// 	"foo" => [
		// 		"bar" => [
		// 			"one" => "two",
		// 			"two" => 2,
		// 			"three" => false
		// 		]
		// 	],
		// 	"ja" => "nei",
		// ];
		// $this->config = new Config($c);

	}


    public function testConfig() {

  //   	echo "oooooo";
		// echo("........==> " . Config::getValue('test.foo.lo')); 

		$this->assertTrue(Config::getValue('test.bar', true) === true, 'Config picking not existing prop should return default');
		$this->assertTrue(Config::getValue('test.foo.lo') === 3, 'Config read test.foo.lo === 3');

		$this->assertTrue(Config::getValue('test.foo.li', 3) === 3, 'Config read fall back to default param');

		// $this->assertTrue(Config::getValue('test.foo.li', null, true) === 3, 'should throw exceptoin');


    }


}