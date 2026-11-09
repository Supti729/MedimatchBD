#!/bin/sh
# Start-up script for the Railway container.
set -e
cd /app

# A Railway volume mounted on /app/storage starts empty, so recreate Laravel's folders.
mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
         storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
php artisan storage:link --force >/dev/null 2>&1 || true

# Cache config/routes/views using the environment variables Railway provides.
php artisan optimize

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    # The database can take a few seconds to accept connections after a deploy.
    tries=0
    until php artisan migrate --force; do
        tries=$((tries + 1))
        [ "$tries" -ge 10 ] && echo "Database not reachable, giving up." && exit 1
        echo "Waiting for the database ($tries/10)..."; sleep 3
    done
    # Reference data only (locations + medicine catalogue). Safe to run on every deploy.
    php artisan db:seed --force
fi

exec frankenphp php-server --listen ":${PORT:-8080}" --root public/
