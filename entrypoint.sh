#! /bin/sh
set -e
htpasswd -bc /etc/statuspasswd status "${FC_STATUS_TOKEN}"
exec "$@"
