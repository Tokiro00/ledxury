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

SQL migration scripts are in `db/migrations/`, numbered sequentially (001–008). They must be executed manually in order against a MySQL database. They implement the Colombian PUC (Plan Único de Cuentas) accounting chart of accounts, plus the expenses module (008).

## Architecture

### MVC Pattern (CodeIgniter 3)

- **Controllers** (`application/controllers/sisvent/`): Organized by domain — `commercial/`, `accounting/`, `admin/`, `business/`, `store/`
- **Models** (`application/models/`): ~31 models using CI Query Builder. Each model maps to a domain entity (invoices, entries, payments, expense records, expense categories, etc.)
- **Views** (`application/views/sisvent/`): PHP templates. Shared layouts in `layouts/` (meta_header, navbar, sidemenu, footer)

### URL Routing

Standard CI3 routing: `base_url/sisvent/{subdirectory}/{controller}/{method}`. Default controller is `welcome`. No custom route overrides — the directory structure IS the routing.

### Auto-loaded Resources (`application/config/autoload.php`)

- **Libraries**: database, session, backend_lib, form_validation, email, user_agent
- **Helpers**: url, login, mam
- **Models**: outh_model, logs_model

All other models are loaded per-controller in `__construct()`.

### Key Libraries

- **`Backend_lib`** (`application/libraries/Backend_lib.php`): Authentication guard. Every controller calls `$this->backend_lib->control()` in its constructor. Accepts optional `$roles` array to restrict by role.
- **`Accounting_lib`** (`application/libraries/Accounting_lib.php`): Centralized journal entry generation for cash/bank movements, invoice settlements, payment processing, and operational expenses. Enforces accounting period closure checks. Designed for multi-store (multi-bodega) operation.
- **`mam_helper`** (`application/helpers/mam_helper.php`): Core utility functions — asset paths, input sanitization, email sending, partner privilege checks, formatting.

### Authentication & Roles

Session-based auth. User session data at `$this->session->userdata('user_data')` contains `uname`, `role`, `admin_store`. Roles: 1 (admin/full access), 2 (gerente/manager), 3 (vendedor/sales), 4 (contador/accountant).

### Accounting Hierarchy (PUC Colombia)

```
accounts_class → accounts_group → accounts_accounts → subaccounts → auxiliary_subaccounts
```

Financial transactions must go through `Accounting_lib` to generate proper journal entries. Accounting periods can be closed, blocking further entries for that month/store.

### Controller Pattern

```php
class Example extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->backend_lib->control();           // Auth check (or ->control([4]) for admin-only)
        $this->load->model('example_model');      // Load needed models
        $this->load->library('accounting_lib');   // Load if doing accounting ops
    }
}
```

### Export Capabilities

- Excel (.xlsx) via PHPSpreadsheet (`vendor/phpoffice/phpspreadsheet`)
- PDF via mPDF (`vendor/mpdf/mpdf`)

### Expenses Module

- **Tables**: `expense_categories` (categories linked to PUC subaccounts) and `expense_records` (operational expenses)
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
