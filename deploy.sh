#!/bin/bash
set -e

APP_DIR="/home/srv1541219.hstgr.cloud/public_html"
echo "Starting deploy..."

cd $APP_DIR

# Pull latest code
git stash
git pull origin main

# Clear all caches FIRST (before dependencies)
php artisan config:clear || true
php artisan view:clear || true
php artisan cache:clear || true
php artisan route:clear || true

# Set permissions
chown -R isoft1807:isoft1807 storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Restart web server
systemctl restart lsws

echo "Deploy complete! Site: https://isoftroerp.com"
