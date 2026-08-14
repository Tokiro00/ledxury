# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MAM ERP (rebranding to **Pulso**) — a sales, inventory, and accounting management system for Colombian commercial enterprises, evolving into a multi-tenant platform (Mastershop-style aggregator). Built on **CodeIgniter 3** (PHP 8.2), with **Tailwind CSS 1.8** and **Webpack 4** on the frontend. The primary language in comments, views, and business logic is **Spanish**.

## Build & Development Commands

```bash
npm install            # Install frontend dependencies (first time)
npm start              # Dev mode with webpack --watch (BrowserSync disabled)
npm run dev            # One-time development build
npm run prod           # Production build (minification, PurgeCSS)
composer install       # Install PHP dependencies (phpspreadsheet, mpdf)

# PHP lint (no test suite exists; lint before deploying)
c:\xampp\php\php.exe -l path/to/file.php

# Local MySQL CLI
c:\xampp\mysql\bin\mysql.exe -u root ledxury
```

Frontend source lives in `public/assets/` — webpack outputs to `public/dist/` (gitignored).

## Databases — CRITICAL

| Environment | DB name | Notes |
|---|---|---|
| **Local (XAMPP)** | `ledxury` | What `application/config/database.php` points to. A `mamdb` DB also exists locally but is NOT used by the app. |
| **Production (EC2)** | `mamdb` | User `admindbmam`. Schemas drift between local and prod — always `DESC` a table on the target before writing INSERTs (e.g., `supplier_invoice_details` uses `unitPrice`/`total` in prod, not `unitCost`/`subtotal`). |

Migrations in `db/migrations/`, numbered sequentially, executed manually. Recent: 057 (expense category devoluciones), 058 (contrapago company varchar), 059 (mam_returns), 060 (Pulso multi-tenant foundation — `tenants` table + `tenant_id` on ~105 tables + rollback script).

## Deployment

Production: AWS EC2 (`ledxury.com`), webroot `/var/www/html`, **no git on server**. Deploy = SCP file to `/tmp` on the server, then `sudo cp` to webroot + `sudo chown apache:apache`. SSH key at `db/Amazon_MAM.pem`. Always `php -l` locally before deploying; back up the prod file to `/tmp` before overwriting. Prod CI logging is disabled (`log_threshold = 0`); errors display on screen instead.

The **Pulso visual rebrand stays local-only** until explicitly released.

## Architecture

### MVC Pattern (CodeIgniter 3)

- **Controllers** (`application/controllers/sisvent/`): organized by domain — `commercial/`, `accounting/`, `admin/`, `business/`, `store/`, `rest/`, plus `api/` and `v2/` (Pulso redesign views).
- **Models** (`application/models/`): ~65 models using CI Query Builder.
- **Views** (`application/views/sisvent/`): PHP templates. Shared layouts in `layouts/` (meta_header, navbar, sidebar, sidemenu, footer).

URL routing: `base_url/sisvent/{subdirectory}/{controller}/{method}`. API routes have explicit mappings in `application/config/routes.php`.

### Multi-Tenant (Pulso) — pervasive concern

The platform supports multiple independent companies (tenants). Seed: `id=1 ledxury` (all legacy data backfilled here), `id=2 mam-online`. Decisions: strong isolation (separate catalogs/cashboxes/banks/bots/accounting per tenant), users bound 1:1 to a tenant, independent document numbering, subdomain routing (`{slug}.pulso.test` locally), no inter-tenant transactions.

**Key pieces:**
- **`tenants` table** — slug, NIT, branding colors, `inter_sucursal_id` (Interrapidísimo `CodigoConvenioRemitente` per tenant), invoice footer texts.
- **`application/core/MY_Model.php`** — base class for tenant-aware models. Models extend `MY_Model` instead of `CI_Model` and call:
  - `$this->applyTenantFilter('alias')` before list/count queries (adds `WHERE tenant_id`)
  - `$this->tenantInsert($table, $data)` / `tenantInsertBatch()` for writes (injects `tenant_id`)
  - `$this->withAllTenants()` — platform-admin bypass for the next query
  - `$this->nextNumber($docType)` — per-tenant document counter (`tenant_invoice_counters`, lazy-inits from legacy MAX for tenant 1)
