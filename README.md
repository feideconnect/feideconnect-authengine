# Feide Connect Auth Engine

[![Build Status](https://travis-ci.org/andreassolberg/feideconnect-authengine.svg?branch=master)](https://travis-ci.org/andreassolberg/feideconnect-authengine)


## Preparations

	# Runtime environemnt
	apt-get install apache2 php5 php5-cli php5-mcrypt 

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


## CLI

	bin/feideconnect.php --help


## Test

	phpunit --bootstrap autoload.php tests 



