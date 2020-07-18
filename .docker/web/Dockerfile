FROM php:7.4-apache

COPY .docker/web/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /srv/app

RUN mkdir -p /srv/app/storage/code
RUN chmod -R ug+rwx /srv/app/storage/code && chown -R www-data:www-data /srv/app && a2enmod rewrite

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

# Packages for formatting code using Prettier (JS, CSS, etc)
RUN apt-get update -y && \
    curl -sL https://deb.nodesource.com/setup_14.x | bash - && \
    apt-get install -y nodejs && npm install
