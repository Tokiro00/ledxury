-- 045: Completa schema de cajas, bancos y movimientos
--
-- Mismo patrón que las migraciones de gastos (040): la migración 004
-- (cashbanks_tables.sql) nunca se aplicó. El código asume columnas que
-- la BD no tiene. Errores en producción al crear caja:
--   "Unknown column 'type' in 'field list'" — Cashboxes_model::save()
--
-- Esta migración agrega idempotentemente todas las columnas que el
-- código existente usa pero no están en el schema.

-- ── 1. cashboxes ──────────────────────────────────────────────────────────
ALTER TABLE cashboxes
    ADD COLUMN IF NOT EXISTS `type` VARCHAR(50) NULL COMMENT 'principal|secundaria|chica',
    ADD COLUMN IF NOT EXISTS `subaccountId` INT(11) NULL COMMENT 'FK subaccounts.id (PUC 1105)';

-- Permitir el estado 'arqueo' además de abierta/cerrada
ALTER TABLE cashboxes
    MODIFY COLUMN `status` ENUM('abierta','cerrada','arqueo') NULL DEFAULT 'cerrada';

-- ── 2. bank_accounts ─────────────────────────────────────────────────────
ALTER TABLE bank_accounts
    ADD COLUMN IF NOT EXISTS `subaccountId` INT(11) NULL COMMENT 'FK subaccounts.id (PUC 1110)',
    ADD COLUMN IF NOT EXISTS `currency` VARCHAR(10) NULL DEFAULT 'COP';

-- ── 3. cash_movements ────────────────────────────────────────────────────
-- Faltan: destinationType, destinationId (transferencias entre cajas/bancos),
-- executedBy (quién registró el movimiento), authorizedBy (quién autorizó).
ALTER TABLE cash_movements
    ADD COLUMN IF NOT EXISTS `destinationType` VARCHAR(50) NULL COMMENT 'caja|banco — solo si movementType=transferencia',
    ADD COLUMN IF NOT EXISTS `destinationId`   INT(11) NULL,
    ADD COLUMN IF NOT EXISTS `executedBy`      VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS `authorizedBy`    VARCHAR(100) NULL;
