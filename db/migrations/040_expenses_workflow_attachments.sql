-- 040: Mejoras al módulo de gastos
--
-- Contexto: la migración 008 nunca se aplicó, así que `expense_records`
-- está incompleto (faltan columnas que el controller asume existen). Como
-- la tabla no tiene datos en local ni en prod, podemos completarla ahora
-- sin riesgo. Además, agrega soporte para el flujo causación + pago contra
-- proveedor (E.0.2) y para los adjuntos de comprobante (E.2).

-- ── Columnas faltantes de migración 008 (source caja/banco, IDs contables) ─
ALTER TABLE expense_records
    ADD COLUMN IF NOT EXISTS source_type ENUM('caja','banco') NULL COMMENT 'Origen del dinero al pagar',
    ADD COLUMN IF NOT EXISTS source_id INT(11) NULL COMMENT 'FK a cashboxes.idCashbox o bank_accounts.idBankAccount',
    ADD COLUMN IF NOT EXISTS voucher_reference VARCHAR(100) NULL COMMENT 'Número de comprobante / factura del proveedor',
    ADD COLUMN IF NOT EXISTS observations TEXT NULL COMMENT 'Observaciones adicionales',
    ADD COLUMN IF NOT EXISTS cash_movement_id INT(11) NULL COMMENT 'FK al movimiento de caja generado al pagar',
    ADD COLUMN IF NOT EXISTS entry_id INT(11) NULL COMMENT 'FK al asiento contable de causación (DR gasto / CR Proveedor)',
    ADD COLUMN IF NOT EXISTS payment_entry_id INT(11) NULL COMMENT 'FK al asiento de pago (DR Proveedor / CR Caja)',
    ADD COLUMN IF NOT EXISTS reversal_entry_id INT(11) NULL COMMENT 'FK al asiento de reversa cuando se anula',
    ADD COLUMN IF NOT EXISTS created_by VARCHAR(100) NULL COMMENT 'Usuario que creó el gasto';

-- ── E.2: tabla de adjuntos (factura PDF, foto del comprobante, etc) ───────
CREATE TABLE IF NOT EXISTS `expense_attachments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `expense_id` INT(11) NOT NULL,
    `filename` VARCHAR(255) NOT NULL COMMENT 'Nombre normalizado en disco',
    `original_name` VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo subido',
    `mime_type` VARCHAR(100) NULL,
    `size_bytes` INT(11) NULL,
    `path` VARCHAR(500) NOT NULL COMMENT 'Ruta relativa desde public/uploads/expenses',
    `uploaded_by` VARCHAR(100) NULL,
    `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `deleted` TINYINT(1) DEFAULT 0,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_expense` (`expense_id`),
    INDEX `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Comprobantes (PDF/foto) de cada gasto';
