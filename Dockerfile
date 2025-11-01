FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev

RUN docker-php-ext-install pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=$PORT
    php artisan view:cache

# Expose port 8000
EXPOSE 8000

# Start Laravel server
CMD php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=$PORT
