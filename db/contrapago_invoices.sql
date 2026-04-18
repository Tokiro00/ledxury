-- Facturas de Interrapidisimo a MAM (por fletes de envios gratis)
CREATE TABLE IF NOT EXISTS `contrapago_invoices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `numero_factura` VARCHAR(50) NOT NULL,
  `fecha_corte` DATE DEFAULT NULL,
  `nit` VARCHAR(20) DEFAULT NULL,
  `razon_social` VARCHAR(255) DEFAULT NULL,
  `total_guias` INT(11) DEFAULT 0,
  `valor_transporte` DECIMAL(15,2) DEFAULT 0.00,
  `valor_seguro` DECIMAL(15,2) DEFAULT 0.00,
  `valor_adicionales` DECIMAL(15,2) DEFAULT 0.00,
  `valor_total` DECIMAL(15,2) DEFAULT 0.00,
  `status` ENUM('pendiente','descontada','pagada') DEFAULT 'pendiente',
  `descontada_en_batch_id` INT(11) DEFAULT NULL,
  `descuento_observacion` TEXT DEFAULT NULL,
  `filename` VARCHAR(255) DEFAULT NULL,
  `created_by` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_factura` (`numero_factura`),
  KEY `idx_status` (`status`),
  KEY `idx_batch` (`descontada_en_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contrapago_invoice_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` INT(11) NOT NULL,
  `numero_guia` VARCHAR(50) NOT NULL,
  `fecha_grabacion` DATETIME DEFAULT NULL,
  `ciudad_origen` VARCHAR(255) DEFAULT NULL,
  `ciudad_destino` VARCHAR(255) DEFAULT NULL,
  `peso` DECIMAL(10,2) DEFAULT 0,
  `valor_comercial` DECIMAL(15,2) DEFAULT 0,
  `valor_adicionales` DECIMAL(15,2) DEFAULT 0,
  `valor_transporte` DECIMAL(15,2) DEFAULT 0,
  `valor_prima` DECIMAL(15,2) DEFAULT 0,
  `valor_total` DECIMAL(15,2) DEFAULT 0,
  `shipping_guide_id` INT(11) DEFAULT NULL,
  `invoice_system_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_guia` (`numero_guia`),
  KEY `idx_shipping_guide` (`shipping_guide_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