- **Tenant context resolution** (priority order): explicit override → web session → default 1. Override via `set_tenant_context($id)` in `mam_helper` — used by JWT APIs, bot webhooks, CLI. Other helpers: `current_tenant_id()`, `apply_tenant($alias)` (for direct `$this->db` queries in controllers), `is_platform_admin()`, `tenant_data($data)`.
- **`Backend_lib::resolveTenant()`** — extracts subdomain from host (`{slug}.pulso.{test|app|local|dev}`), validates the user belongs to that tenant (platform admins exempt), hydrates session with `tenant_id`, `tenant_slug`, `tenant_brand`, etc. Falls back to user's tenant on `localhost`.
- **JWT carries tenant**: `JWT_lib::generateToken()` embeds `tid` + `pa` claims; all API `_authenticate()` methods call `set_tenant_context()` after validation (with DB fallback for legacy tokens).
- **Platform admin**: `users.is_platform_admin = 1` (Alex `71339095`). Tenant switcher in navbar; tenant CRUD at `/sisvent/admin/tenants` (controller checks the flag explicitly).
- Getters by primary key (`getUser`, `getBill`, etc.) intentionally do NOT filter by tenant — login and cross-tenant lookups need them.
- Apache local vhost: `*.pulso.test` → this docroot (see `c:\xampp\apache\conf\extra\httpd-vhosts.conf`); hosts-file entries required.

**When adding any new query against transactional tables, make it tenant-aware** (extend MY_Model or use the helpers). New features must assume multi-tenant from design.

### Two Authentication Patterns

**Web (MVC):** Session-based. Every web controller calls `$this->backend_lib->control()` (optionally `->control([1])` role-gated, or `->controlModule('key')`) in its constructor — this also resolves the tenant. Session user data: `uname`, `role`, `store`, `admin_store`, `tenant_id`, `is_platform_admin`. Roles: 1 admin, 2 gerente, 3 vendedor, 4 contador/almacenista, 10 superadmin-bots.

**API (REST):** JWT (HS256, 7 days) via `Authorization: Bearer`. Secret in `application/config/secrets.php`. Stateless bot webhooks use `users.bot_api_key` via `X-Api-Key` header. All API auth paths must call `set_tenant_context()` after validating.

### Key Libraries

- **`Accounting_lib`**: centralized journal entry generation. Resolves accounts through `accountingsettings_model` (`accounting_settings` table maps setting keys → subaccount ids; e.g. `account_inventory_transit`, `account_payable`). **Controllers using Accounting_lib must load `accountingsettings_model`** or account resolution silently falls back to PUC-code lookup, which fails if the PUC seed differs (prod uses 143501/220505, not the 143505/220501 defaults hardcoded as fallbacks).
- **`Interrapidisimo_lib`**: carrier REST API (quote, create guides, PDF, pickups, status). Credentials in `secrets.php`: one corporate `IdClienteCredito` + per-shipment `CodigoConvenioRemitente` (sucursal — will come from `tenants.inter_sucursal_id` for multi-tenant shipping). API docs: `db/INTERRAPIDISIMO_API_REST_DOCUMENTACION_TECNICA.md`.
- **`Reconciliation_lib`**, **`JWT_lib`**, **`Api_response`**, **`mam_helper`** — as their names suggest.

### Accounting (PUC Colombia)

```
accounts_class → accounts_group → accounts_accounts → subaccounts → auxiliary_subaccounts
```

All financial transactions go through `Accounting_lib`. Periods can be closed per month/store, blocking entries. To void a document, soft-delete it AND mark its `entries` row `deleted=1` (filtered from reports).

### Products cost columns — gotcha

`products.cost` is legacy and ~97% empty. The real cost lives in **`products.cost_cop`** (and `cost_rmb` for China imports). Always read cost as `COALESCE(NULLIF(cost_cop,0), NULLIF(cost,0), 0)`.

### MAM Consignment Model (Cierre Compra MAM)

Ledxury operates without owned inventory — MAM supplies stock on consignment. In `admin/Accountspayable.php`:
- **`closeCycleMam()`** — consolidates products sold since last cierre, minus physical returns in stock, creates a `supplier_invoices` bill to provider MAM (id=12) with journal entry (DR inventory-transit / CR proveedores + aux MAM). Editable preview (remove rows, adjust qty/cost) before generating.
- **`returnToMam()`** — physical return of customer-returned stock to MAM: decrements inventory, creates `mam_returns` + items, printable acta (`returnPdf`), reverse journal entry, and a **negative `supplier_invoices` row (`NC-MAM-...`) acting as a credit note** that nets against payables.
- **`deleteBill()`** — voids a supplier invoice (soft delete + reverses its journal entry); only if unpaid.

