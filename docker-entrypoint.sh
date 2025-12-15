#!/bin/sh
set -e

# Run standard Laravel optimization commands
echo "Caching configuration..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Generate preload script for OpCache
echo "Generating OpCache preload script..."
echo "<?php" > preload.php
echo "require __DIR__ . '/vendor/autoload.php';" >> preload.php
# Basic preload logic (can be improved)
# Only preload hot files to avoid memory exhaustion
find app -name "*.php" >> preload.php

# Start supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
