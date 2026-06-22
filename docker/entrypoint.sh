#!/usr/bin/env sh
# ---------------------------------------------------------------------------
# HR PRO container startup: prepare app, migrate, seed once, serve.
# Seeding always creates roles/permissions + one admin account; the full demo
# dataset is loaded only when SEED_DEMO=true (see DatabaseSeeder).
# ---------------------------------------------------------------------------
set -e
cd /var/www/html

# Ensure an .env exists (env vars from the host still take precedence).
[ -f .env ] || cp .env.example .env

# Generate an application key if the host didn't supply APP_KEY.
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# SQLite database file + writable runtime directories.
mkdir -p database \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs
[ -f database/database.sqlite ] || touch database/database.sqlite

# Schema + seed (seed only once per container filesystem).
php artisan migrate --force
if [ ! -f storage/.seeded ]; then
    if php artisan db:seed --force; then
        touch storage/.seeded
        echo "HR PRO: database seeded successfully (SEED_DEMO=${SEED_DEMO:-false})."
    else
        echo "HR PRO: WARNING — seeding failed (see errors above); the app will still start."
    fi
fi

php artisan storage:link || true

# Cache config/routes/views for performance (key already generated above).
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Bind to the port the platform provides (Render/Koyeb/Fly set $PORT).
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
