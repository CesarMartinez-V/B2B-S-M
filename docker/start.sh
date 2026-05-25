#!/bin/sh
set -eu

mkdir -p \
  storage/app \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chmod -R ug+rwX storage bootstrap/cache

php artisan config:cache

if ! php artisan route:cache; then
  php artisan route:clear
fi

php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
