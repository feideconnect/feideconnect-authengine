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

mkdir -p build/logs/

rm -f build/unit-test.log
touch build/unit-test.log

echo "Running docker-compose build testenv"
docker-compose build testenv
echo "- Done"

echo "Cassandra should now be available"
echo "Running schema setup to complete"
docker-compose run dataportenschemas
docker-compose run metadataschemas
echo "- Done"

echo "Running docker-compose run testenv ant"
docker-compose run testenv ant
echo "- Done"
