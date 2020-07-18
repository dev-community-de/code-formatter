#!/usr/bin/env sh
nohup prettierd-server &
exec docker-php-entrypoint apache2-foreground
