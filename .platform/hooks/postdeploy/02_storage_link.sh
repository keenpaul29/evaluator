#!/bin/bash
set -e

cd /var/app/current

php artisan storage:link --force