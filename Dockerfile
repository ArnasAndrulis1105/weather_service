# syntax=docker/dockerfile:1.6


ARG PHP_VERSION=8.4
ARG APP_DIR=/var/www/html


############################
# Composer (builder)
############################
FROM composer:2 AS composer_downloader


############################
# PHP base (extensions)
############################
FROM php:${PHP_VERSION}-fpm-alpine AS php_base


RUN set -eux; \
apk add --no-cache bash git icu-dev libpq-dev unzip libzip-dev oniguruma-dev; \
docker-php-ext-configure intl; \
docker-php-ext-install -j$(nproc) intl opcache pdo pdo_pgsql; \
rm -rf /var/cache/apk/*


COPY --from=composer_downloader /usr/bin/composer /usr/bin/composer


WORKDIR ${APP_DIR}


############################
# Dependencies (dev or prod)
############################
FROM php_base AS app


COPY .docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini


COPY composer.json composer.lock* symfony.lock* ./
RUN set -eux; \
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-interaction --prefer-dist --no-progress; \
rm -rf /root/.cache/composer


COPY . .


RUN set -eux; \
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --no-progress; \
php bin/console cache:clear --no-warmup || true; \
php bin/console cache:warmup || true


EXPOSE 9000


ENTRYPOINT ["/bin/sh", "/var/www/html/.docker/docker-entrypoint.sh"]
CMD ["php-fpm"]
