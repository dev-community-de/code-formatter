FROM php:7.4-apache

COPY .docker/web/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /srv/app

RUN apt-get update -y && apt-get install -y clang-format
