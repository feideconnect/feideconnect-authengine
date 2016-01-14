<?php
namespace tests;

use FeideConnect\Utils\Misc;

class MiscUtilsTest extends \PHPUnit_Framework_TestCase {

    public function testEnsureArray() {
        $this->assertEquals([], Misc::ensureArray(null));
        $list = [1, 4, 3];
        $this->assertEquals($list, Misc::ensureArray($list));
    }

    public function testContainsSameElements() {
        $this->assertTrue(Misc::containsSameElements([], []));
        $this->assertTrue(Misc::containsSameElements([1, 2, 3], [3, 2, 1]));
        $this->assertFalse(Misc::containsSameElements([], [3, 2, 1]));
        $this->assertFalse(Misc::containsSameElements([3, 2, 1], []));
        $this->assertFalse(Misc::containsSameElements([3, 2, 1], [3, 2, 1, 4]));
        $this->assertFalse(Misc::containsSameElements([3, 2, 1, 4], [3, 2, 1]));
    }
}
