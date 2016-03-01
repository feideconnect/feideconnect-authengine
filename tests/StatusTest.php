<?php
namespace tests;

use FeideConnect\Controllers\Status;

class StatusTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        putenv("FC_STATUS_TOKEN=foo");
        putenv("JENKINS_BUILD_NUMBER=1");
        putenv("DOCKER_INSTANCE=green");
    }

    public function testStatus() {
        $_SERVER['HTTP_X_DP_STATUS_TOKEN'] = 'foo';
        $res = Status::status();
        $this->assertEquals([
            'info' => [
                'JENKINS_BUILD_NUMBER' => '1',
                'DOCKER_INSTANCE' => 'green',
            ],
        ], $res->getData());
    }

    public function testBadToken() {
        $_SERVER['HTTP_X_DP_STATUS_TOKEN'] = '';
        $res = Status::status();
        $this->assertEquals(403, $res->getStatus());
    }
}