# Running Auth Engine with Docker - Quickstart



## Prepareations

	git clone -b topic-dockerfriendly git@github.com:feideconnect/feideconnect-authengine.git
	cd feideconnect-authengine
	bin/build-prepare.sh


## Build

	docker build -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine .

## Configure

Edit `dataporten-config/ENV`. Minimum is to setup cassandra endpoints.

Setup `/etc/hosts/` with something like:

	192.168.99.100 auth.dataporten.no


## Run

	docker run -p 80:80 -p 443:443 -d --name dae --env-file=dataporten-config/authengine-dev/ENV -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine







