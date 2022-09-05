# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.1-cli

FROM composer:2 AS composer

FROM php:$PHP_VERSION AS php

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    git=1:2.30.2-1 \
    libzip-dev=1.7.3-1 \
    unzip=6.0-26+deb11u1 \
    zip=3.0-12 \
    zlib1g-dev=1:1.2.11.dfsg-2+deb11u2 \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    # Install PHP extensions
    && docker-php-ext-install zip \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install bcmath \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb
