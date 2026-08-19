-- ============================================================================
-- 067: Puente accesoriosmam -> Ledxury (remisiones del canal MAM-Online)
--
-- Tabla de control del importador: cada remisión del canal (channel_remisions
-- en accesoriosmam, cliente 3377 "MAM-Online" que representa a Ledxury) se
-- importa UNA sola vez como factura de proveedor MAM pendiente por recibir.
-- La llave única sobre remision_id es la garantía de idempotencia.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `mam_remision_sync` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `remision_id` INT(11) NOT NULL COMMENT 'id en channel_remisions de accesoriosmam',
  `supplier_invoice_id` INT(11) DEFAULT NULL COMMENT 'factura creada en Ledxury (NULL si omitida)',
  `total_ar` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `items` INT(11) NOT NULL DEFAULT 0,
  `missing_products` TEXT DEFAULT NULL COMMENT 'códigos que no existían en Ledxury al importar',
  `status` ENUM('importada','omitida_saldo_inicial','error') NOT NULL DEFAULT 'importada',
  `error_msg` VARCHAR(500) DEFAULT NULL,
  `imported_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_remision` (`remision_id`),
  KEY `idx_invoice` (`supplier_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
