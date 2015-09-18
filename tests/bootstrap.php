<?php
namespace tests;

require_once(__DIR__ . '/../lib/_autoload.php');

putenv("AEENV=test");
if (getenv('AEENV') !== "test") {
	throw new \Exception("Not able to set environmentvariable for test environment.");
}

define('TESTUSER', 'testuser@example.org');
define('TESTUSER_SEC', 'feide:' . TESTUSER);
