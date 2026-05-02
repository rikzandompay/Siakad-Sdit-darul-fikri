FROM helipad/laravel-frankenphp:latest

# Build steps
RUN composer install --no-dev --no-interaction --prefer-dist
RUN npm install && npm run build
RUN php artisan optimize:clear
