<?php
namespace tests;

require_once(__DIR__ . '/../lib/_autoload.php');

putenv("AEENV=test");
if (getenv('AEENV') !== "test") {
	throw new \Exception("Not able to set environmentvariable for test environment.");
}

spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'tests\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

define('TESTUSER', 'testuser@example.org');
define('TESTUSER_SEC', 'feide:' . TESTUSER);

if (\FeideConnect\Config::getValue('storage.keyspace') == 'feideconnect') {
	throw new \Exception("Not running testes on production database");
}
