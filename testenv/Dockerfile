FROM debian:jessie
RUN apt-get update && apt-get install -y --no-install-recommends \
  ant \
  ca-certificates \
  cmake \
  g++ \
  git \
  libgmp-dev \
  libpcre3-dev \
  libssl-dev \
  libuv-dev \
  make \
  php5-cli \
  php5-dev \
  && rm -rf /var/lib/apt/lists/*
RUN git clone https://github.com/datastax/php-driver.git /tmp/php-driver && \
  cd /tmp/php-driver && \
  git checkout v1.2.2 && \
  git submodule update --init && \
  cd ext && \
  ./install.sh && \
  cd / && \
  rm -rf /tmp/php-driver && \
  echo 'extension=cassandra.so' >/etc/php5/cli/conf.d/cassandra.ini
VOLUME /authengine
WORKDIR /authengine
CMD [ "/bin/true" ]
