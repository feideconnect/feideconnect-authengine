# Dataporten Auth Engine

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
kubectl --namespace dataporten delete job cassandra-schema-session
kubectl --namespace dataporten apply -f etc/kubernetes/job-schema.yaml
kubectl --namespace dataporten exec -i dataporten-cassandra-3112224070-njkaq cqlsh < ../metadata-import/etc/init.cql
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


## Twig

If you want to use the Twig templating system instead of Mustache, just set the configuration accordingly in
`etc/authengine/config.json`:

```
    "twig": {
        "use": true,
        "cacheDir": "/tmp",
        "autoReload": true
    },
```

* `use`: set to `true` to use Twig instead of Mustache. Set to `false` to disable it.
* `cacheDir`: the path to the directory where Twig should compile and keep a cache of templates.
* `autoReload`: whether Twig should reload templates automatically when they are modified, or not.


## DUST templates

http://www.dustjs.com/guides/rendering/

The last part is about precompiling. To precompile the templates:

TODO: Separate templates for accountchooser and oauth

```
./node_modules/dustjs-linkedin/bin/dustc --amd templates/*.dust > www/static/accountchooser/templates.js
```

## SCSS

[The difference between SASS and SCSS](http://sass-lang.com/documentation/file.SASS_REFERENCE.html#Syntax) - it's two different syntaxes where SCSS is an extension of CSS and SASS is it's own beast.


For developing with SCSS on Linux:
```
sudo apt-get install ruby-dev
sudo gem install sass --no-user-install
sass -v  # Test if works

# For automatically compiling to css when scss-files are changed:
# sass --watch <src>:<target>
sass --watch css/src:css/target
```

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
etc/scopedef.json -
```

SimpleSAMLphp configuration

```
etc/simplesamlphp-config
etc/simplesamlphp-metadata
```

Mount these additional configuration files:

```
/conf/saml.pem
/conf/saml.crt
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


Used by auth engine

```
CASSANDRA_USERNAME=dataporten
CASSANDRA_PASSWORD=xxx
AE_DEBUG=true
AE_SALT=xxx
FC_ENDPOINT_GROUPS=https://groups-api.dataporten.no
FC_ENDPOINT_CORE=https://api.dataporten.no
AE_TESTUSERSFILE=testusers.json
FEIDE_IDP=https://idp.feide.no
```

`AE_TESTUSERSFILE` points to a json file in `etc` that contains test users that will override config content.


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

## Running tests

There is a shell script that simplifies automation of running tests. Used in CI.

```
./runtests.sh
```

If you would like to setup the test environment more manually, you can run:

```
docker-compose up -d cassandra
# Wait some time, until cassandra is up.
docker-compose run dataportenschemas
docker-compose run metadataschemas
docker-compose run testenv ant
```

For testing a specific unit test, you can run:

```
docker-compose run testenv vendor/phpunit/phpunit/phpunit tests/ConfigTest
```


## Managing translations

To update main dictionary content, upload `dictionaries/dictionary.en.json` to transifex.

To download translations run:

```
grunt lang
```


## Optimizing js

```
cd www/static
../../node_modules/requirejs/bin/r.js -o baseUrl=. name=components/almond/almond  mainConfigFile=js/src/requireconfig.js include=accountchooser/main.js out=build/accountchooser_bundle.js

```
