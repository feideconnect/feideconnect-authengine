# Feide Connect Auth Engine

[![Code Climate](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/gpa.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)
[![Test Coverage](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/coverage.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)

## Preparations

	# Runtime environemnt
	apt-get install apache2 php5 php5-cli php5-mcrypt php5-imagick php5-curl php5-gmp

	a2enmod ssl
 
	# building environment. Using node.js package manager npm.
	apt-get install nodejs nodejs-legacy
	curl https://www.npmjs.com/install.sh | sh

## Install

Make parent folder for Feide Connect.

	mkdir -p /var/feideconnect


Install Connect Auth Engine

	cd /var/feideconnect
	git clone https://github.com/feideconnect/feideconnect-authengine.git

	cd feideconnect-authengine
	composer update

Download SimpleSAMLphp

	cd /var/feideconnect/feideconnect-authengine
	wget https://simplesamlphp.org/res/downloads/simplesamlphp-1.13.2.tar.gz
	tar zxf simplesamlphp-1.13.2.tar.gz
	ln -s simplesamlphp-1.13.2 simplesamlphp 

	cd simplesamlphp
	composer require duoshuo/php-cassandra dev-master
	composer require feideconnect/simplesamlphp-module-cassandrastore dev-master

Setup configuration files:

	cd /var/feideconnect

	mv simplesamlphp/config feideconnect-authengine/etc/simplesamlphp-config
	ln -s ../feideconnect-authengine/etc/simplesamlphp-config simplesamlphp/config

	mv simplesamlphp/metadata feideconnect-authengine/etc/simplesamlphp-metadata
	ln -s ../feideconnect-authengine/etc/simplesamlphp-metadata simplesamlphp/metadata


Configuration of SimpleSAMLphp:


Edit `feideconnect-authengine/etc/simplesamlphp-config/config.php`..


    'auth.adminpassword'       => '...',
    'secretsalt'               => '...',
    'technicalcontact_name'    => 'Feide Connect',
    'technicalcontact_email'   => 'support@feide.no',
    'store.type'               => 'cassandrastore:CassandraStore',
    'store.cassandra.nodes'    => ["..."],
    'store.cassandra.keyspace' => 'sessionstore',


NPM initialization

installs: bower and grunt

	cd /var/feideconnect/feideconnect-authengine
	npm install

Bower

	node_modules/bower/bin/bower install --allow-root

Post-bower, fill in licenced fonts.

* Add fonts into `static/components/uninett-bootstrap-theme/fonts/`.



## Managing translations



To update main dictionary content, upload `dictionaries/dictionary.en.json` to transifex.

To download translations run:

	grunt lang


## Run test suite with ant


	ant

## Run phpunit


	./vendor/bin/phpunit


## Test with automated clients


	casperjs test tests-casperjs/index.js
	phantomjs tests-phantomjs/main.js
	mocha --no-timeouts  tests-phantomjs-node/index.js








