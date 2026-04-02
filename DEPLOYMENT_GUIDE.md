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

```bash
# Check that tables exist
mysql -h 127.0.0.1 -u isof_isoftro_user -p isof_isoftro_db << EOF
SELECT 'addon_features' as table_name, COUNT(*) as count FROM addon_features
UNION ALL
SELECT 'tenant_addons', COUNT(*) FROM tenant_addons
UNION ALL
SELECT 'addon_requirements', COUNT(*) FROM addon_requirements
UNION ALL
SELECT 'addon_usage_logs', COUNT(*) FROM addon_usage_logs;
EOF
```

Expected output:
```
+-----------------------+-------+
| table_name            | count |
+-----------------------+-------+
| addon_features        |    19 |
| tenant_addons         |     0 |
| addon_requirements    |     4 |
| addon_usage_logs      |     0 |
+-----------------------+-------+
```

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

Then run:
```bash
chmod +x deploy-addons.sh
./deploy-addons.sh
```

## What Was Changed

### New Files
- `database/migrations/2026_04_02_100000_create_addon_feature_system.php` - 4 new tables
- `database/seeders/AddonFeaturesSeeder.php` - Feature definitions
- `database/seeders/DatabaseSeeder.php` - Seeder registry
- `resources/views/super-admin/manage-addons.php` - Admin UI
- `seed-addons.php` - Direct seeding script

### Modified Files
- `app/Http/Controllers/API/SuperAdminController.php` - Added 8 new add-on methods
- `config/config.php` - Enhanced hasFeature() to check add-ons
- `routes/api.php` - Added 9 new API routes

### Key Features Added

**19 Premium Add-Ons Across 6 Categories:**

- **Analytics (2):** Advanced Analytics, BI Dashboard
- **Communications (3):** SMS Gateway, Email Campaigns, WhatsApp
- **Integrations (4):** Google Classroom, Zoom, Microsoft Teams, Google Meet
- **Automation (3):** Workflow Engine, Advanced API, Premium Webhooks
- **Compliance (4):** Advanced Security, GDPR, Backup & Recovery, Data Encryption
- **Support (3):** Priority Support, Dedicated Manager, Custom Training

**Pricing:**
- Monthly: $20 - $200 per add-on
- Annual: 10-20% discount available

## Rollback Plan (If Needed)

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

## Troubleshooting

### Migrations fail with "table already exists"
- This is expected if migrating in a dev environment
- Use `--force` flag: `php artisan migrate --force`

### Seeding fails with database error
- Ensure database credentials are correct in `.env`
- Run seeding with direct script: `php seed-addons.php`

### Add-on menu not visible in tenant portal
- Clear browser cache and cookies
- Check that tenant has plan-based features assigned
- Verify hasFeature() returns true via API: `/api/super/tenants/{tenantId}/addons`

### API returns 401 Unauthorized
- Check authentication token is valid
- Use: `curl ... -H "Authorization: Bearer $TOKEN"`

## Support

For issues or questions:
- Check error logs: `/home/srv1541219.hstgr.cloud/public_html/storage/logs/`
- Database issue: `mysql -u isof_isoftro_user -p isof_isoftro_db`
- Contact: isoftrosolutions@gmail.com

## Success Verification

✅ All tables created (4 new tables with correct row counts)
✅ 19 add-on features seeded
✅ Super admin UI accessible at `/admin/manage-addons`
✅ API endpoints respond with 200 status
✅ Existing tenants get plan-based features auto-assigned
✅ New tenants get features based on their plan
✅ Feature gating works in sidebar (modules appear/disappear based on features)

---

**Deployment Date:** 2026-04-02
**Version:** 1.0.0
**Git Commits:** 94393f8, 21fcbe8
