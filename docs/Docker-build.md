# Building docker images for Auth engine




## Preparations before build


Load licenced resources and config from separate repos:

	bin/build-prepare.sh


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

	docker build -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine .

## Publishing docker iamge

To publish to Google Cloud Private Registry:

	gcloud docker push eu.gcr.io/turnkey-cocoa-720/dataporten-authengine




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

	docker run -p 80:80 -p 443:443 -d --name dae --env-file=./ENV -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine

Clean up before running a new container image:

	docker stop dae && docker rm dae

Debug container

	docker exec -ti dae bash


## Accessing the instance

Add to your `/etc/hosts`

	192.168.99.100 auth.dataporten.no 

Access in your browser:

	
	https://auth.dataporten.no/auth

Or something like this to initiate a login flow:

	https://auth.dataporten.no/oauth/authorization?response_type=token&state=3a92bee8-0462-4c29-b607-ff6cbb3908d1&redirect_uri=https%3A%2F%2Fcal.uninett.no%2F&client_id=b3dc2e62-32a5-4bd4-886e-b9afc12650ed


Make sure you accept the self signed certificate.



## Running Docker for Development


Files not checked in to repo yet, needs to be copied from elsewhere:

* ENV
* etc/config.json
* etc/simplesamlphp-config
* etc/simplesamlphp-metadata
* var/


Mount source code from local computer.


	docker run -p 80:80 -p 443:443 -d --name dae -v "$PWD":/dataporten/feideconnect-authengine --env-file=./ENV -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine


Mount source code from master branch, located elsewhere (mounting the lib, templates and www folder only):

	export SRCF=/Users/andreas/wc/fc/feideconnect-authengine-master
	export DSTF=/dataporten/feideconnect-authengine

	docker run -p 80:80 -p 443:443 -d --name dae -v "$SRCF/dictionaries":"$DSTF/dictionaries" -v "$SRCF/lib":"$DSTF/lib" -v "$SRCF/templates":"$DSTF/templates" -v "$SRCF/vendor":"$DSTF/vendor" -v "$SRCF/www":"$DSTF/www" --env-file=./ENV -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine




## Running auth engine on Kubernetes - Google Container engine



	kubectl create -f etc/kubecfg/rc.json
	kubectl create -f etc/kubecfg/service.json

	
Specific for Google Container Engine, you will get output like this when creating the service:

	You have exposed your service on an external port on all nodes in your
	cluster.  If you want to expose this service to the external internet, you may
	need to set up firewall rules for the service port(s) (tcp:30762,tcp:32509) to serve traffic.


gcloud compute firewall-rules create dataporten-authengine-service-1 --allow=tcp:30762
gcloud compute firewall-rules create dataporten-authengine-service-2 --allow=tcp:32509





