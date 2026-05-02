-- ============================================================================
-- 031_contrapago_consolidated.sql
-- Consolidación de migrations sueltas que estaban en db/ root como SQL ad-hoc:
--   - contrapago_tables.sql       (tablas batches + payments)
--   - contrapago_invoices.sql     (tablas invoices + invoice_items)
--   - contrapago_company.sql      (ALTER: agregar columna company a 2 tablas)
--   - contrapago_duplicates.sql   (ALTER: estado 'duplicada' + duplicate_of_id)
--
-- Si tu DB ya las tiene corridas (probablemente, en producción), este archivo
-- es idempotente: CREATE TABLE IF NOT EXISTS y los ALTERs fallan silenciosos
-- si la columna ya existe (manejado vía procedure abajo).
-- ============================================================================

-- ── Tablas base ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contrapago_batches` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `sheet_name` VARCHAR(100) DEFAULT NULL,
  `total_guias` INT(11) DEFAULT 0,
  `total_valor` DECIMAL(15,2) DEFAULT 0.00,
  `fecha_pago` DATE DEFAULT NULL,
  `banco` VARCHAR(100) DEFAULT NULL,
  `matched` INT(11) DEFAULT 0,
  `unmatched` INT(11) DEFAULT 0,
  `cash_movement_id` INT(11) DEFAULT NULL,
  `status` ENUM('importado','conciliado','registrado') DEFAULT 'importado',
  `created_by` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contrapago_payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `batch_id` INT(11) NOT NULL,
  `numeroGuia` VARCHAR(50) NOT NULL,
  `fechaVenta` DATETIME DEFAULT NULL,
  `valorTotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `nombreDestinatario` VARCHAR(255) DEFAULT NULL,
  `conciliacion` VARCHAR(50) DEFAULT NULL,
  `fechaPago` DATE DEFAULT NULL,
  `valorPago` DECIMAL(15,2) DEFAULT NULL,
  `banco` VARCHAR(100) DEFAULT NULL,
  `observacion` TEXT DEFAULT NULL,
  `shipping_guide_id` INT(11) DEFAULT NULL,
  `invoice_id` INT(11) DEFAULT NULL,
  `status` ENUM('pendiente','conciliado','sin_match','duplicada') DEFAULT 'pendiente',
  `created_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_guia` (`numeroGuia`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Facturas Interrapidísimo a MAM/Ledxury ───────────────────────────────
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

-- ── ALTERs idempotentes (procedure helper) ───────────────────────────────
-- Agrega columna solo si no existe ya. Soluciona el problema de
-- "ALTER TABLE ... ADD COLUMN" que falla si la columna ya existe.

DROP PROCEDURE IF EXISTS _add_col_if_missing;

DELIMITER //
CREATE PROCEDURE _add_col_if_missing(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- contrapago_payments: company, duplicate_of_id
CALL _add_col_if_missing('contrapago_payments', 'company',
    "`company` ENUM('ledxury','mam') DEFAULT 'ledxury' AFTER `invoice_id`, ADD KEY `idx_company` (`company`)");
CALL _add_col_if_missing('contrapago_payments', 'duplicate_of_id',
    "`duplicate_of_id` INT(11) DEFAULT NULL AFTER `company`, ADD KEY `idx_duplicate` (`duplicate_of_id`)");

-- contrapago_invoice_items: company
CALL _add_col_if_missing('contrapago_invoice_items', 'company',
    "`company` ENUM('ledxury','mam') DEFAULT 'ledxury' AFTER `invoice_system_id`, ADD KEY `idx_company` (`company`)");

-- Limpiar el helper (no queda en producción)
DROP PROCEDURE IF EXISTS _add_col_if_missing;
