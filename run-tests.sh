#!/bin/bash
set -e
cd "$(dirname "${BASH_SOURCE[0]}")"

docker-compose up -d cassandra
function clean-docker() {
    docker-compose kill
    docker-compose rm --force --all
}
if test -z "${NOCLEAN}"
then
    trap clean-docker EXIT
fi

CASSANDRA_PORT=$(docker-compose port cassandra 9042 | sed 's@.*:@@')
CASSANDRA="localhost:${CASSANDRA_PORT}"

echo "Cassandra available on ${CASSANDRA}"
echo "Running schema setup to complete"
docker-compose run dataportenschemas
echo "Done"

if [ ! -d simplesamlphp ]; then
    curl -sSL https://simplesamlphp.org/res/downloads/simplesamlphp-1.14.0.tar.gz | tar xz
    mv simplesamlphp-1.14.0 simplesamlphp
fi

mkdir -p etc/test
sed "s/@@CASSANDRA@@/${CASSANDRA}/" <test-config/auth-engine-config.json >etc/test/config.json
cp test-config/jwt-*.pem etc

rm -f unit-test.log
touch unit-test.log
ant
