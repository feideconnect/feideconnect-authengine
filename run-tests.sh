#!/bin/bash
set -e
cd "$(dirname "${BASH_SOURCE[0]}")"

docker-compose up -d cassandra
# function clean-docker() {
#     docker-compose kill
#     docker-compose rm --force --all
# }
# if test -z "${NOCLEAN}"
# then
#     trap clean-docker EXIT
# fi

echo "Cassandra should now be available"
echo "Running schema setup to complete"
docker-compose run dataportenschemas
echo "- Done"

#mkdir -p etc/test
mkdir -p build/logs/
#chmod -R a+rwX build/logs
#touch build/logs/jdepend.xml

# sed "s/@@CASSANDRA@@/cassandra:9042/" <test-config/auth-engine-config.json >etc/test/config.json
# cp test-config/jwt-*.pem etc

rm -f build/unit-test.log
touch build/unit-test.log

# echo "Running docker-compose build testenv"
# docker-compose build testenv
# echo "- Done"

echo "Running docker-compose run testenv ant"
docker-compose run testenv ls -la /authengine
docker-compose run testenv ant
echo "- Done"
