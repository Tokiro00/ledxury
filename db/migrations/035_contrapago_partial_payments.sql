-- 035: Anti-duplicados de hojas + pagos parciales de facturas Inter
-- Soporta el caso donde una factura Inter se cobra en múltiples pagos
-- (ej: Fra. 208540 cobrada parte en PAGO 5 y parte en PAGO 6)

-- 1) Hash único por hoja para evitar reimportar la misma
ALTER TABLE `contrapago_batches`
  ADD COLUMN `import_hash` VARCHAR(64) DEFAULT NULL AFTER `created_by`,
  ADD UNIQUE KEY `uk_import_hash` (`import_hash`);

-- 2) Status 'parcial' a facturas Inter
ALTER TABLE `contrapago_invoices`
  MODIFY COLUMN `status` ENUM('pendiente','parcial','descontada','pagada') NOT NULL DEFAULT 'pendiente';

-- 3) Tabla de pagos parciales de facturas Inter
-- Una factura Inter puede ser cobrada (compensada) en múltiples pagos contrapago.
-- Cada fila aquí registra: en qué pago contrapago se cobró cuánto de qué factura Inter.
CREATE TABLE IF NOT EXISTS `contrapago_invoice_payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` INT(11) NOT NULL COMMENT 'FK contrapago_invoices.id',
  `batch_id` INT(11) NOT NULL COMMENT 'FK contrapago_batches.id',
  `monto_cobrado` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `texto_observacion` TEXT DEFAULT NULL COMMENT 'Texto de observación que generó el vínculo',
  `created_by` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_batch` (`batch_id`),
  UNIQUE KEY `uk_invoice_batch` (`invoice_id`, `batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Pagos parciales o totales que compensan facturas Inter';
