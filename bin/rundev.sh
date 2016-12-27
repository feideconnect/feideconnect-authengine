#! /bin/bash

# Run metadata-import linked to the docker-compose cassandra instance.
export RUN_PATH=`dirname "$0" || echo .`
set -a
. ${RUN_PATH}/_config.sh
set +a

docker stop ${KUBERNETES_DEPLOYMENT}
docker rm ${KUBERNETES_DEPLOYMENT}
docker run -d --name ${KUBERNETES_DEPLOYMENT} \
    -p 8080:80 \
    --link devenv_cassandra_1:cassandra \
    --net devenv_default \
    -v ${PWD}/www:/authengine/www \
    -v ${PWD}/lib:/authengine/lib \
    --env-file ENV ${IMAGE}
docker logs -f ${KUBERNETES_DEPLOYMENT}


# -v ${PWD}/etc/simplesamlphp-config:/feide/vendor/simplesamlphp/simplesamlphp/config \
# -v ${PWD}/etc/simplesamlphp-metadata:/feide/vendor/simplesamlphp/simplesamlphp/metadata \
#     -v ${PWD}/metadata-import/getmetadata.php:/metadata-import/getmetadata.php \
