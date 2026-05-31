#!/bin/sh
set -e

echo "🚀 Starting SisforSinta..."

# Clear and cache Laravel configs for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Fix storage permissions
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Setup complete, starting supervisord..."

exec /usr/bin/supervisord -c /etc/supervisord.conf
