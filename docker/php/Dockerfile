# syntax=docker/dockerfile:1.7
# -----------------------------------------------------------------------------
# Laravel 13 / PHP 8.5 / php-fpm-alpine
# Targets: dev (bind-mounted source)  |  prod (self-contained image)
# -----------------------------------------------------------------------------
ARG PHP_VERSION=8.5
ARG NODE_VERSION=24

# ------------------------------- base ----------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    PHP_INI_DIR=/usr/local/etc/php

# mlocati/docker-php-extension-installer resolves every build/runtime lib itself
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        redis \
        sockets \
        zip \
    && apk add --no-cache tini fcgi mariadb-client \
    && rm -rf /var/cache/apk/*

# Run as a non-root user whose ids match the host, so bind-mounted files
# keep sane ownership. Override with --build-arg UID=$(id -u).
RUN deluser --remove-home www-data 2>/dev/null || true; \
    addgroup -g ${GID} app && adduser -u ${UID} -G app -s /bin/sh -D app

COPY docker/php/conf.d/ ${PHP_INI_DIR}/conf.d/
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["/sbin/tini", "--", "entrypoint"]
CMD ["php-fpm"]

HEALTHCHECK --interval=15s --timeout=3s --start-period=20s \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 >/dev/null 2>&1 || exit 1

# ------------------------------- dev -----------------------------------------
FROM base AS dev

ENV XDEBUG_MODE=off

RUN install-php-extensions xdebug \
    && apk add --no-cache git openssh-client bash

COPY docker/php/xdebug.ini ${PHP_INI_DIR}/conf.d/99-xdebug.ini
RUN sed -i 's/^opcache.validate_timestamps=.*/opcache.validate_timestamps=1/' ${PHP_INI_DIR}/conf.d/10-opcache.ini

USER app

# ------------------------------ vendor ---------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer composer install \
        --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ------------------------------ assets ---------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json* ./
RUN --mount=type=cache,target=/root/.npm \
    if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY . .
RUN npm run build

# ------------------------------- prod ----------------------------------------
FROM base AS prod

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --chown=app:app --from=vendor /var/www/html ./
COPY --chown=app:app --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R app:app storage bootstrap/cache

USER app
