# Instructions on testing




## PHP Unit Testing

How to run phpunit tests:

	clear; AEENV=test phpunit --bootstrap lib/_autoload.php tests


The AEENV variable should be set to `test` in order to use the configuration in `etc/test/*`. The AEENV variable might also be set to `CI` when running tests in CI.





## Running overall system tests

Test suites using Node.js + Phantomjs + Mocha.

Password for test user is set using an environment variable named `password`.


	cd tests-phantomjs-node
	password=xxxxxx mocha --no-timeouts index


