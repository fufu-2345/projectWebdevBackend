FROM php:8.2-apache

WORKDIR /var/www/html

# Install system dependencies and Node.js
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

# Install pnpm
RUN npm install -g pnpm

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader \
    && pnpm install \
    && chown -R www-data:www-data . \
    && chmod -R 755 storage bootstrap/cache

# Configure Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Setup startup script
COPY start.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start.sh

# Expose port
EXPOSE 80

# Start server
CMD ["/usr/local/bin/start.sh"]
