#!/bin/sh
set -e

echo "🚀 Starting SisforSinta..."

# Update Nginx port dynamically based on the PORT environment variable provided by the PaaS
PORT=${PORT:-8000}
sed -i "s/listen 8000;/listen ${PORT};/g" /etc/nginx/nginx.conf

# Fix storage permissions (critical - ensures www-data can write logs/cache/sessions)
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create empty laravel.log with correct ownership if it doesn't exist
touch /var/www/html/storage/logs/laravel.log
chown www-data:www-data /var/www/html/storage/logs/laravel.log
chmod 664 /var/www/html/storage/logs/laravel.log

# Clear any stale cache before optimizing
php artisan cache:clear || echo "⚠️ Cache clear failed, continuing..."
php artisan config:clear || echo "⚠️ Config clear failed, continuing..."

# Cache all Laravel optimizations for production (config, routes, views, events)
php artisan optimize || echo "⚠️ Optimize failed, continuing..."

# Run migrations gracefully so they don't crash the container if DB isn't ready
php artisan migrate --force || echo "⚠️ Migrations failed, continuing anyway..."

echo "✅ Setup complete, starting supervisord..."

exec /usr/bin/supervisord -c /etc/supervisord.conf
