# Instructions on testing



How to run phpunit tests:

	clear; AEENV=test phpunit --bootstrap lib/_autoload.php tests


The AEENV variable should be set to `test` in order to use the configuration in `etc/test/*`. The AEENV variable might also be set to `CI` when running tests in CI.









AEENV=test phpunit --bootstrap lib/_autoload.php tests/User