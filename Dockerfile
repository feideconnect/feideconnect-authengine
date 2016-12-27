FROM uninett-docker-uninett.bintray.io/jessie/base

# Install packages
RUN apt-get update && apt-get install -y --no-install-recommends \
  apache2 \
  ca-certificates \
  curl \
  cmake \
  g++ \
  git \
  libgmp-dev \
  libpcre3-dev \
  libssl-dev \
  libuv-dev \
  locales \
  make \
  php5 \
  php5-cli \
  php5-curl \
  php5-dev \
  php5-gmp \
  php5-imagick \
  php5-mcrypt
# && rm -rf /var/lib/apt/lists/*

# Setup locales
RUN sh -c 'echo "en_US.UTF-8 UTF-8" > /etc/locale.gen'
RUN locale-gen
ENV LC_ALL=en_US.UTF-8

RUN curl -sL https://deb.nodesource.com/setup_6.x | sh
RUN apt-get install -y nodejs
RUN curl -sS https://www.npmjs.com/install.sh | sh


#COPY known_hosts /etc/ssh/ssh_known_hosts

RUN git clone https://github.com/datastax/php-driver.git /tmp/php-driver && \
  cd /tmp/php-driver && \
  git checkout v1.2.2 && \
  git submodule update --init && \
  cd ext && \
  ./install.sh && \
  cd / && \
  rm -rf /tmp/php-driver && \
  echo 'extension=cassandra.so' >/etc/php5/apache2/conf.d/cassandra.ini

# Start installing the app...
RUN mkdir -p /authengine
WORKDIR /authengine
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
COPY ./composer.json .
RUN composer update --no-interaction --no-dev --no-progress


COPY package.json .
RUN npm install

COPY bower.json .
RUN node_modules/bower/bin/bower install --allow-root
# COPY fonts /feideconnect/feideconnect-authengine/www/static/components/uninett-bootstrap-theme/fonts

COPY www www

# Warning: Do not use these fonts unless you have a licence on your site.
RUN curl -o /authengine/www/static/components/uninett-bootstrap-theme/fonts/colfaxLight.woff http://mal.uninett.no/uninett-theme/fonts/colfaxLight.woff
RUN curl -o /authengine/www/static/components/uninett-bootstrap-theme/fonts/colfaxMedium.woff http://mal.uninett.no/uninett-theme/fonts/colfaxMedium.woff
RUN curl -o /authengine/www/static/components/uninett-bootstrap-theme/fonts/colfaxRegular.woff http://mal.uninett.no/uninett-theme/fonts/colfaxRegular.woff
RUN curl -o /authengine/www/static/components/uninett-bootstrap-theme/fonts/colfaxThin.woff http://mal.uninett.no/uninett-theme/fonts/colfaxThin.woff
RUN curl -o /authengine/www/static/components/uninett-bootstrap-theme/fonts/colfaxRegularItalic.woff http://mal.uninett.no/uninett-theme/fonts/colfaxRegularItalic.woff

# === Copy auth engine ===
COPY lib lib
COPY etc/authengine etc
COPY dictionaries dictionaries
COPY templates templates
COPY etc/simplesamlphp-config /authengine/vendor/simplesamlphp/simplesamlphp/config
COPY etc/simplesamlphp-metadata /authengine/vendor/simplesamlphp/simplesamlphp/metadata
# === === ===

RUN curl -o /authengine/etc/GeoLite2-City.mmdb.gz http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz
RUN gunzip /authengine/etc/GeoLite2-City.mmdb.gz
ENV AE_GEODB "etc/GeoLite2-City.mmdb"

# Configuration files...
# RUN ln -sf /conf/config.json /conf/disco.json /conf/cert/jwt-cert.pem /conf/cert/jwt-key.pem /conf/disco2.json /conf/GeoIP2-City.mmdb etc
# RUN composer require --ignore-platform-reqs --update-no-dev --no-interaction --no-progress feideconnect/simplesamlphp-module-cassandrastore dev-master
# RUN touch ./modules/authtwitter/enable ./modules/oauth/enable ./modules/authfacebook/enable ./modules/authlinkedin/enable

RUN mkdir -p /var/log/simplesamlphp
RUN touch /var/log/simplesamlphp/simplesamlphp.log
RUN chown www-data /var/log/simplesamlphp/simplesamlphp.log

ENV HTTPS_ON "on"
ENV HTTPS_PROTO "https"
ENV AE_DEBUG "false"

# Setup apache
COPY etc/apache/apache.conf /etc/apache2/apache2.conf
COPY etc/apache/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
ENV APACHE_LOCK_DIR "/var/"
RUN a2enmod remoteip
RUN rm /etc/localtime && ln -s /usr/share/zoneinfo/Europe/Oslo /etc/localtime
RUN curl -sS https://vault.uninett.no:8200/v1/dpcassandra/ca/pem > /etc/ssl/certs/cassandraca.pem && c_rehash
CMD ["/usr/sbin/apache2", "-D", "FOREGROUND"]

VOLUME /var/log
EXPOSE 80
ARG JENKINS_BUILD_NUMBER
ENV JENKINS_BUILD_NUMBER ${JENKINS_BUILD_NUMBER}
LABEL no.uninett.dataporten.jenkins_build="${JENKINS_BUILD_NUMBER}"
