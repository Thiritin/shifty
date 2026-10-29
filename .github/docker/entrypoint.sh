#!/bin/sh

# The app shows a setup page while the database is unreachable, so a failed migration must not stop the container.
php artisan migrate --force || echo "Migrations skipped: the database is not reachable yet."
php artisan shifty:setup-token || true

exec "$@"
