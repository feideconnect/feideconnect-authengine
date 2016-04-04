# Building docker images for Auth engine




## Preparations before build


Load `var/fonts` and `var/GeoIP2-City.mmdb`.

SimpleSAMLphp certs

	mkdir -p var/simplesamlphp-certs
	cd var/simplesamlphp-certs
	openssl req -new -x509 -days 3652 -nodes -out saml.crt -keyout saml.pem

HTTPS certs

	mkdir -p var/web-certs
	cd var/web-certs


	openssl genrsa -des3 -passout pass:x -out server.pass.key 2048
	openssl rsa -passin pass:x -in server.pass.key -out server.key
	openssl req -new -key server.key -out server.csr
	openssl x509 -req -days 999 -in server.csr -signkey server.key -out server.crt

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

	docker run -p 80:80 -d --name dae --env-file=./ENV -t andreassolberg/dataporten-authengine

Debug container

	docker exec -i -t dae bash


## Accessing the instance

Add to your `/etc/hosts`

	192.168.99.100 auth.dataporten.no 

Access in your browser:

	https://auth.dataporten.no/auth

Make sure you accept the self signed certificate.



## Running Docker for Development

Mount source code from local computer.

