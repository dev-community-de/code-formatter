# Dockerfile for production

FROM php:7.4-apache

# Packages for formatting C and C++ code
RUN apt-get update -y && \
    apt-get install -y clang-format

# Packages for formatting Go code
RUN apt-get update -y && \
    apt-get install -y golang

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY . /srv/app

WORKDIR /srv/app

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

RUN mkdir -p /srv/app/storage/code
RUN chmod -R ug+rwx /srv/app/storage/code && chown -R www-data:www-data /srv/app && a2enmod rewrite

EXPOSE 8080
