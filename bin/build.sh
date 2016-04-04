#! /bin/bash

echo "Stopping existing image"
docker stop dae && docker rm dae

echo "Building new image"
docker build -t andreassolberg/dataporten-authengine .

echo "Running container"
docker run -p 80:80 -p 443:443 -d --name dae --env-file=./ENV -t andreassolberg/dataporten-authengine

echo "Opening shell"
docker exec -i -t dae bash

