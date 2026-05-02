FROM helipad/laravel-frankenphp:latest

# Install PHP dependencies using the lockfile
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
