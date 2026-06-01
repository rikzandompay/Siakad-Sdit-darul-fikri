#!/bin/sh
set -e

echo "🚀 Starting SisforSinta..."

# Update Nginx port dynamically based on the PORT environment variable provided by the PaaS
PORT=${PORT:-8000}
sed -i "s/listen 8000;/listen ${PORT};/g" /etc/nginx/nginx.conf

# Clear and cache Laravel configs for production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run migrations gracefully so they don't crash the container if DB isn't ready
php artisan migrate --force || echo "⚠️ Migrations failed, continuing anyway..."

# Fix storage permissions
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Setup complete, starting supervisord..."

exec /usr/bin/supervisord -c /etc/supervisord.conf
