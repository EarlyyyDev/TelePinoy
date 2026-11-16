FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libonig-dev libpq-dev libxml2-dev libzip-dev \
    && docker-php-ext-install mbstring pdo_pgsql xml zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

ENV PORT=10000

CMD ["sh", "-c", "php artisan migrate --force && if [ \"$SEED_DATABASE\" = \"true\" ]; then php artisan db:seed --force; fi && exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
