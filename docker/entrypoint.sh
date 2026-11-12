#!/bin/sh
set -e
cd /var/www/html

# APP_KEY: generate once and keep it in storage (a volume-backed folder survives rebuilds).
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    [ -f storage/app/.app_key ] || php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/.app_key
    export APP_KEY="$(cat storage/app/.app_key)"
fi

php artisan migrate --force          # no-op when schema.sql already created everything
php artisan storage:link 2>/dev/null || true
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
