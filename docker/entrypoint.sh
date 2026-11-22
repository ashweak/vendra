#!/bin/sh
set -eu
cd /var/www/html

# Use Render's public URL when APP_URL isn't set (used for links in notifications).
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
fi

# A demo container can run without a fixed key; sessions simply reset with the data.
if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
    echo "APP_KEY not set: generated a temporary key for this container."
fi

touch database/database.sqlite

if [ "${VENDRA_PUBLIC_DEMO:-false}" = "true" ]; then
    echo "Public demo mode: resetting the database with fictional data."
    php artisan migrate:fresh --seed --force
else
    php artisan migrate --force
fi

# Public URLs for uploaded pictures (public/storage -> storage/app/public).
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache database

# Scheduler (expiry + retention jobs) runs alongside Apache as the web user.
runuser -u www-data -- php artisan schedule:work &

exec apache2-foreground
