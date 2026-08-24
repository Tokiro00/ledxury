-- ============================================================================
-- ARCHIVADA — NO APLICAR. Multi-tenant quedo descartado (decision de Alex,
-- reconfirmada el 22/08/2026: "no quiero usar tenant, dejemos eso archivado").
--
-- Produccion NO tiene tabla `tenants` ni columnas `tenant_id`. Aplicar esto
-- agrega tenant_id a ~105 tablas y rompe TODAS las consultas del sistema.
-- db/deploy.sh la rechaza a proposito. Se conserva solo como historia.
-- Ver db/recuperacion/RESTAURAR_PRODUCCION.md
-- ============================================================================
-- =====================================================================
-- Migration 060: Pulso Multi-Tenant Foundation
-- =====================================================================
-- Fecha: 2026-05-28
-- Autor: Alex + Claude
-- Branch: alex/pulso-multitenant-fase1
--
-- Convierte el ERP single-tenant en plataforma multi-tenant tipo Mastershop:
--   - Tabla `tenants` (Ledxury y MAM-Online como seed)
--   - Columna `tenant_id INT NOT NULL DEFAULT 1` en 105 tablas transaccionales
--   - Backfill automático: todo el histórico queda asignado a tenant 1 (Ledxury)
--   - `users.is_platform_admin` para usuarios cross-tenant (Alex, Jorge)
--   - `tenant_invoice_counters` para numeración independiente por tenant (Fase 2 lo usará)
--
-- NO se tocan tablas SHARED del sistema:
--   account_side, accounts_*, subaccounts, dane_municipalities,
--   delivery_type, paymentmethods, roles, role_permissions, tmp_providers
--
-- Estrategia: ALGORITHM=INSTANT donde MariaDB lo permite (MariaDB 10.4+).
-- Sin downtime esperado. Indices se crean con LOCK=NONE.
--
-- Rollback: db/migrations/060_pulso_multitenant_foundation_rollback.sql
-- =====================================================================

SET FOREIGN_KEY_CHECKS=0;

