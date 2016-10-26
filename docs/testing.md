# Instructions on testing


## PHP Unit Testing

How to run phpunit tests:

    ./run-test.sh



## Running overall system tests

Test suites using Node.js + Phantomjs + Mocha.

Password for test user is set using an environment variable named `password`.


	cd tests-phantomjs-node
	password=xxxxxx mocha --no-timeouts index


