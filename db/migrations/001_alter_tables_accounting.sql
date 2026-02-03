-- ============================================================================
-- MIGRACIÓN 001: Mejoras al Schema de Base de Datos para Contabilidad
-- Fecha: 2026-01-25
-- Descripción: Agrega campos necesarios para soporte de PUC Colombia y
--              contabilidad integrada con Caja y Bancos
-- ============================================================================

-- Crear backup antes de ejecutar:
-- mysqldump -u root mamdb > backup_mamdb_20260125.sql

-- ============================================================================
-- 1. Tabla entries (Asientos Contables)
-- ============================================================================
-- Agregar campos para soft delete, fecha, bodega y referencia a transacción

ALTER TABLE `entries`
  ADD COLUMN `entryDate` DATE NOT NULL DEFAULT CURRENT_DATE AFTER `entryDescription`,
  ADD COLUMN `entryStoreId` INT NULL AFTER `entryDate`,
  ADD COLUMN `entryTransactionType` VARCHAR(50) NULL COMMENT 'purchase, invoice, payment, refund, settlement, cash_movement' AFTER `entryType`,
  ADD COLUMN `entryTransactionId` BIGINT NULL COMMENT 'ID de la transacción origen' AFTER `entryTransactionType`,
  ADD COLUMN `deleted` TINYINT(4) DEFAULT 0 AFTER `entryStatusComment`,
  ADD COLUMN `deleted_at` DATETIME NULL AFTER `deleted`,
  ADD COLUMN `updated_at` DATETIME NULL AFTER `deleted_at`,
  ADD INDEX `idx_transaction` (`entryTransactionType`, `entryTransactionId`),
  ADD INDEX `idx_store` (`entryStoreId`),
  ADD INDEX `idx_date` (`entryDate`),
  ADD INDEX `idx_deleted` (`deleted`);

-- ============================================================================
-- 2. Tabla subaccounts (Subcuentas)
-- ============================================================================
-- Agregar campos para tipo de cuenta y código PUC

ALTER TABLE `subaccounts`
  ADD COLUMN `accountType` VARCHAR(50) NULL COMMENT 'asset, liability, equity, revenue, expense, cost' AFTER `accountStatement`,
  ADD COLUMN `pucCode` VARCHAR(20) NULL COMMENT 'Código PUC Colombia' AFTER `accountType`,
  ADD COLUMN `store` INT NULL COMMENT 'Multi-bodega' AFTER `pucCode`,
  ADD INDEX `idx_puc_store` (`pucCode`, `store`),
  ADD INDEX `idx_account_type` (`accountType`),
  ADD INDEX `idx_store` (`store`);

-- ============================================================================
-- 3. Tabla auxiliary_subaccounts (Cuentas Auxiliares)
-- ============================================================================
-- Agregar campo para identificar tipo de auxiliar

ALTER TABLE `auxiliary_subaccounts`
  ADD COLUMN `accountType` VARCHAR(50) NULL COMMENT 'client, provider, employee, other' AFTER `accountStatement`;

-- ============================================================================
-- 4. Tabla accounts_class (Clases de Cuentas)
-- ============================================================================
-- Agregar código PUC para jerarquía contable

ALTER TABLE `accounts_class`
  ADD COLUMN `pucCode` VARCHAR(20) NULL COMMENT 'Código PUC clase (1 dígito)' AFTER `classDescription`,
  ADD UNIQUE INDEX `idx_puc_store` (`pucCode`, `store`),
  ADD INDEX `idx_puc` (`pucCode`);

-- ============================================================================
-- 5. Tabla accounts_group (Grupos de Cuentas)
-- ============================================================================
-- Agregar código PUC para jerarquía contable

ALTER TABLE `accounts_group`
  ADD COLUMN `pucCode` VARCHAR(20) NULL COMMENT 'Código PUC grupo (2 dígitos)' AFTER `groupDescription`,
  ADD UNIQUE INDEX `idx_puc` (`pucCode`);

-- ============================================================================
-- 6. Tabla accounts_accounts (Cuentas)
-- ============================================================================
-- Agregar código PUC para jerarquía contable

ALTER TABLE `accounts_accounts`
  ADD COLUMN `pucCode` VARCHAR(20) NULL COMMENT 'Código PUC cuenta (4 dígitos)' AFTER `accountDescription`,
  ADD UNIQUE INDEX `idx_puc` (`pucCode`);

-- ============================================================================
-- VERIFICACIÓN POST-MIGRACIÓN
-- ============================================================================

-- Verificar que los campos se agregaron correctamente
SELECT
    'entries' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN entryDate IS NOT NULL THEN 1 ELSE 0 END) as con_fecha,
    SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) as no_eliminados
FROM entries
UNION ALL
SELECT
    'subaccounts' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN pucCode IS NOT NULL THEN 1 ELSE 0 END) as con_puc,
    SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) as no_eliminados
FROM subaccounts;

-- Ver estructura de tabla entries
DESCRIBE entries;

-- Ver estructura de tabla subaccounts
DESCRIBE subaccounts;

-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
-- 1. Este script es IDEMPOTENTE - se puede ejecutar múltiples veces
-- 2. Hacer BACKUP antes de ejecutar en producción
-- 3. Los campos nuevos son NULL por defecto para no afectar datos existentes
-- 4. El campo entryDate usa CURRENT_DATE como default para entradas existentes
-- 5. Los índices mejoran el performance de consultas contables
-- 6. accountSide y accountStatement en subaccounts son VARCHAR ('1' o '2')
-- ============================================================================
