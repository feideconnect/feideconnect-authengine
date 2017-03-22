<?php


// Script end
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

// Script start
$rustart = getrusage();



function profiler_status($method) {
    global $rustart;
    $ru = getrusage();

    $utime = rutime($ru, $rustart, "utime");
    $stime = rutime($ru, $rustart, "stime");

    \FeideConnect\Logger::info('Profiler stats', array(
        'method' => $method,
        'profiler' => [
            'computation_duration' => $utime,
            'system_call_duration' => $stime,
        ]
    ));

    $path = \FeideConnect\Utils\URL::selfPathNoQuery();
    $path = substr($path, 1); // Remove leading `/`
    $path = preg_replace('/[^A-Za-z0-9-_]+/', '_', $path); // Replace all special characters with `_`.

    $statsd = \FeideConnect\Utils\Statsd::getInstance();
    $statsd->timing('profiler.' . $path . '.utime', $utime);
    $statsd->timing('profiler.' . $path . '.stime', $stime);
}
