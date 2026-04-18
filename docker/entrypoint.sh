#!/bin/sh
set -e

cd /app

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Ensure SQLite database exists
touch database/database.sqlite

# Run migrations
php artisan migrate --force

# Cache config, routes, views for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
chmod -R 775 storage bootstrap/cache database

exec "$@"
