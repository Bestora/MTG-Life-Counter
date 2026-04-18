#!/bin/sh
set -e

cd /app

# Ensure storage directories exist (volume mount may be empty on first run)
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/testing \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Ensure SQLite database exists
mkdir -p database
touch database/database.sqlite

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

# Create .env if it doesn't exist (dockerignored, not in image)
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate app key if not set
if [ -z "$APP_KEY" ] || grep -q "^APP_KEY=$" .env 2>/dev/null; then
    php artisan key:generate --force
fi

# Clear any cached config (may reference wrong paths)
php artisan config:clear

# Run migrations
echo "[entrypoint] Running migrations..."
php artisan migrate --force --verbose
echo "[entrypoint] Migrations complete."

# Cache config, routes, views for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] Boot complete, starting services..."

exec "$@"
