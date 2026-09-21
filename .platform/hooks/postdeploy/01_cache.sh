#!/bin/bash
set -e

# Restart queue worker
php artisan queue:restart

# Clear and warm caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Post-deploy hooks completed."
