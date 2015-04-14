#! /bin/bash


# Load Auth Engine config from test directory.
export AEENV=test


phpunit --bootstrap lib/_autoload.php tests
casperjs test tests-casperjs/index.js
phantomjs tests-phantomjs/main.js
