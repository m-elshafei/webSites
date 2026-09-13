#!/bin/sh
set -e

# Only the app container prepares the filesystem; workers just start.
if [ -f artisan ]; then
    mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache

    # Best effort: on bind mounts the host owns these, and that is fine.
    chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true

    if [ "${APP_ENV}" = "production" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi

    if [ "${RUN_MIGRATIONS}" = "true" ]; then
        php artisan migrate --force --isolated
    fi
fi

exec "$@"
