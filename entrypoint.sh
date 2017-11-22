#! /bin/sh
set -e
ln -s /conf/cassandraca.pem /etc/ssl/certs/cassandraca.pem && c_rehash > /dev/null 2>&1
htpasswd -bc /etc/statuspasswd status "${FC_STATUS_TOKEN}"
exec "$@"
