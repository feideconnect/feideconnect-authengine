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

mkdir -p etc/test
mkdir -p build/logs/
touch build/logs/jdepend.xml

#sed "s/@@CASSANDRA@@/cassandra:9042/" <test-config/auth-engine-config.json >etc/test/config.json
cp test-config/jwt-*.pem etc

rm -f unit-test.log
touch unit-test.log
docker-compose run testenv ant
