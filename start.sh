#!/bin/bash

# Generate application key if not exists
php artisan key:generate --force

# Cache configuration
php artisan config:clear
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate --force

# Build assets if needed
if [ -f "package.json" ]; then
    pnpm build
fi

# Start Apache
apache2-foreground
