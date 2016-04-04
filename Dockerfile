FROM uninett-docker-uninett.bintray.io/jessie/base
RUN apt-get update
# Setup locales
RUN sh -c 'echo "en_US.UTF-8 UTF-8" > /etc/locale.gen'
RUN RUNLEVEL=1 DEBIAN_FRONTEND=noninteractive apt-get install -y locales
RUN locale-gen
ENV LC_ALL=en_US.UTF-8

# Install base os stuff
RUN RUNLEVEL=1 DEBIAN_FRONTEND=noninteractive apt-get install -y ca-certificates
RUN RUNLEVEL=1 DEBIAN_FRONTEND=noninteractive apt-get install -y curl git

# Apache and php
RUN RUNLEVEL=1 DEBIAN_FRONTEND=noninteractive apt-get install -y apache2 php5 php5-cli php5-mcrypt php5-imagick php5-curl php5-gmp php5-sqlite
RUN a2enmod ssl

# Nodejs and npm
RUN RUNLEVEL=1 DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs nodejs-legacy
RUN curl https://www.npmjs.com/install.sh | sh

# ADD known_hosts /etc/ssh/ssh_known_hosts

# Start installing the app...
RUN mkdir -p /dataporten
WORKDIR /dataporten
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# Install simplesamlphp
RUN curl https://simplesamlphp.org/res/downloads/simplesamlphp-1.14.0.tar.gz |tar zx
RUN ln -s simplesamlphp-1.14.0 simplesamlphp

# Setup Simplesamlphp
WORKDIR /dataporten/simplesamlphp
RUN composer update
RUN composer remove simplesamlphp/simplesamlphp-module-infocard 
RUN composer remove simplesamlphp/simplesamlphp-module-aggregator2
RUN composer remove simplesamlphp/simplesamlphp-module-modinfo
RUN composer remove simplesamlphp/simplesamlphp-module-openidprovider
RUN composer remove rediris-es/simplesamlphp-module-papi
RUN composer require --no-interaction --no-progress simplesamlphp/simplesamlphp-module-openid "^1.0"
RUN composer require --no-interaction --no-progress duoshuo/php-cassandra dev-master#c9485df6d6ed797bd472d6bea5370b486d1edcb0
RUN composer require --no-interaction --no-progress feideconnect/simplesamlphp-module-cassandrastore dev-master
RUN mkdir -p /tmp/simplesaml
RUN rm -rf config metadata
RUN touch ./modules/openid/enable ./modules/authtwitter/enable ./modules/oauth/enable ./modules/authfacebook/enable ./modules/authlinkedin/enable
RUN mkdir -p /var/log/simplesamlphp
RUN touch /var/log/simplesamlphp/simplesamlphp.log
RUN chown www-data /var/log/simplesamlphp/simplesamlphp.log
ADD etc/simplesamlphp-config config
ADD etc/simplesamlphp-metadata metadata
ADD var/simplesamlphp-certs cert

# Install Auth engine
#WORKDIR /dataporten


# Setup Auth engine
RUN mkdir -p /dataporten/feideconnect-authengine
WORKDIR /dataporten/feideconnect-authengine

ADD composer.json /dataporten/feideconnect-authengine
RUN composer update --no-interaction --no-dev --no-progress

ADD package.json /dataporten/feideconnect-authengine
RUN npm install

ADD bower.json /dataporten/feideconnect-authengine
ADD .bowerrc /dataporten/feideconnect-authengine
RUN node_modules/bower/bin/bower install --allow-root

ADD . /dataporten/feideconnect-authengine

ADD var/GeoIP2-City.mmdb etc/GeoIP2-City.mmdb
ADD var/fonts /dataporten/feideconnect-authengine/www/static/components/uninett-bootstrap-theme/fonts
# RUN ln -s /conf/config.json /conf/disco.json /conf/cert/jwt-cert.pem /conf/cert/jwt-key.pem /conf/disco2.json /conf/GeoIP2-City.mmdb etc


# Setup apache
RUN rm /etc/apache2/sites-enabled/000-default.conf /etc/apache2/conf-enabled/other-vhosts-access-log.conf
ADD etc/apache/site-ssl.conf /etc/apache2/sites-enabled/dataporten.conf
ADD etc/apache/apache.conf /etc/apache2/apache2.conf
ADD var/web-certs /etc/apache2/certs
RUN a2enmod remoteip
RUN rm /etc/localtime && ln -s /usr/share/zoneinfo/Europe/Oslo /etc/localtime

#RUN curl -sS https://vault.uninett.no:8200/v1/dpcassandra/ca/pem > /etc/ssl/certs/cassandraca.pem && c_rehash

CMD /usr/sbin/apache2ctl -D FOREGROUND
#VOLUME /var/log
EXPOSE 80
EXPOSE 443
#ARG JENKINS_BUILD_NUMBER
#ENV JENKINS_BUILD_NUMBER ${JENKINS_BUILD_NUMBER}
#LABEL no.uninett.dataporten.jenkins_build="${JENKINS_BUILD_NUMBER}"
