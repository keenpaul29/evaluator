#!/bin/sh

# Only create SQLite database if using SQLite connection
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    touch database/database.sqlite
fi

php artisan key:generate --force 2>/dev/null
php artisan migrate --force
php artisan config:cache
exec php artisan serve --host=0.0.0.0 --port=$PORT
