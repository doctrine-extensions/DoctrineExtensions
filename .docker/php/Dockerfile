# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4-cli

FROM composer:2 AS composer

FROM php:$PHP_VERSION AS php

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get satisfy -qq --yes --no-install-recommends \
    "git (>= 1:2.30.2), git (<< 1:3), \
    libzip-dev (>= 1.7.3), libzip-dev (<< 2), \
    unzip (>= 6), unzip (<< 7), \
    zip (>= 3), zip (<< 4), \
    zlib1g-dev (>= 1:1.2.11.dfsg), zlib1g-dev (<< 1:2)" \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    # Install PHP extensions
    && docker-php-ext-install zip \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install bcmath \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug
