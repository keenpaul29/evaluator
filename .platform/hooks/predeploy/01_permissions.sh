#!/bin/bash
set -e

# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R webapp:webapp storage bootstrap/cache

# Create SQLite database if using SQLite (dev only)
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    touch database/database.sqlite
    chmod 775 database/database.sqlite
fi

echo "Pre-deploy hooks completed."
