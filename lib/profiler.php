<?php


// Script end
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

// Script start
$rustart = getrusage();



function profiler_status() {
	global $rustart;
	$ru = getrusage();
	echo "---------- Profiler stats --------- \n";
	echo "Computation  : " . rutime($ru, $rustart, "utime") . " ms\n";
	echo "System calls : " . rutime($ru, $rustart, "stime") . " ms\n";
}