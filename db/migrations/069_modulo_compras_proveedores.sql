-- ============================================================================
-- 069: Módulo de compras/proveedores (portado de stockaccessories.co)
--
-- Reemplaza el flujo de supplier_invoices por un módulo completo:
--   provider_invoices        facturas de proveedor (en_transito → open → paid)
--   provider_invoice_items   detalle por producto
--   provider_invoice_import_costs  costos de importación (flete/aduana/nacionalización)
--   provider_payments        pagos (desde cajas/bancos de Ledxury)
--   provider_advances        anticipos a proveedor + aplicaciones
--   provider_product_map     mapeo SKU proveedor → código propio (imports)
--   purchase_orders / purchase_order_items  órdenes de compra (fase 2)
--
-- Adaptaciones vs stockaccessories:
--   · Moneda base COP (allá era USD): columnas *_base en vez de *_usd, default
--     currency 'COP'. Multi-moneda se conserva (USD/RMB para importaciones de
--     China): exchange_rate = pesos por unidad de la moneda extranjera.
--   · Pagos y anticipos salen de la tesorería de Ledxury: source_type
--     ('caja'|'banco') + source_id (cashboxes/bank_accounts), no cash_accounts.
--   · CREATE TABLE IF NOT EXISTS (idempotente), utf8mb4.
--
-- supplier_invoices NO se toca: queda como histórico; las 3 facturas vivas se
-- migran con db/scripts/migrar_a_provider_invoices.php.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `provider_invoices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `inv_code` VARCHAR(50) NOT NULL,
  `provider_id` INT(11) NOT NULL,
  `po_id` INT(11) DEFAULT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE DEFAULT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'COP',
  `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `withholding` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `import_freight` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `import_customs` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `import_nationalization` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `landed_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('en_transito','open','paid_partial','paid','cancelled') NOT NULL DEFAULT 'open',
  `received_at` DATETIME DEFAULT NULL,
  `received_by` VARCHAR(100) DEFAULT NULL,
  `received_store_id` INT(11) DEFAULT NULL,
  `origin_ref` VARCHAR(100) DEFAULT NULL COMMENT 'referencia externa (ej. remision del canal en accesoriosmam)',
  `notes` TEXT DEFAULT NULL,
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `financing_pct` DECIMAL(6,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_code` (`inv_code`),
  KEY `idx_provider_status` (`provider_id`,`status`),
  KEY `idx_issue_date` (`issue_date`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_po` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `provider_invoice_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `provider_invoice_id` INT(11) NOT NULL,
  `product_id` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  `cbm` DECIMAL(12,4) DEFAULT NULL,
  `prev_cost` DECIMAL(14,4) DEFAULT NULL,
  `prev_price` DECIMAL(14,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pii_invoice` (`provider_invoice_id`),
  KEY `idx_pii_product` (`product_id`),
  CONSTRAINT `fk_pii_invoice` FOREIGN KEY (`provider_invoice_id`) REFERENCES `provider_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `provider_invoice_import_costs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `provider_invoice_id` INT(11) NOT NULL,
  `concept` VARCHAR(40) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `amount_base` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `alloc_basis` VARCHAR(10) NOT NULL DEFAULT 'value',
  `paid_source_type` VARCHAR(10) DEFAULT NULL,
  `paid_source_id` INT(11) DEFAULT NULL,
  `entry_id` INT(11) DEFAULT NULL,
  `created_by` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pic_invoice` (`provider_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `provider_payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pay_code` VARCHAR(50) NOT NULL,
  `invoice_id` INT(11) NOT NULL,
  `provider_id` INT(11) NOT NULL,
  `pay_date` DATE NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'COP',
  `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount_invoice_currency` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `source_type` ENUM('caja','banco') DEFAULT NULL,
  `source_id` INT(11) DEFAULT NULL,
  `cash_movement_id` INT(11) DEFAULT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `fx_diff` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME DEFAULT NULL,
  `deleted_by` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_provider_date` (`provider_id`,`pay_date`),
  KEY `idx_pay_date` (`pay_date`),
  KEY `idx_pp_source` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `provider_advances` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `adv_code` VARCHAR(30) NOT NULL,
  `provider_id` INT(11) NOT NULL,
  `po_id` INT(11) DEFAULT NULL,
  `pay_date` DATE NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'COP',
  `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `amount` DECIMAL(15,2) NOT NULL,
  `amount_base` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `applied_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `source_type` ENUM('caja','banco') DEFAULT NULL,
  `source_id` INT(11) DEFAULT NULL,
  `cash_movement_id` INT(11) DEFAULT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('open','applied','refunded') NOT NULL DEFAULT 'open',
  `notes` TEXT DEFAULT NULL,
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  `updated_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adv_code` (`adv_code`),
  KEY `idx_provider` (`provider_id`),
  KEY `idx_po` (`po_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `provider_advance_applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `advance_id` INT(11) NOT NULL,
  `invoice_id` INT(11) NOT NULL,
  `amount_base` DECIMAL(15,2) NOT NULL,
  `applied_at` DATETIME DEFAULT current_timestamp(),
  `created_by` VARCHAR(100) DEFAULT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_advance` (`advance_id`),
  KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `provider_product_map` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `provider_id` INT(11) NOT NULL,
  `provider_sku` VARCHAR(80) NOT NULL,
  `provider_ref` VARCHAR(120) DEFAULT NULL,
  `product_id` VARCHAR(50) NOT NULL,
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_sku` (`provider_id`,`provider_sku`),
  KEY `idx_provider` (`provider_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_code` VARCHAR(50) NOT NULL,
  `provider_id` INT(11) NOT NULL,
  `store_id` INT(11) NOT NULL,
  `order_date` DATE NOT NULL,
  `expected_date` DATE DEFAULT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'COP',
  `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `status` ENUM('draft','sent','received_partial','received_full','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_po_code` (`po_code`),
  KEY `idx_provider` (`provider_id`),
  KEY `idx_status` (`status`,`order_date`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `product_id` VARCHAR(50) NOT NULL,
  `qty_ordered` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
  `qty_received` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
  `cost_unit` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `total_line` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `notes` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po` (`po_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
