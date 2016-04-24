#! /bin/bash

echo "Stopping existing image"
docker stop dae && docker rm dae

echo "Building new image"
docker build -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine .

echo "Running container"
docker run -p 80:80 -p 443:443 -d --name dae --env-file=./ENV -t eu.gcr.io/turnkey-cocoa-720/dataporten-authengine

echo "Opening shell"
docker exec -i -t dae bash

