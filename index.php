<?php

require_once(__DIR__ . '/autoload.php');

// error_reporting(E_ERROR | E_PARSE);


header('Content-Type: text/plain; charset=utf-8');

$dbConfig = json_decode(file_get_contents(__DIR__ . '/etc/config.json'), true);
$c = new \FeideConnect\Data\Repositories\Cassandra($dbConfig);

// $data = $c->getAccessToken();



$client = $c->getClient('a5b9491e-372d-49d9-943c-63d40dcb67f4');
$client->debug();



profiler_status();
