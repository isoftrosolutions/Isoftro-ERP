# Add-On Features System - Production Deployment Guide

## Overview
This deployment adds a scalable, plan-based add-on feature gating system to iSoftro ERP. It includes:
- 4 new database tables (addon_features, tenant_addons, addon_requirements, addon_usage_logs)
- 19 pre-configured premium features
- Automatic feature assignment based on tenant plans
- Super admin management UI and API endpoints

## Pre-Deployment Checklist

- [ ] GitHub repository updated (commits pushed)
- [ ] Database backups created
- [ ] Maintenance window scheduled
- [ ] SSH access to server available

 ### For chatbot Nepal Project 
cd /home/isoftroerp.com/chatbotnepal.isoftroerp.com
git pull origin main
sudo -u isoft1807 /usr/local/lsws/lsphp84/bin/php artisan migrate --force
sudo -u isoft1807 /usr/local/lsws/lsphp84/bin/php artisan config:clear


## Deployment Steps

### 1. SSH into Production Server

```bash
ssh root@187.127.139.209
# Enter password when prompted
```

Server Details:
- **Path:** `/home/srv1541219.hstgr.cloud/public_html/`
- **Web Server:** OpenLiteSpeed
- **Database:** MariaDB (isof_isoftro_db)

### 2. Navigate to Application Root

```bash
cd /home/srv1541219.hstgr.cloud/public_html/
```

### 3. Pull Latest Changes from GitHub

```bash
git pull origin main
```

Expected output:
```
From https://github.com/isoftrosolutions/Isoftro-ERP
 * branch            main       -> FETCH_HEAD
Already up to date.
```

or

```
Updating 8a52a5b..21fcbe8
Fast-forward
 app/Http/Controllers/API/SuperAdminController.php       | 32 ++-
 config/config.php                                       | 35 ++-
 database/migrations/2026_04_02_100000_create_addon_feature_system.php | 80 +++++++
 database/seeders/AddonFeaturesSeeder.php                | 215 +++++++++++++++++
 database/seeders/DatabaseSeeder.php                     | 14 ++
 resources/views/super-admin/manage-addons.php           | 650 ++++++++++
 routes/api.php                                          | 21 +-
 seed-addons.php                                         | 112 ++++
 8 files changed, 1535 insertions(+)
```

### 4. Run Database Migrations

```bash
php artisan migrate --force
```

Expected output:
```
Running migrations.

  2026_04_02_100000_create_addon_feature_system
```

### 5. Seed Add-on Features

```bash
php seed-addons.php
```

Expected output:
```
[*] Seeding add-on features...
[✓] Added 19 add-on features
[✓] Added 4 add-on requirements

[✅] Seeding completed successfully!
```

### 6. Clear Application Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 7. Restart Web Server

```bash
systemctl restart lsws
```

### 8. Verify Deployment



### 9. Test Add-On API Endpoints

```bash
# Get authentication token first
TOKEN=$(curl -s -X POST "https://isoftroerp.com/api/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"isoftrosolutions@gmail.com","password":"YOUR_PASSWORD"}' | jq -r '.access_token')

# Test get available add-ons
curl -X GET "https://isoftroerp.com/api/super/addons" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq .

# Expected: List of add-on features grouped by category
```

## Complete Deployment Script (One-Command)

If you want to run everything at once, create a file `deploy-addons.sh`:

```bash
#!/bin/bash

cd /home/srv1541219.hstgr.cloud/public_html/

echo "[1/8] Pulling latest code..."
git pull origin main

echo "[2/8] Running migrations..."
php artisan migrate --force

echo "[3/8] Seeding add-on features..."
php seed-addons.php

echo "[4/8] Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo "[5/8] Restarting web server..."
systemctl restart lsws

echo "[6/8] Waiting for server to start..."
sleep 5

echo "[7/8] Verifying deployment..."
mysql -h 127.0.0.1 -u isof_isoftro_user -p isof_isoftro_db \
  -e "SELECT COUNT(*) as addon_count FROM addon_features;"

echo "[✅] Deployment completed successfully!"
echo ""
echo "Next steps:"
echo "1. Access Super Admin dashboard"
echo "2. Navigate to 'Add-on Features Management' page"
echo "3. Verify 19 add-ons are listed"
echo "4. Assign add-ons to test tenant"
echo "5. Check that modules appear in tenant portal"
```




If something goes wrong:

```bash
cd /home/srv1541219.hstgr.cloud/public_html/

# Revert to previous commit
git reset --hard HEAD~1

# Or revert specific files
git revert HEAD

# Rollback migrations
php artisan migrate:rollback

# Restart server
systemctl restart lsws
```
