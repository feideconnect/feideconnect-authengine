<?php

/**
 * An example of a project-specific implementation.
 * 
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Foo\Bar\Baz\Qux class
 * from /path/to/project/src/Baz/Qux.php:
 * 
 *      new \Foo\Bar\Baz\Qux;
 *      
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'FeideConnect\\';

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



if (get_magic_quotes_runtime()) {
    set_magic_quotes_runtime(FALSE);
}




require_once(__DIR__ . '/profiler.php');
require_once(dirname(__DIR__) . '/vendor/autoload.php');
require_once(dirname(dirname(__DIR__)) . '/simplesamlphp/lib/_autoload.php');

// error_reporting(E_ERROR | E_WARNING);



use FeideConnect\Logger;

/* Log full backtrace on errors and warnings. */
function Connect_error_handler($errno, $errstr, $errfile = NULL, $errline = 0, $errcontext = NULL) {

    static $limit = 5;
    $limit -= 1;
    if ($limit < 0) {
        /* We have reached the limit in the number of backtraces we will log. */
        return FALSE;
    }

    var_dump($errcontext); exit;


    switch ($errno) {
        case E_USER_ERROR:

            Logger::error($errstr, [
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext
            ]);

            exit(1);
            break;

        case E_USER_WARNING:

            Logger::warning($errstr, [
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext
            ]);
            break;

        case E_USER_NOTICE:

            Logger::info($errstr, [
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext
            ]);
            break;

        default:

            Logger::info($errstr, [
                "errno" => $errno,
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext
            ]);
            break;
    }

    return false;
}
set_error_handler('Connect_error_handler');
