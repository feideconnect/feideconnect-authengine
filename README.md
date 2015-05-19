# Feide Connect Auth Engine

[![Build Status](https://travis-ci.org/feideconnect/feideconnect-authengine.svg?branch=master)](https://travis-ci.org/andreassolberg/feideconnect-authengine)
[![Code Climate](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/gpa.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)
[![Test Coverage](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/coverage.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)

## Preparations

	# Runtime environemnt
	apt-get install apache2 php5 php5-cli php5-mcrypt php5-imagick php5-curl php5-gmp
 
	# building environment. Using node.js package manager npm.
	apt-get install nodejs nodejs-legacy
	curl https://www.npmjs.com/install.sh | sh

## Install

Make parent folder for Feide Connect.

	mkdir -p /var/feideconnect


Download SimpleSAMLphp

	cd /var/feideconnect
	wget https://simplesamlphp.org/res/downloads/simplesamlphp-1.13.2.tar.gz
	tar zxvf simplesamlphp-1.13.2.tar.gz
	ln -s simplesamlphp-1.13.2 simplesamlphp 

	cd simplesamlphp
	composer require duoshuo/php-cassandra dev-master
	composer require feideconnect/simplesamlphp-module-cassandrastore dev-master

Install Connect Auth Engine

	cd /var/feideconnect
	git clone https://github.com/feideconnect/feideconnect-authengine.git

	cd feideconnect-authengine
	composer update

Setup configuration files:

	cd /var/feideconnect

	mv simplesamlphp/config feideconnect-authengine/etc/simplesamlphp-config
	ln -s ../feideconnect-authengine/etc/simplesamlphp-config simplesamlphp/config

	mv simplesamlphp/metadata feideconnect-authengine/etc/simplesamlphp-metadata
	ln -s ../feideconnect-authengine/etc/simplesamlphp-metadata simplesamlphp/metadata


Configuration of SimpleSAMLphp:


Edit `feideconnect-authengine/etc/simplesamlphp-config/config.php`..


    'auth.adminpassword' => 'xxxx',
    'secretsalt' => 'xzmygojw4xmea8fplvcvfmyqk7sddhbv',
    'technicalcontact_name' => 'Feide Connect',
    'technicalcontact_email' => 'support@feide.no',
    'store.type'                    => 'cassandrastore:CassandraStore',
    'store.cassandra.nodes' => ["...."],
    'store.cassandra.keyspace' => 'sessionstore',


NPM initialization

installs: bower and grunt

	cd /var/feideconnect/feideconnect-authengine
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








