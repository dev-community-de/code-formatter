# Dockerfile for production

FROM php:7.4-apache

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY . /srv/app

WORKDIR /srv/app

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

RUN mkdir -p /srv/app/storage/code
RUN chmod -R ug+rwx /srv/app/storage/code && chown -R www-data:www-data /srv/app && a2enmod rewrite
RUN chmod +x /srv/app/prettierd.js

# Packages for formatting C and C++ code
RUN apt-get update -y && \
    apt-get install -y clang-format

# Packages for formatting Go code
RUN apt-get update -y && \
    apt-get install -y golang

# Packages for formatting Python code
RUN apt-get update -y && \
    apt-get install -y python3.7-minimal python3-pip && \
    pip3 install black

# Packages for formatting code using Prettier (JS, CSS, PHP, etc)
RUN apt-get update -y && \
    curl -sL https://deb.nodesource.com/setup_14.x | bash - && \
    apt-get install -y git nodejs && npm install

# Install prettier-daemon
COPY prettierd-server.sh /usr/local/bin/prettierd-server
RUN chmod +x /usr/local/bin/prettierd-server

# Install custom entrypoint
COPY entrypoint.sh /usr/local/bin/cf-entrypoint
RUN chmod +x /usr/local/bin/cf-entrypoint

ENTRYPOINT ["cf-entrypoint"]

EXPOSE 8080
