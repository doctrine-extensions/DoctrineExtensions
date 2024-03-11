# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.1-cli

FROM composer:2 AS composer

FROM php:$PHP_VERSION AS php

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -qq --yes --no-install-recommends git libzip-dev unzip zip zlib1g-dev \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install PHP extensions
RUN docker-php-ext-install zip pcntl bcmath \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb
