<?php


namespace tests;

use FeideConnect\CLI;
use FeideConnect\Config;
use FeideConnect\Router;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Data\Models;

class CLITest extends \PHPUnit_Framework_TestCase {


    protected $cli;

    public function __construct() {

        // $config = json_decode(file_get_contents(__DIR__ . '/../etc/ci/config.json'), true);
        $this->cli = new CLI();


    }

    public function testOAuthConfig() {

    }




}
