-- =====================================================================
-- Migration 065: tenant_id en tablas de Bots / Reglas de compra
-- =====================================================================
-- Fecha: 2026-06-19
-- Autor: Alex + Claude
-- Branch: alex/pulso-multitenant-fase1
--
-- Completa el aislamiento multi-tenant en las tablas que quedaron fuera de
-- la migración 060. Permite convertir Builderbot_model, Purchaserules_model
-- (y Dropshipping ya tenía tenant_id en sus tablas) a MY_Model.
--
-- SHARED (NO se tocan): delivery_type (catálogo de tipos de entrega, global).
--
-- Backfill automático: ADD COLUMN ... NOT NULL DEFAULT 1 → todo el histórico
-- queda en tenant 1 (Ledxury).
--
-- LOCAL-ONLY hasta el release de Pulso (igual que 060).
-- =====================================================================

SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE builderbot_configs
    ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1 AFTER id,
    ADD INDEX IF NOT EXISTS idx_tenant (tenant_id);

ALTER TABLE builderbot_messages
    ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1,
    ADD INDEX IF NOT EXISTS idx_tenant (tenant_id);

ALTER TABLE builderbot_webhooks
    ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1,
    ADD INDEX IF NOT EXISTS idx_tenant (tenant_id);

ALTER TABLE bot_conversation_tags
    ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1,
    ADD INDEX IF NOT EXISTS idx_tenant (tenant_id);

ALTER TABLE purchase_rules
    ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1,
    ADD INDEX IF NOT EXISTS idx_tenant (tenant_id);

SET FOREIGN_KEY_CHECKS=1;