-- ---------------------------------------------------------------------
-- 1. Tabla `tenants`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tenants (
    id INT NOT NULL AUTO_INCREMENT,
    slug VARCHAR(40) NOT NULL,
    name VARCHAR(120) NOT NULL,
    nit VARCHAR(20) DEFAULT NULL,
    razon_social VARCHAR(200) DEFAULT NULL,
    inter_sucursal_id INT DEFAULT NULL COMMENT 'CodigoConvenioRemitente Inter',
    inter_pickup_address VARCHAR(200) DEFAULT NULL,
    inter_pickup_city VARCHAR(8) DEFAULT NULL COMMENT 'Código DANE',
    brand_primary VARCHAR(7) NOT NULL DEFAULT '#FF5A36',
    brand_secondary VARCHAR(7) NOT NULL DEFAULT '#FFF7EE',
    logo_url VARCHAR(255) DEFAULT NULL,
    invoice_template VARCHAR(50) NOT NULL DEFAULT 'pulso',
    invoice_account TEXT,
    invoice_support TEXT,
    bot_enabled TINYINT(1) NOT NULL DEFAULT 0,
    bot_api_key VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_slug (slug),
    KEY idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tenants (id, slug, name, nit, razon_social, brand_primary, brand_secondary, invoice_template, invoice_account, invoice_support, active, created_at, updated_at)
VALUES
  (1, 'ledxury', 'Ledxury', '901427578', 'MULTI ACCESORIOS MEDELLIN S.A.S.', '#FF5A36', '#FFF7EE', 'pulso',
   'Consignar en la cuenta de ahorros Bancolombia No. 00564017515 a nombre de DANIEL GARCIA.',
   'Por favor enviar soporte de pago al WhatsApp 604-4581795', 1, NOW(), NOW()),
  (2, 'mam-online', 'MAM-Online', NULL, NULL, '#1B7A47', '#F2FBF5', 'pulso',
   NULL, NULL, 0, NOW(), NOW());

-- ---------------------------------------------------------------------
-- 2. Contadores de numeración independiente (vacío, Fase 2 lo usará)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tenant_invoice_counters (
    tenant_id INT NOT NULL,
    doc_type VARCHAR(30) NOT NULL COMMENT 'invoice, budget, credit_note, refund, mam_return, etc.',
    last_number BIGINT NOT NULL DEFAULT 0,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (tenant_id, doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. Users: tenant_id + is_platform_admin
-- ---------------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER idUser,
    ADD COLUMN is_platform_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER tenant_id,
    ADD INDEX idx_tenant (tenant_id),
    ADD INDEX idx_platform_admin (is_platform_admin);

-- Alex Alzate como platform admin (confirmado). Jorge Cano pendiente de identificar.
UPDATE users SET is_platform_admin=1 WHERE idUser='71339095';

-- ---------------------------------------------------------------------
-- 4. tenant_id en tablas transaccionales (105 tablas)
--    Patrón: ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 + INDEX
--    Backfill automático: el DEFAULT 1 asigna todo el histórico a Ledxury
-- ---------------------------------------------------------------------

-- == COMERCIAL (13) ==
ALTER TABLE invoices             ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER idInvoice, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE invoice_details      ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE invoice_discounts    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE budgets              ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER idBudget, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE budget_detail        ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE payments             ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE refunds              ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE refund_details       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE credit_notes         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE credit_note_details  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE vouchers             ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE noinvoices           ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE nopayments           ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == CLIENTES Y CARTERA (9) ==
ALTER TABLE clients                       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER idClient, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE client_tokens                 ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE customer_credits              ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE customer_credit_applications  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE payment_agreements            ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE payment_agreement_installments ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE collection_activities         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE recovery_campaigns            ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE campaign_clients              ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == PRODUCTOS E INVENTARIO (24) ==
ALTER TABLE products              ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE inventory             ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE inventories           ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE inventory_adjustments ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE counts                ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE count_details         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE count_1_details       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE count_2_details       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE count_assignments     ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE final_count_details   ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE transfers             ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE transfer_details      ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE product_providers     ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE product_families      ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE product_section       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE product_datasheets    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE products_labels_values ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE datasheets_labels     ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE catalogues            ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE catalogue_details     ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE catalog_overrides     ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE promopacks            ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE promopacks_details    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE promopurchase         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == COMPRAS Y PROVEEDORES (11) ==
ALTER TABLE providers                 ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE supplier_invoices         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE supplier_invoice_details  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE supplier_orders           ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE supplier_order_details    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE supplier_payments         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE supplier_expenses         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE purchases                 ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE purchase_detail           ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE import_landed_costs       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE import_receiving          ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == STORES Y CONTABILIDAD (13) ==
ALTER TABLE stores                ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER idStore, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE entries               ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER entryID, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE accounting_periods    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE accounting_settings   ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE auxiliary_subaccounts ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE cost_centers          ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE account_statement     ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE cashboxes             ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE cashbox_closures      ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE cash_movements        ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE bank_accounts         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE bank_statement_lines  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE bank_reconciliations  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == GASTOS (3) ==
ALTER TABLE expenses          ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE expense_categories ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE expense_records   ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == RRHH Y KPIS (11) ==
ALTER TABLE employee_advances              ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE employee_advance_installments  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE settlement_advance_payments    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE bonus_calculations             ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE departments                    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE department_kpis                ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE sales_goal                     ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE company_goals                  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE tracking_weekly                ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE tracking_weekly_extras         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE cierre_mensual                 ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == LOGÍSTICA (2) ==
ALTER TABLE shipping_guides          ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE shipping_tracking_events ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == BOTS, CRM Y AGENTES (13) ==
ALTER TABLE bot_imports         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE bot_conversations   ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE bot_messages        ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ai_conversations    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ai_messages         ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE crm_leads           ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE crm_activities      ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE crm_stage_log       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE public_leads        ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE vendor_routes       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE vendor_route_stops  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE vendor_route_log    ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE chat_messages       ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == LOGS Y NOTIFICACIONES (4) ==
ALTER TABLE notifications   ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE user_messages   ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE logs            ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);
ALTER TABLE statement_logs  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

-- == OTROS (1) ==
ALTER TABLE advertising  ADD COLUMN tenant_id INT NOT NULL DEFAULT 1, ADD INDEX idx_tenant (tenant_id);

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================================
-- VERIFICACIÓN
-- =====================================================================
-- Después de ejecutar, validar:
--   SELECT COUNT(*) FROM information_schema.columns
--   WHERE TABLE_SCHEMA='mamdb' AND COLUMN_NAME='tenant_id';
--   -- Esperado: 106 (105 transaccionales + tabla users)
--
--   SELECT id, slug, name, brand_primary FROM tenants;
--   -- Esperado: 2 filas (ledxury, mam-online)
--
--   SELECT idUser, name, is_platform_admin FROM users WHERE is_platform_admin=1;
--   -- Esperado: Alex Alzate (71339095)
-- =====================================================================
