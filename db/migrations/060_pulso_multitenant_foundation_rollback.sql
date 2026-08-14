-- =====================================================================
-- Rollback de Migration 060: Pulso Multi-Tenant Foundation
-- =====================================================================
-- Revierte todos los cambios de 060.
-- USAR SOLO si el migration causó problemas.
-- =====================================================================

SET FOREIGN_KEY_CHECKS=0;

-- Comercial
ALTER TABLE invoices             DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE invoice_details      DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE invoice_discounts    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE budgets              DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE budget_detail        DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE payments             DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE refunds              DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE refund_details       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE credit_notes         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE credit_note_details  DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE vouchers             DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE noinvoices           DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE nopayments           DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Clientes
ALTER TABLE clients                       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE client_tokens                 DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE customer_credits              DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE customer_credit_applications  DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE payment_agreements            DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE payment_agreement_installments DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE collection_activities         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE recovery_campaigns            DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE campaign_clients              DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Productos
ALTER TABLE products              DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE inventory             DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE inventories           DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE inventory_adjustments DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE counts                DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE count_details         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE count_1_details       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE count_2_details       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE count_assignments     DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE final_count_details   DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE transfers             DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE transfer_details      DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE product_providers     DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE product_families      DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE product_section       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE product_datasheets    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE products_labels_values DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE datasheets_labels     DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE catalogues            DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE catalogue_details     DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE catalog_overrides     DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE promopacks            DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE promopacks_details    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE promopurchase         DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Compras
ALTER TABLE providers                 DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE supplier_invoices         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE supplier_invoice_details  DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE supplier_orders           DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE supplier_order_details    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE supplier_payments         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE supplier_expenses         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE purchases                 DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE purchase_detail           DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE import_landed_costs       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE import_receiving          DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Stores y contabilidad
ALTER TABLE stores                DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE entries               DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE accounting_periods    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE accounting_settings   DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE auxiliary_subaccounts DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE cost_centers          DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE account_statement     DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE cashboxes             DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE cashbox_closures      DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE cash_movements        DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE bank_accounts         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE bank_statement_lines  DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE bank_reconciliations  DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Gastos
ALTER TABLE expenses          DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE expense_categories DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE expense_records   DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- RRHH
ALTER TABLE employee_advances              DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE employee_advance_installments  DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE settlement_advance_payments    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE bonus_calculations             DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE departments                    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE department_kpis                DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE sales_goal                     DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE company_goals                  DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE tracking_weekly                DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE tracking_weekly_extras         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE cierre_mensual                 DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Logística
ALTER TABLE shipping_guides          DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE shipping_tracking_events DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Bots/CRM
ALTER TABLE bot_imports         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE bot_conversations   DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE bot_messages        DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE ai_conversations    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE ai_messages         DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE crm_leads           DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE crm_activities      DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE crm_stage_log       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE public_leads        DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE vendor_routes       DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE vendor_route_stops  DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE vendor_route_log    DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE chat_messages       DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Logs
ALTER TABLE notifications   DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE user_messages   DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE logs            DROP INDEX idx_tenant, DROP COLUMN tenant_id;
ALTER TABLE statement_logs  DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Otros
ALTER TABLE advertising  DROP INDEX idx_tenant, DROP COLUMN tenant_id;

-- Users
ALTER TABLE users
    DROP INDEX idx_tenant,
    DROP INDEX idx_platform_admin,
    DROP COLUMN is_platform_admin,
    DROP COLUMN tenant_id;

-- Drop tablas nuevas
DROP TABLE IF EXISTS tenant_invoice_counters;
DROP TABLE IF EXISTS tenants;

SET FOREIGN_KEY_CHECKS=1;
