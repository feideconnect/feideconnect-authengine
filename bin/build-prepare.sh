#! /bin/bash

echo "Load resources"
git clone git@scm.uninett.no:feide-connect/dataporten-resources.git

echo "Load config"
git clone git@scm.uninett.no:feide-connect/dataporten-config.git


mkdir var
cp -r dataporten-config/authengine-dev/simplesamlphp-certs var/
cp -r dataporten-config/authengine-dev/web-certs var/

cp -r dataporten-config/authengine-dev/simplesamlphp-config etc/
cp -r dataporten-config/authengine-dev/simplesamlphp-metadata etc/