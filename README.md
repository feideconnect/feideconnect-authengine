# Feide Connect Auth Engine

[![Build Status](https://travis-ci.org/feideconnect/feideconnect-authengine.svg?branch=master)](https://travis-ci.org/andreassolberg/feideconnect-authengine)
[![Code Climate](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/gpa.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)
[![Test Coverage](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/coverage.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)

## Preparations

	# Runtime environemnt
	apt-get install apache2 php5 php5-cli php5-mcrypt php5-imagick php5-curl
 
	# building environment. Using node.js package manager npm.
	apt-get install nodejs nodejs-legacy
	curl https://www.npmjs.com/install.sh | sh

## Install


Composer initialization

installs PHP dependencies

	composer install
	cp -r vendor/andreassolberg/simplesamlphp/config-tempates etc/simplesamlphp-config
	ln -s ../../../etc/simplesamlphp-config vendor/andreassolberg/simplesamlphp/config

	cp -r vendor/andreassolberg/simplesamlphp/metadata-templates etc/simplesamlphp-metadata
	ln -s ../../../etc/simplesamlphp-metadata vendor/andreassolberg/simplesamlphp/metadata

	Configure SimpleSAMLphp



NPM initialization

installs: bower and grunt

	npm install

Bower

	node_modules/bower/bin/bower install --allow-root


Initalize Cassandra schema

	cqlsh $HOST -f etc/bootstrap.init-keyspace.sql
	cqlsh $HOST -f etc/bootstrap.sql



## CLI

	bin/feideconnect.php --help


## Test


	phpunit --bootstrap lib/_autoload.php tests
	casperjs test tests-casperjs/index.js
	phantomjs tests-phantomjs/main.js
	mocha --no-timeouts  tests-phantomjs-node/index.js








