#!/bin/sh
set -eu
cd /var/www/html
trap 'chown -R www-data:www-data storage bootstrap/cache' EXIT
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# Keep the initial password out of the migration process and its error output.
env -u WEBFX_INITIAL_PASSWORD php artisan migrate --force
php artisan webfx:bootstrap
