# Feide Connect Auth Engine

[![Code Climate](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/gpa.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)
[![Test Coverage](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/coverage.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)


## Docker


Build docker image

```
docker build -t dataporten-authengine .
```

Runnning docker image

```
docker run -p 8080:80 --env-file ENV dataporten-authengine
```

## configuration

Main configuration of Auth engine:

```
etc/config.json   - Main configuration
etc/disco2.json   - Auth providers options
etc/scopedef.json -
```

SimpleSAMLphp configuration

```
etc/
```

**Enrivonment variables Auth engine**

Used by both auth engine and apache

```
AE_SERVER_NAME=auth.dataporten.no
FC_CASSANDRA_CONTACTPOINTS=cassandra
FC_CASSANDRA_KEYSPACE=dataporten
FC_CASSANDRA_USESSL=false
```

Used by apache only

```
APACHE_LOCK_DIR=/var/
SERVER_ADMIN=kontakt@uninett.no
```

Used by auth engine

```
CASSANDRA_USERNAME=dataporten
CASSANDRA_PASSWORD=xxx
DEFAULT_IDP=https://idp.feide.no
AE_DEBUG=true
AE_SALT=xxx
FC_ENDPOINT_GROUPS=https://groups-api.dataporten.no
FC_ENDPOINT_CORE=https://api.dataporten.no
```

Unspecified

```
AE_AS_TWITTER_KEY=xxx
AE_AS_TWITTER_SECRET=xxx
AE_AS_LINKEDIN_KEY=xxx
AE_AS_LINKEDIN_SECRET=xxx
AE_AS_FACEBOOK_KEY=xxx
AE_AS_FACEBOOK_SECRET=xxx
```

Used by SimpleSAMLphp config

```
AE_SAML_ADMINPASSWORD=xxx
AE_SAML_SECRETSALT=xxx
AE_SAML_TECHNICALCONTACT_NAME=UNINETT AS
AE_SAML_TECHNICALCONTACT_EMAIL=kontakt@uninett.no
FC_CASSANDRA_SESSION_KEYSPACE=sessionstore
FC_CASSANDRA_SESSION_USESSL=false
```

To run development version on http (insecure):

```
AE_SERVER_NAME=localhost:8080
AE_DEBUG=true
HTTPS_ON=off
HTTPS_PROTO=http
```


## Old setup instructions...

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

## For Developers

### Managing translations

To update main dictionary content, upload `dictionaries/dictionary.en.json` to transifex.

To download translations run:

```
grunt lang
```

### Run test suite with ant

```
ant
```

### Run phpunit

```
./vendor/bin/phpunit
```
