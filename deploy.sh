#!/bin/bash
set -e

APP_DIR="/home/srv1541219.hstgr.cloud/public_html"
echo "Starting deploy..."

cd $APP_DIR

# Pull latest code
git stash
git pull origin main

# Skip composer install if PHP version incompatible
# composer install --no-dev --optimize-autoloader

# Run migrations (only if needed)
# php artisan migrate --force

# Clear all caches
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan route:clear

# Set permissions
chown -R isoft1807:isoft1807 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart web server
systemctl restart lsws

echo "Deploy complete! Site: https://isoftroerp.com"
