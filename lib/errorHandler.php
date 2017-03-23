<?php
use FeideConnect\Logger;

function getStackTrace() {


    // Credits to http://makandracards.com/magento/8123-pretty-backtrace-stack-trace
    $d = debug_backtrace();
    array_shift($d);
    array_shift($d);

    // echo '<pre>'; print_r($d);

    $out = '';
    $c1width = strlen(count($d) + 1);
    $c2width = 0;
    foreach ($d as &$f) {
        if (!isset($f['file'])) {
            $f['file'] = '';
        }
        if (!isset($f['line'])) {
            $f['line'] = '';
        }
        if (!isset($f['class'])) {
            $f['class'] = '';
        }
        if (!isset($f['type'])) {
            $f['type'] = '';
        }
        // $f['file_rel'] = str_replace(BP . DS, '', $f['file']);
        $thisLen = strlen($f['file'] . ':' . $f['line']);
        if ($c2width < $thisLen) {
            $c2width = $thisLen;
        }
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
function Connect_error_handler($errno, $errstr, $errfile = null, $errline = 0, $errcontext = null) {

    static $limit = 5;
    $limit -= 1;
    if ($limit < 0) {
        /* We have reached the limit in the number of backtraces we will log. */
        return false;
    }

    // echo "<pre>" . getStackTrace(); exit;
    switch ($errno) {
        case E_USER_ERROR:

            Logger::error($errstr, [
                "errortype" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStackTrace()
            ]);

            exit(1);
            break;

        case E_USER_WARNING:

            Logger::warning($errstr, [
                "errortype" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStackTrace()
            ]);
            break;

        case E_USER_NOTICE:

            Logger::info($errstr, [
                "errortype" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStackTrace()
            ]);
            break;

        default:

            Logger::info($errstr, [
                "errno" => $errno,
                "errortype" => "phperror",
                "line" => $errline,
                "file" => $errfile,
                "context" => $errcontext,
                "stacktrace" => getStackTrace()
            ]);
            break;
    }

    return false;
}
