# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SisVent (Sistema de Ventas) — a multi-module business management/ERP system built on **CodeIgniter 3.x**. Handles invoicing, inventory, accounting, client/vendor management, payments, and shipping integrations. The frontend uses **Tailwind CSS 1.x + Alpine.js** bundled via **Webpack 4**.

## Local Development Environment

- **Server**: XAMPP (Apache + MySQL + PHP) on Windows
- **Access URL**: `http://localhost/ledxury/`
- **PHP compatibility**: Running on PHP 8.2+. CodeIgniter 3 triggers `E_DEPRECATED` warnings for dynamic properties — these are suppressed in `index.php` error reporting. Do not re-enable `-1` (all errors) or the app will show CI3 core deprecation noise.

## Build & Development Commands

```bash
# Frontend build (Tailwind + JS bundling)
npm start          # Dev mode with watch + BrowserSync live reload
npm run dev        # One-off development build
npm run prod       # Production build (minified, PurgeCSS)
npm run build      # Both prod + dev concurrently

# PHP dependencies
composer install

# Tests (PHPUnit)
vendor/bin/phpunit
```

## Architecture

### Backend (CodeIgniter 3 MVC)

- **Entry point**: `index.php` → routes through CodeIgniter to controllers
- **URL pattern**: `http://localhost/ledxury/sisvent/{module}/{controller}/{method}/{params}` — CI3 maps URL segments to `application/controllers/` directory structure automatically. No custom routes are defined; all routing follows the convention.
- **Base URL**: auto-detected in `application/config/config.php`
- **Database**: MySQL (`mamdb`) via MySQLi driver, configured in `application/config/database.php`
- **CSRF protection**: enabled globally — all POST forms must include the CSRF token

**Controllers** (`application/controllers/sisvent/`) organized by domain:
- `accounting/` — Chart of accounts, subaccounts, account groups/classes
- `admin/` — Payments, settlements, vouchers, payment methods
- `business/` — Clients, products, providers, stores, users, vendors
- `commercial/` — Budgets (presupuestos), invoices (facturas), non-invoices
- `store/` — Catalogue, inventory, transfers
- `rest/` — JSON API endpoints (Pedidos, Presupuestos, DbQuery)
- Root: `Dashboard.php`, `Login.php`, `Message.php`

**Models** (`application/models/`) — one per entity (25 total). The largest/most complex: `Invoices_model`, `Inventory_model`, `Noinvoices_model`, `Budgets_model`.

**Libraries** (`application/libraries/`):
- `Backend_lib.php` — Authentication guard, loaded in controller constructors to enforce login
- `Interrapidisimo_tracker.php` — Shipping integration with Interrapidísimo
- `Tracking_service.php` — Tracking abstraction layer

**Helpers** (`application/helpers/`):
- `mam_helper.php` — Large utility file (~55KB) with shared functions (`is_logged_in()`, `test_input()`, `getVendorSettlement()`, etc.)
- `login_helper.php` — Authentication utilities

### Frontend

- **Webpack entry**: `public/assets/js/main.js` → outputs to `public/dist/`
- **CSS**: Tailwind CSS processed via PostCSS, with PurgeCSS in production
- **JS modules**: `public/assets/js/apps/` contains feature-specific modules (inventory, modals, file upload, etc.)
- **Alpine.js**: initialized in `public/assets/js/alpine/`
- **Static libs**: jQuery, Lodash, Glide.js in `public/assets/js/static/`

### Views

Views in `application/views/sisvent/` mirror the controller module structure. Layout components (`layouts/meta_header.php`, `navbar.php`, `sidemenu.php`, `sidebar.php`, `footer.php`) are loaded by each page view. When adding new pages, follow the same pattern: load `meta_header` at top, include `navbar`/`sidemenu`, then page content, then `footer`.

## Key Integrations

- **Interrapidísimo**: Shipping/tracking provider. Library in `application/libraries/Interrapidisimo_tracker.php`, config in `application/config/tracking.php`
- **Google Sheets API**: Used for data import (orders). Setup docs in `GOOGLE_SHEETS_API_SETUP.md`
- **PHPSpreadsheet**: Excel import/export
- **mPDF**: PDF generation (invoices, catalogs)

## Database

- MySQL database `mamdb`, schema in `db/mamdb.sql` (with `db/contab.sql` for accounting tables)
- Schema changes tracked in `database_changes_*.sql` files at repo root
- When making schema changes, add the SQL to a new `database_changes_YYYY-MM.sql` file

## Language

- Default language: Spanish. Language files in `application/language/spanish/` and `application/language/english/`
- Code comments, variable names, and UI text are primarily in Spanish

## Authentication

- Session-based auth with bcrypt password hashing
- `Backend_lib` library enforces login on protected controllers
- `login_helper.php` provides `is_logged_in()` check
