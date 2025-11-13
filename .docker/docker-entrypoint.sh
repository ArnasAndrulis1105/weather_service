#!/bin/sh
set -e


mkdir -p var/cache var/log var/sessions
chown -R www-data:www-data var || true


php bin/console doctrine:database:create --if-not-exists || true
php bin/console doctrine:migrations:migrate -n || true


exec "$@"
