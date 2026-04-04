# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**iSoftro Academic ERP** — A multi-tenant SaaS ERP for educational institutions in Nepal. Built on Laravel 11 (PHP 8.2+) with a hybrid legacy/modern architecture. The system handles student management, fee collection, academic operations, HR, payroll, and double-entry accounting with Nepal-specific compliance (BS/AD dual calendar, NAS standards, TDS/SSF/ESF).

## Common Commands

```bash
# Asset development
npm run dev       # Start Vite dev server with hot reload
npm run build     # Build production assets (CSS/JS)

# Laravel artisan
php artisan serve           # Start local PHP dev server
php artisan migrate         # Run database migrations
php artisan db:seed         # Seed database
php artisan tinker          # Interactive REPL
php artisan route:list      # List all registered routes
php artisan cache:clear     # Clear application cache
php artisan config:clear    # Clear config cache

# Dependencies
composer install    # Install/update PHP dependencies
npm install         # Install JS dependencies
```

Apache serves the app from `c:\Apache24\htdocs\erp`. The `public/` directory is the document root. URL rewriting via `.htaccess` routes all requests through `public/index.php`. The application is live at **https://isoftroerp.com/**.

## Architecture

### Multi-Tenant System
Every request passes through `IdentifyTenant` middleware, which resolves the tenant from the subdomain and sets a global `tenant_id`. All models using the `TenantScoped` trait (`app/Models/Traits/TenantScoped.php`) automatically filter queries by this tenant. Never remove `tenant_id` scoping from queries.

### Authentication (Hybrid)
Two parallel auth systems coexist:
- **JWT** (`tymon/jwt-auth`) — used by API routes (`routes/api.php`), guarded by `auth:api` middleware
- **Session** — used by web routes (`routes/web.php`), guarded by role-specific middleware (`auth.superadmin`, `super_admin`, `module`)

The `AuthController` handles both flows. JWT TTL is 480 min (access) / 43200 min (refresh), configured in `config/jwt.php`.

### Role-Based Access Control
Roles are **hardcoded** in `config/config.php` (not in the database): `superadmin`, `instituteadmin`, `frontdesk`, `teacher`, `student`, `guardian`. Module-level access is enforced by `CheckModuleAccess` middleware. There is no dynamic role creation UI.

### Controller Organization
Controllers are organized by role/context:
- `app/Http/Controllers/API/` — RESTful JSON endpoints
- `app/Http/Controllers/Admin/` — Admin panel (mix of modern and legacy)
- `app/Http/Controllers/SuperAdmin/`, `FrontDesk/`, `Guardian/`, `Student/`, `Teacher/`

Business logic lives in `app/Services/` (20+ service classes). Controllers should delegate to services rather than implementing business logic directly.

### Accounting System
Double-entry bookkeeping via event-driven architecture. When operational events fire (e.g., `FeeCollected`, `SalaryPaid`), listeners in `app/Listeners/` auto-generate vouchers via `AccountingService`. Key models: `Account` (GL), `Voucher`, `LedgerPosting`. Fiscal year follows Nepali calendar (Shrawan 1 – Ashad 31).

### Frontend
Blade templates in `resources/views/` organized by role (`admin/`, `student/`, `guardian/`, `frontdesk/`, `layouts/`, `components/`). Alpine.js for interactivity, Bootstrap 5 for UI, SASS compiled via Vite. Entry points: `resources/css/app.scss` and `resources/js/app.js`.

### Legacy Compatibility
`bootstrap/legacy.php` provides a `db()` function returning a raw PDO connection for older procedural code. Some routes `require` legacy PHP files directly. When modifying these areas, check both the legacy path and the modern Laravel path.

## Key Configuration

| File | Purpose |
|------|---------|
| `config/config.php` | App constants, roles, file upload limits (5MB), permissions |
| `config/jwt.php` | JWT TTL and algorithm settings |
| `config/database.php` | MySQL connection (`hamrolabs_db`, `root`, no password in dev) |
| `config/mail.php` | SMTP/Gmail settings for transactional email |
| `.env` | Environment overrides (DB credentials, JWT secrets, API keys) |

## Known Gaps & Caveats

- **CSRF is disabled globally** — session-based web routes are vulnerable; be cautious adding new POST routes that rely solely on session auth
- **Library module** is stubbed (returns "coming in V3.1")
- **Inventory and Transport modules** have frontend UI but no backend implementation
- **Nepali date** conversions use `anuzpandey/laravel-nepali-date`; all date inputs may be in BS (Bikram Sambat) format — always check which calendar is expected before writing date logic
