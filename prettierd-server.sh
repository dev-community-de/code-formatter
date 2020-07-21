#!/usr/bin/env sh
while :
do
  start-stop-daemon                 \
    --group www-data                \
    --chuid www-data                \
    --chdir /srv/app                \
    --exec /srv/app/prettierd.js    \
    --start
done
