<?php
namespace tests;

require_once(__DIR__ . '/../lib/_autoload.php');

putenv("AEENV=test");
if (getenv('AEENV') !== "test") {
	throw new \Exception("Not able to set environmentvariable for test environment.");
}

define('TESTUSER', 'testuser@example.org');
define('TESTUSER_SEC', 'feide:' . TESTUSER);

if (\FeideConnect\Config::getValue('storage.keyspace') == 'feideconnect') {
	throw new \Exception("Not running testes on production database");
}

require_once(__DIR__ . '/DBHelper.php');