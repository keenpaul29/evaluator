#!/bin/bash
set -e

# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R webapp:webapp storage bootstrap/cache

echo "Pre-deploy hooks completed."
