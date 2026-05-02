-- Cuentas por cobrar entre compañías (Ledxury <-> MAM)
-- Cada movimiento representa un cobro o un pago entre las dos empresas

CREATE TABLE IF NOT EXISTS `intercompany_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('cobro_pendiente','pago_recibido','ajuste') NOT NULL DEFAULT 'cobro_pendiente',
    -- cobro_pendiente: Ledxury pagó algo de MAM (fletes) o MAM cobró por Ledxury (contrapagos) = MAM debe a Ledxury (o al revés)
    -- pago_recibido: MAM transfirió a Ledxury (o viceversa) y baja el saldo
    -- ajuste: corrección manual
  `concepto` ENUM('flete_mam','contrapago_mam','transferencia','ajuste_manual') NOT NULL DEFAULT 'flete_mam',
  `direccion` ENUM('mam_debe_ledxury','ledxury_debe_mam') NOT NULL DEFAULT 'mam_debe_ledxury',
  `monto` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `fecha` DATE NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `numero_movimiento` VARCHAR(50) DEFAULT NULL COMMENT 'Referencia bancaria o documento',
  -- Vínculos opcionales para trazabilidad:
  `contrapago_batch_id` INT(11) DEFAULT NULL COMMENT 'Si viene de un lote de pagos contrapago',
  `contrapago_invoice_id` INT(11) DEFAULT NULL COMMENT 'Si viene de una factura Inter',
  `cash_movement_id` INT(11) DEFAULT NULL COMMENT 'Movimiento bancario asociado',
  `bank_account_id` INT(11) DEFAULT NULL,
  `status` ENUM('activo','anulado') NOT NULL DEFAULT 'activo',
  `created_by` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  `deleted_by` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_concepto` (`concepto`),
  KEY `idx_status` (`status`),
  KEY `idx_batch` (`contrapago_batch_id`),
  KEY `idx_invoice` (`contrapago_invoice_id`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT 'Cuentas por cobrar/pagar entre Ledxury y MAM';
