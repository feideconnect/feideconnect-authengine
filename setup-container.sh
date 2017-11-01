#! /bin/sh
set -e
curl -sL https://deb.nodesource.com/setup_6.x | bash
apt-get install -y nodejs
npm install npm@latest -g
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer update --no-interaction --no-dev --no-progress
npm install
node_modules/bower/bin/bower install --allow-root

a2enmod remoteip
rm /etc/localtime && ln -s /usr/share/zoneinfo/Europe/Oslo /etc/localtime
curl -sS https://vault.uninett.no:8200/v1/dpcassandra/ca/pem > /etc/ssl/certs/cassandraca.pem && c_rehash

mkdir -p /authengine/etc
curl -sS http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz|gunzip > /authengine/etc/GeoLite2-City.mmdb
mkdir -p /var/log/simplesamlphp
touch /var/log/simplesamlphp/simplesamlphp.log
chown www-data /var/log/simplesamlphp/simplesamlphp.log
touch ./vendor/simplesamlphp/simplesamlphp/modules/authtwitter/enable \
      ./vendor/simplesamlphp/simplesamlphp/modules/oauth/enable \
      ./vendor/simplesamlphp/simplesamlphp/modules/authfacebook/enable \
      ./vendor/simplesamlphp/simplesamlphp/modules/authlinkedin/enable

# Bundle javascript
bin/build.sh

# Cleanup
apt-get remove -y --purge --auto-remove nodejs lsb-release
rm -rf node_modules
rm -rf /root/.npm /root/.cache /root/.composer
