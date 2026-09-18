#!/bin/bash
set -e

# Environment variables are available from EB option_settings
DB_HOST="${DB_HOST:-}"
DB_USERNAME="${DB_USERNAME:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_DATABASE="${DB_DATABASE:-}"

if [ -z "$DB_HOST" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_DATABASE" ]; then
    echo "DB env vars not set, skipping database creation."
    exit 0
fi

echo "Waiting for MySQL at $DB_HOST..."
for i in $(seq 1 30); do
    if php -r "\$c=new mysqli('$DB_HOST','$DB_USERNAME','$DB_PASSWORD');if(\$c->connect_error){echo \$c->connect_error;exit(1);}\$c->close();echo 'connected';" 2>/dev/null; then
        echo ""
        echo "Connected. Creating database if not exists..."
        php -r "
        \$c = new mysqli('$DB_HOST', '$DB_USERNAME', '$DB_PASSWORD');
        if (\$c->connect_error) { echo 'Connect failed: '.\$c->connect_error; exit(1); }
        \$c->query('CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`');
        echo 'Database $DB_DATABASE ready.';
        \$c->close();
        "
        exit 0
    fi
    echo "Attempt $i: MySQL not ready, retrying in 3s..."
    sleep 3
done

echo "ERROR: Could not connect to MySQL after 90 seconds"
exit 1
