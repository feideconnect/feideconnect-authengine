FROM uninett-docker-uninett.bintray.io/jessie/minbase

# Install packages
RUN install_packages.sh \
  apache2 \
  ca-certificates \
  locales \
  php5 \
  php5-cli \
  php5-curl \
  php5-gmp \
  php5-imagick \
  php5-mcrypt

# Setup locales
RUN sh -c 'echo "en_US.UTF-8 UTF-8" > /etc/locale.gen'
RUN locale-gen
ENV LC_ALL=en_US.UTF-8

# Warning: Do not use these fonts unless you have a licence on your site.
ADD ["http://mal.uninett.no/uninett-theme/fonts/colfaxLight.woff", "http://mal.uninett.no/uninett-theme/fonts/colfaxMedium.woff", "http://mal.uninett.no/uninett-theme/fonts/colfaxRegular.woff", "http://mal.uninett.no/uninett-theme/fonts/colfaxThin.woff", "http://mal.uninett.no/uninett-theme/fonts/colfaxRegularItalic.woff", " /authengine/www/static/components/uninett-bootstrap-theme/fonts/"]

RUN with_packages.sh "php5-dev cmake g++ git libgmp-dev libpcre3-dev libssl-dev libuv-dev make" "git clone https://github.com/datastax/php-driver.git /tmp/php-driver && \
  cd /tmp/php-driver && \
  git checkout v1.2.2 && \
  git submodule update --init && \
  cd ext && \
  ./install.sh && \
  cd / && \
  rm -rf /tmp/php-driver && \
  echo 'extension=cassandra.so' >/etc/php5/apache2/conf.d/cassandra.ini  && \
  echo 'extension=cassandra.so' >/etc/php5/cli/conf.d/cassandra.ini"

# Start installing the app...
RUN mkdir -p /authengine
WORKDIR /authengine

COPY ["composer.json", "package.json", "bower.json", ".bowerrc", "setup-container.sh", "./"]
RUN with_packages.sh "git curl" ./setup-container.sh
COPY www www

# === Copy auth engine ===
COPY lib lib
COPY etc/authengine etc
COPY dictionaries dictionaries
COPY templates templates
COPY etc/simplesamlphp-config /authengine/vendor/simplesamlphp/simplesamlphp/config
COPY etc/simplesamlphp-metadata /authengine/vendor/simplesamlphp/simplesamlphp/metadata

ENV AE_GEODB "etc/GeoLite2-City.mmdb"
ENV HTTPS_ON "on"
ENV HTTPS_PROTO "https"
ENV AE_DEBUG "false"

# Setup apache
COPY etc/apache/apache.conf /etc/apache2/apache2.conf
COPY etc/apache/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
ENV APACHE_LOCK_DIR "/var/"
COPY entrypoint.sh /
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/sbin/apache2", "-D", "FOREGROUND"]

VOLUME /var/log
EXPOSE 80
ARG JENKINS_BUILD_NUMBER
ENV JENKINS_BUILD_NUMBER ${JENKINS_BUILD_NUMBER}
LABEL no.uninett.dataporten.jenkins_build="${JENKINS_BUILD_NUMBER}"
