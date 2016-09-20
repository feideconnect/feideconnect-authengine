#!/bin/bash
set -e
cd "$(dirname "${BASH_SOURCE[0]}")"

docker-compose up -d
function clean-docker() {
    docker-compose kill
    docker-compose rm --force --all
}
trap clean-docker EXIT

CASSANDRA_PORT=$(docker-compose port cassandra 9042 | sed 's@.*:@@')
CASSANDRA="localhost:${CASSANDRA_PORT}"

echo "Cassandra available on ${CASSANDRA}"
echo "Waiting schema setup to complete"
while ! docker-compose ps 2>/dev/null | grep -q 'dataportenschemas.*Exit'; do
    sleep 0.1
done
echo "Done"

if [ ! -d simplesamlphp ]; then
    curl -sSL https://simplesamlphp.org/res/downloads/simplesamlphp-1.14.0.tar.gz | tar xz
    mv simplesamlphp-1.14.0 simplesamlphp
fi

mkdir -p etc/test
sed "s/@@CASSANDRA@@/${CASSANDRA}/" <test-config/auth-engine-config.json >etc/test/config.json
cp test-config/jwt-*.pem etc

rm unit-test.log
touch unit-test.log
ant
