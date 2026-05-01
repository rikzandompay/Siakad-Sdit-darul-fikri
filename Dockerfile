FROM helipad/laravel-frankenphp:latest

# Baris ini akan dieksekusi saat deploy di server
RUN rm -f composer.lock && composer update --no-dev --no-interaction --prefer-dist