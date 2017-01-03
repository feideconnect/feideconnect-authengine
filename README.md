# Feide Connect Auth Engine

[![Code Climate](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/gpa.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)
[![Test Coverage](https://codeclimate.com/github/feideconnect/feideconnect-authengine/badges/coverage.svg)](https://codeclimate.com/github/feideconnect/feideconnect-authengine)


## Kubernetes

```
bin/build.sh publish        # Build docker image and upload to private gce repository

kubectl create namespace dataporten
kubectl --namespace dataporten apply -f etc/kubernetes/secrets.yaml
kubectl --namespace dataporten apply -f etc/kubernetes/deployments.yaml
kubectl --namespace dataporten apply -f etc/kubernetes/service.yaml
kubectl --namespace dataporten apply -f etc/kubernetes/ingress.yaml
```


Initialize cassandra schema:

```
kubectl --namespace dataporten delete job cassandra-schema-main
kubectl --namespace dataporten apply -f etc/kubernetes/job-schema.yaml
kubectl --namespace dataporten exec -i dataporten-cassandra-3112224070-u98j9 cqlsh < ../metadata-import/etc/init.cql
```

Load eduGAIN metadata

```
kubectl --namespace dataporten delete job metadata-import
kubectl --namespace dataporten apply -f etc/kubernetes/job-metadata-import.yaml
```

### Useful commands

Access cassandra with CQLSH

Through local client
```
kubectl --namespace dataporten port-forward dataporten-cassandra-3112224070-u98j9 9042:9042
cqlsh
```

Directly on cassandra container:

```
kubectl --namespace dataporten exec -ti dataporten-cassandra-3112224070-u98j9 cqlsh
```

Inspect

```
kubectl --namespace dataporten get pods --show-all
kubectl --namespace dataporten get secrets
```


## Run for development

Run a local docker container with mounted directories for the content that you would like to work on.

```
bin/build.sh
bin/rundev.sh
```

Access the service on:

* <http://localhost:8080/auth>




## Docker


Build docker image

```
docker build -t dataporten-authengine .
```

Runnning docker image

```
docker run -p 8080:80 --env-file ENV dataporten-authengine
```


## Configuration

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
AE_SAML_TECHNICALCONTACT_EMAIL=kontakt@uninett.no
FC_CASSANDRA_CONTACTPOINTS=cassandra
FC_CASSANDRA_KEYSPACE=dataporten
FC_CASSANDRA_USESSL=false
```


Used by apache only

(optional)

```
APACHE_LOCK_DIR=/var/
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