### Contrapago / Interrapidísimo Settlement System (`admin/Contrapagos.php`)

Two import types, cross-referenced by guide number against `shipping_guides.numeroPreenvio`:
- **Payment lots** (`contrapago_batches` + `contrapago_payments`): Excel of contrapago disbursements Inter paid us. `matchGuides()` classifies each guide: match → `company='ledxury'`, no match → `company='mam'`. Duplicate guides across lots are flagged `duplicada`. "Registrar ingreso" creates the cash movement + journal entry.
- **Carrier invoices / CORTE** (`contrapago_invoices` + `contrapago_invoice_items`): freight bills Inter charges us. Items classified per company via UI dropdown (`markCompany`): `ledxury | mam | mam_online | no_invoice | disputa | sin_revisar`.

**Intercompany receivables** (`intercompany_movements`, `Intercompany_model`): when registering a payment or invoice, `generateFromContrapagoBatch()` / `generateFromInterInvoice()` create one `cobro_pendiente` movement **per partner company** (`GROUP BY company`, excluding ledxury/administrative buckets) — the freight Ledxury paid on behalf of MAM/MAM-Online becomes a receivable. `partner_company` column tracks which company owes. Dashboard: `/sisvent/admin/contrapagos/entreCompanias` shows per-company balances.

Caveat: only import the consolidated payment file (real amounts); the CORTE listing has no per-guide amounts — importing it as a payment lot poisons dedup (guides get marked `duplicada` in the real lot).

### Bot Integration (`sisvent/rest/BotImport.php`)

Webhooks from Google Sheets bots / BuilderBot. API-key auth, no session. Flow: `receive` → `bot_sales_queue` → async `process` → creates budgets. After auth, sets tenant context from the owning vendor's `tenant_id`.

### Sales Tracking & KPIs (`admin/Tracking.php`)

Hardcoded company constants (META_VENTAS, META_RECAUDO, MARGEN_BRUTO 0.527, STORES_MDE [1,3,5], STORES_INV [1,8]). Weekly snapshots in `tracking_weekly` / `tracking_weekly_extras` (append-only).

### Expenses Module

`expense_categories` (linked to PUC subaccounts) + `expense_records` (operational). The older `expenses` table is **only** for vendor settlements. "Pagado" expense → cash movement + balance update + journal entry. **storeId=0 convention**: cajas/bancos with `storeId=0` appear in all store dropdowns.

## Conventions & Gotchas

- **jQuery events**: always delegated — `$(document).on('event', '#sel', fn)`. Direct binding fails (elements load after script init). Form submit buttons that trigger AJAX should be `type="button"` with a click handler + `onsubmit="return false;"` on the form — otherwise a JS error silently falls through to a native GET submit.
- **PHP 8.2 + CI3**: `E_DEPRECATED` must stay suppressed in `index.php` (dynamic properties → headers-already-sent → session failure). Node 22+ needs `NODE_OPTIONS=--openssl-legacy-provider` (already in package.json).
- **IDE diagnostics**: `$this->db` / `$this->session` on models trigger PHP6602 "magic method" hints — false positives, CI3 resolves them at runtime. Trust `php -l` instead.
- **Flashdata**: use module-specific keys (`login_error`), never generic `error` (leaks across views).
- **Excel number columns** may carry invisible characters — when importing guide numbers, strip non-digits before matching (`REGEXP_REPLACE(col, '[^0-9]', '')`).
- **`number input step`**: avoid `step="100"` on editable money inputs — browser validation rejects non-multiples.

## Tech Stack Summary

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2 (XAMPP), CodeIgniter 3 |
| Database | MariaDB 10.4 (InnoDB) |
| CSS | Tailwind CSS 1.8.7 |
| JS | jQuery 3.5, Vue 2 (sidebar/layout), Alpine (v2 Pulso views), Lodash |
| Bundler | Webpack 4 |
| Server | Apache + .htaccess rewriting (XAMPP local, EC2 prod) |
