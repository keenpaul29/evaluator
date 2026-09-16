#!/bin/sh
touch database/database.sqlite
php artisan key:generate --force 2>/dev/null
php artisan migrate --force
php artisan config:cache
exec php artisan serve --host=0.0.0.0 --port=$PORT
