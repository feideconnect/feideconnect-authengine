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

    \FeideConnect\Logger::info('Profiler stats', array(
        'method' => $method,
        'profiler' => [
            'computation_duration' => rutime($ru, $rustart, "utime"),
            'system_call_duration' => rutime($ru, $rustart, "stime"),
        ]
    ));

}
