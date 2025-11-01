#!/bin/bash

# Generate application key
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Start Apache
apache2-foreground
