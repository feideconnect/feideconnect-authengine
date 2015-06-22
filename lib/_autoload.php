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


function getStrackTrace() {


    // Credits to http://makandracards.com/magento/8123-pretty-backtrace-stack-trace
    $d = debug_backtrace();
    array_shift($d);
    array_shift($d);

    // echo '<pre>'; print_r($d);

    $out = '';
    $c1width = strlen(count($d) + 1);
    $c2width = 0;
    foreach ($d as &$f) {
        if (!isset($f['file'])) $f['file'] = '';
        if (!isset($f['line'])) $f['line'] = '';
        if (!isset($f['class'])) $f['class'] = '';
        if (!isset($f['type'])) $f['type'] = '';
        // $f['file_rel'] = str_replace(BP . DS, '', $f['file']);
        $thisLen = strlen($f['file'] . ':' . $f['line']);
        if ($c2width < $thisLen) $c2width = $thisLen;
    }
    foreach ($d as $i => $f) {
        $args = '';
        if (isset($f['args'])) {
            $args = array();
            foreach ($f['args'] as $arg) {
                if (is_object($arg)) {
                    $str = get_class($arg);
                } elseif (is_array($arg)) {
                    $str = 'Array';
                } elseif (is_numeric($arg)) {
                    $str = $arg;
                } else {
                    $str = "'$arg'";
                }
                $args[] = $str;
            }
            $args = implode(', ', $args);
        }
        $out .= sprintf(
            "[%{$c1width}s] %-{$c2width}s %s%s%s(%s)\n",
            $i,
            $f['file'] . ':' . $f['line'],
            $f['class'],
            $f['type'],
            $f['function'],
            $args
        );
    }
    return $out;

}


/* Log full backtrace on errors and warnings. */
function Connect_error_handler($errno, $errstr, $errfile = NULL, $errline = 0, $errcontext = NULL) {

    static $limit = 5;
    $limit -= 1;
    if ($limit < 0) {
        /* We have reached the limit in the number of backtraces we will log. */
        return FALSE;
    }

    // echo "<pre>" . getStrackTrace(); exit;
    switch ($errno) {
        case E_USER_ERROR:

            Logger::error($errstr, [
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStrackTrace()
            ]);

            exit(1);
            break;

        case E_USER_WARNING:

            Logger::warning($errstr, [
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStrackTrace()
            ]);
            break;

        case E_USER_NOTICE:

            Logger::info($errstr, [
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStrackTrace()
            ]);
            break;

        default:

            Logger::info($errstr, [
                "errno" => $errno,
                "type" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStrackTrace()
            ]);
            break;
    }

    return false;
}
set_error_handler('Connect_error_handler');
