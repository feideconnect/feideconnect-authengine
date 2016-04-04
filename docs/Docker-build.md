# Building docker images for Auth engine




## Preparations before build


Load `var/fonts` and `var/GeoIP2-City.mmdb`.

SimpleSAMLphp certs

	mkdir -p var/simplesamlphp-certs
	cd var/simplesamlphp-certs
	openssl req -new -x509 -days 3652 -nodes -out saml.crt -keyout saml.pem


## Building


To build a new docker image:

	docker stop dae && docker rm dae
	docker build -t andreassolberg/dataporten-authengine .


## Running Docker image

Prepare an ENV file:

	APACHE_LOCK_DIR=/var/
	APACHE_PID_FILE=/var/pid/apache
	APACHE_RUN_USER=www-data
	APACHE_RUN_GROUP=www-data
	AE_SERVER_NAME=auth.dataporten.no
	SERVER_ADMIN=kontakt@uninett.no
	AE_AS_TWITTER_KEY=xxx
	AE_AS_TWITTER_SECRET=xxx
	AE_AS_LINKEDIN_KEY=xxx
	AE_AS_LINKEDIN_SECRET=xxx
	AE_AS_FACEBOOK_KEY=xxx
	AE_AS_FACEBOOK_SECRET=xxx
	AE_SALT=73d9c77b-951f-4a29-9113-3e72405cfa1a
	FC_ENDPOINT_GROUPS=https://groups-api.dataporten.no
	FC_ENDPOINT_CORE=https://api.dataporten.no
	AE_SAML_ADMINPASSWORD=1234
	AE_SAML_SECRETSALT=xxxx
	AE_SAML_TECHNICALCONTACT_NAME=UNINETT AS
	AE_SAML_TECHNICALCONTACT_EMAIL=kontakt@uninett.no
	FC_CASSANDRA_CONTACTPOINTS=xxx, xxx, xxx, xxx
	FC_CASSANDRA_KEYSPACE=feideconnect
	FC_CASSANDRA_USESSL=false
	FC_CASSANDRA_SESSION_KEYSPACE=sessionstore
	FC_CASSANDRA_SESSION_USESSL=false
	CASSANDRA_USERNAME=dataporten
	CASSANDRA_PASSWORD=xxx
	DEFAULT_IDP=https://idp-test.feide.no

Then run container:

	docker run -p 80:80 --name dae --env-file=./ENV -t andreassolberg/dataporten-authengine


## Running Docker for Development

Mount source code from local computer.

