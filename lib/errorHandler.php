<?php
use FeideConnect\Logger;

function getStackTrace() {


    $d = debug_backtrace();
    array_shift($d);
    array_shift($d);

    return \FeideConnect\Exceptions\Exception::formatStackTrace($d);
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
