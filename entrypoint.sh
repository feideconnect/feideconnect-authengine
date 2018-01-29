#! /bin/sh
set -e
htpasswd -bc /etc/statuspasswd status "${FC_STATUS_TOKEN}"
if [ "$NODE_ENV" = "production" ]; then
    npm run build
fi
exec "$@"
