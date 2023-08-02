# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.2-cli

FROM composer:2 AS composer

FROM php:$PHP_VERSION AS php

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    git \
    libzip-dev \
    unzip \
    zip \
    zlib1g-dev \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    # Install PHP extensions
    && docker-php-ext-install zip \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install bcmath \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb
