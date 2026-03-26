# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MAM ERP — a sales, inventory, and accounting management system for Colombian commercial enterprises. Built on **CodeIgniter 3** (PHP), with **Tailwind CSS 1.8** and **Webpack 4** on the frontend. The primary language in comments, views, and business logic is **Spanish**.

## Build & Development Commands

```bash
npm install            # Install frontend dependencies (first time)
npm start              # Dev mode with webpack --watch + BrowserSync
npm run dev            # One-time development build
npm run prod           # Production build (minification, PurgeCSS)
npm run build          # Concurrent prod + dev build
composer install       # Install PHP dependencies (phpspreadsheet, mpdf)
```

Frontend source lives in `public/assets/` — webpack outputs to `public/dist/` (gitignored).

## Database Migrations

SQL migration scripts are in `db/migrations/`, numbered sequentially. Execute manually in order:

| Range | Content |
|-------|---------|
| 001–008 | PUC chart of accounts, cash/bank, expenses, accounting periods, settlements |
| 009 | Department KPIs seed data and bonus structures |
| 010 | Roles and permissions |
| 011 | Full upgrade (schema consolidation) |
| 012 | Bank reconciliation tables (`bankstatementlines`, `bankreconciliations`) |
| 013 | PUC subaccounts for expenses, income, costs |
| 014 | Weekly/monthly tracking tables (`tracking_weekly`, `cierre_mensual`) |
| 015 | AI conversation persistence (`ai_conversations`, `ai_messages`) |
| migration_contabilidad.sql | Bot integration columns, entry enhancements, PUC codes |

## Architecture

### MVC Pattern (CodeIgniter 3)

- **Controllers** (`application/controllers/sisvent/`): Organized by domain — `commercial/`, `accounting/`, `admin/`, `business/`, `store/`
- **Models** (`application/models/`): ~37 models using CI Query Builder. Each model maps to a domain entity.
- **Views** (`application/views/sisvent/`): PHP templates. Shared layouts in `layouts/` (meta_header, navbar, sidemenu, footer)

### URL Routing

Standard CI3 routing: `base_url/sisvent/{subdirectory}/{controller}/{method}`. Default controller is `welcome`. No custom route overrides — the directory structure IS the routing.

API routes follow: `base_url/api/v1/{method}` and `base_url/sisvent/rest/{controller}/{method}`.

### Auto-loaded Resources (`application/config/autoload.php`)

- **Libraries**: database, session, backend_lib, form_validation, email, user_agent
- **Helpers**: url, login, mam
- **Models**: outh_model, logs_model

All other models are loaded per-controller in `__construct()`.

### Two Authentication Patterns

**Web (MVC):** Session-based. Every web controller calls `$this->backend_lib->control()` in its constructor. Accepts optional `$roles` array (e.g., `->control([1])` for admin-only, `->control([4])` for accountant).

**API (REST):** JWT-based via `Authorization: Bearer <token>` header. `JWT_lib` uses HS256, 7-day expiration. Secret configured via `$config['jwt_secret']` in `application/config/secrets.php` — **must be changed from default in production**. Stateless controllers (e.g., `BotImport`) use API key auth via `users.bot_api_key`.

```php
// Web controller pattern
class Example extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->backend_lib->control();           // Auth check
        $this->load->model('example_model');
        $this->load->library('accounting_lib');  // Only if doing accounting ops
    }
}

// API controller pattern — no backend_lib, uses JWT
// Returns JSON via $this->api_response->success($data) or ->error($msg, $code)
// CORS headers set automatically
```

### Authentication & Roles

Session-based auth. User session data at `$this->session->userdata('user_data')` contains `uname`, `role`, `admin_store`. Roles: 1 (admin/full access), 2 (gerente/manager), 3 (vendedor/sales), 4 (contador/accountant).

Some accounting modules use `$this->backend_lib->controlModule()` instead of `->control()` for module-level permissions.

### Key Libraries

- **`Backend_lib`** (`application/libraries/Backend_lib.php`): Authentication guard. Calls `->control()` or `->controlModule()`.
- **`Accounting_lib`** (`application/libraries/Accounting_lib.php`): Centralized journal entry generation for cash/bank movements, invoice settlements, payment processing, and operational expenses. Enforces accounting period closure checks. Designed for multi-store (multi-bodega) operation.
- **`Reconciliation_lib`** (`application/libraries/Reconciliation_lib.php`): Bank statement reconciliation. `autoMatch()` uses strict criteria (exact amount, ±3 days); `suggestMatches()` uses relaxed scoring (±10% amount, ±7 days). Returns top 5 candidates with confidence score 0–100.
- **`JWT_lib`** (`application/libraries/JWT_lib.php`): HS256 JWT encode/decode for REST API authentication.
- **`Api_response`** (`application/libraries/Api_response.php`): Standardizes all API JSON responses as `{ status, message, data }` with CORS headers.
- **`mam_helper`** (`application/helpers/mam_helper.php`): Core utility functions — asset paths, input sanitization, email sending, partner privilege checks, formatting.

### Accounting Hierarchy (PUC Colombia)

```
accounts_class → accounts_group → accounts_accounts → subaccounts → auxiliary_subaccounts
```

Financial transactions must go through `Accounting_lib` to generate proper journal entries. Accounting periods can be closed, blocking further entries for that month/store.

Journal entries now include: `entryStoreId`, `cost_center_id`, `entryTransactionType`, `entryTransactionId`, `created_by`.

**Opening Balances** (`Apertura.php`): Creates 'apertura' journal entries for initial subaccount balances. Uses contra-accounts (class 3 patrimonio). Transactional — all entries grouped atomically.

**Cost Centers** (`Costcenters.php`): Hierarchical cost center CRUD. Linked to journal entries via `cost_center_id`. Soft delete pattern.

### REST API Module (`application/controllers/api/V1.php`)

Endpoints available:
- `POST /api/v1/login`, `POST /api/v1/refresh` — JWT auth
- `GET /api/v1/clients` — list/search clients (vendors see only their own)
- `GET /api/v1/products` — catalog search with pagination
- `GET /api/v1/budgets` — list/filter by store; `POST /api/v1/budgets/sync` — create from external source
- `GET /api/v1/cartera` — accounts receivable summary by client
- `POST /api/v1/refunds` — create refund from invoice
- Liquidación endpoints for vendor settlements

Role 3 (vendedor) results are automatically filtered to their own clients/budgets.

### Bot Integration (`application/controllers/sisvent/rest/BotImport.php`)

Receives sales data from external bots (e.g., Google Sheets) via webhook. Uses API key auth (`users.bot_api_key`), **not** session or JWT. Does NOT call `backend_lib->control()`.

Flow: `POST /sisvent/rest/botimport/receive` → queued in `bot_sales_queue` → processed async via `GET /sisvent/rest/botimport/process` → creates budgets in MAM.

### Sales Tracking & KPIs (`application/controllers/sisvent/admin/Tracking.php`)

Hardcoded company KPIs:
```php
const META_VENTAS    = 500000000;   // Monthly sales goal (COP)
const META_RECAUDO   = 500000000;   // Collections goal
const MARGEN_BRUTO   = 0.527;       // Fixed gross margin %
const STORES_MDE     = [1, 3, 5];   // Stores counted for MDE sales
const STORES_INV     = [1, 8];      // Stores counted for inventory
```

Four dashboards: `semanal` (weekly), `acumulado` (month-to-date), `cierre` (monthly close P&L), `mi_desempeno` (individual vendor vs. goal).

Data is stored as weekly snapshots in `tracking_weekly` and `tracking_weekly_extras` — append-only audit trail.

### Department & Bonus System

Departments (`departments` table) have tiered bonuses: `bonus_base`, `bonus_cumpl`, `bonus_elite`, `bonus_max_annual`. KPIs defined in `department_kpis` with weights and targets. Minimum 3 KPIs at ≥70% compliance required to qualify for bonuses. Results tracked in `department_kpi_results`.

### AI Assistant & Agents

- **`Aiassistant.php`**: Conversations persisted to `ai_conversations` + `ai_messages` (role: user/assistant).
- **`Agents.php`** (admin-only, role 1): Automation agents — Collections Agent queries overdue invoices (>30 days) and uses AI to generate collection messages.

### Export Capabilities

- Excel (.xlsx) via PHPSpreadsheet (`vendor/phpoffice/phpspreadsheet`)
- PDF via mPDF (`vendor/mpdf/mpdf`)

### Expenses Module

- **Tables**: `expense_categories` (linked to PUC subaccounts) and `expense_records` (operational expenses)
- **Note**: The existing `expenses` table is used exclusively for vendor settlements/liquidations. Operational expenses use `expense_records`.
- **Flow**: Creating a "pagado" expense triggers: cash movement (egress), balance update on caja/banco, and automatic journal entry via `Accounting_lib::recordExpense()`
- **storeId=0 convention**: Cajas and bancos with `storeId=0` appear in all store dropdowns (uses `LEFT JOIN` + `OR storeId=0` in queries)

### jQuery Event Binding Convention

All jQuery event handlers in views **must** use delegated events via `$(document).on('event', '#selector', fn)` instead of `$('#selector').on('event', fn)`. Direct binding fails because elements load after script initialization.

### PHP 8.2 Compatibility

CodeIgniter 3 was not designed for PHP 8.2. Key constraints:
- `E_DEPRECATED` **must** be suppressed in `index.php` error_reporting (both dev and prod). CI3 uses dynamic properties extensively, which PHP 8.2 deprecates. If deprecation notices are displayed, they output HTML before session initialization → "headers already sent" → session failure → 500 errors.
- Node.js 22+ requires `NODE_OPTIONS=--openssl-legacy-provider` for Webpack 4 builds (already configured in `package.json` scripts).

### Flashdata Convention

Use **specific flashdata keys** per module (e.g., `login_error`, not generic `error`). The generic `error` key leaks across views because many views display `flashdata('error')`. The login controller uses `login_error`.

## Tech Stack Summary

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2 (XAMPP), CodeIgniter 3 |
| Database | MySQL (InnoDB) |
| CSS | Tailwind CSS 1.8.7 |
| JS | jQuery 3.5, Lodash, vanilla JS (Babel/ES6+) |
| Bundler | Webpack 4 |
| Server | Apache with .htaccess URL rewriting (XAMPP locally) |
