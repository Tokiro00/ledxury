-- ============================================================================
-- CREATE TABLE statements for 23 new tables referenced by CodeIgniter models
-- Generated: 2026-04-02
-- Based on analysis of model files on remote server
-- ============================================================================

-- ============================================================================
-- 1. bank_accounts (Bankaccounts_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `idBankAccount` int(11) NOT NULL AUTO_INCREMENT,
  `bankName` varchar(255) NOT NULL,
  `accountNumber` varchar(100) NOT NULL,
  `ownerName` varchar(255) DEFAULT NULL,
  `accountType` varchar(50) DEFAULT 'ahorros',
  `currentBalance` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'activa',
  `storeId` int(11) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idBankAccount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 2. bank_reconciliations (Bankreconciliations_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `idReconciliation` int(11) NOT NULL AUTO_INCREMENT,
  `bankAccountId` int(11) NOT NULL,
  `reconciliationDate` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendiente',
  `authorizedBy` varchar(100) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idReconciliation`),
  KEY `idx_bankAccountId` (`bankAccountId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 3. bank_statement_lines (Bankstatementlines_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bank_statement_lines` (
  `idLine` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliationId` int(11) NOT NULL,
  `rowNumber` int(11) DEFAULT 0,
  `transactionDate` datetime DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `matchStatus` varchar(50) DEFAULT 'pendiente',
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idLine`),
  KEY `idx_reconciliationId` (`reconciliationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 4. cash_movements (Cashmovements_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `cash_movements` (
  `idMovement` int(11) NOT NULL AUTO_INCREMENT,
  `sourceType` varchar(50) NOT NULL COMMENT 'caja or banco',
  `sourceId` int(11) NOT NULL,
  `movementType` varchar(50) NOT NULL COMMENT 'ingreso, egreso, apertura, cierre, transferencia',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `concept` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `documentNumber` varchar(100) DEFAULT NULL,
  `movementDate` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'activo',
  `referenceType` varchar(50) DEFAULT NULL,
  `referenceId` int(11) DEFAULT NULL,
  `entryId` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idMovement`),
  KEY `idx_source` (`sourceType`, `sourceId`),
  KEY `idx_movementDate` (`movementDate`),
  KEY `idx_reference` (`referenceType`, `referenceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 5. cashbox_closures (Cashboxclosures_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `cashbox_closures` (
  `idClosure` int(11) NOT NULL AUTO_INCREMENT,
  `cashboxId` int(11) NOT NULL,
  `closureDate` datetime DEFAULT NULL,
  `openingBalance` decimal(15,2) DEFAULT 0.00,
  `totalIngress` decimal(15,2) DEFAULT 0.00,
  `totalEgress` decimal(15,2) DEFAULT 0.00,
  `expectedBalance` decimal(15,2) DEFAULT 0.00,
  `actualBalance` decimal(15,2) DEFAULT 0.00,
  `difference` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pendiente',
  `authorizedBy` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idClosure`),
  KEY `idx_cashboxId` (`cashboxId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 6. accounting_periods (Accountingperiods_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `accounting_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storeId` int(11) DEFAULT NULL,
  `periodYear` int(4) NOT NULL,
  `periodMonth` int(2) NOT NULL,
  `periodType` varchar(20) DEFAULT 'monthly',
  `status` varchar(20) DEFAULT 'open',
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `closingEntryId` int(11) DEFAULT NULL,
  `closedBy` varchar(100) DEFAULT NULL,
  `closedAt` datetime DEFAULT NULL,
  `reopenedBy` varchar(100) DEFAULT NULL,
  `reopenedAt` datetime DEFAULT NULL,
  `totalIncome` decimal(15,2) DEFAULT 0.00,
  `totalExpenses` decimal(15,2) DEFAULT 0.00,
  `totalCosts` decimal(15,2) DEFAULT 0.00,
  `netIncome` decimal(15,2) DEFAULT 0.00,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_year_month` (`periodYear`, `periodMonth`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 7. accounting_settings (Accountingsettings_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `accounting_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `subaccount_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 8. cost_centers (Costcenters_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `cost_centers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 9. departments (Departments_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `leader_user_id` varchar(100) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 10. department_kpis (Departments_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `department_kpis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `kpi_key` varchar(100) DEFAULT NULL,
  `target_value` decimal(15,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 11. bonus_calculations (Departments_model.php - getBonusesByDepartment, saveBonus)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bonus_calculations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `quarter` int(1) NOT NULL,
  `total_bonus` decimal(15,2) DEFAULT 0.00,
  `details` text DEFAULT NULL,
  `calculated_by` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendiente',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_year_quarter` (`year`, `quarter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 12. expense_categories (Expensecategories_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `accounting_account_id` int(11) DEFAULT NULL,
  `accounting_subaccount_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 13. expense_records (Expenserecords_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `expense_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expense_date` date DEFAULT NULL,
  `expense_category_id` int(11) DEFAULT NULL,
  `provider_id` varchar(100) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendiente',
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_expense_category_id` (`expense_category_id`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 14. product_providers (Productproviders_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `product_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` varchar(100) NOT NULL,
  `providerId` varchar(100) NOT NULL,
  `providerSku` varchar(100) DEFAULT NULL,
  `providerPrice` decimal(15,2) DEFAULT 0.00,
  `leadTimeDays` int(11) DEFAULT 0,
  `minOrderQty` int(11) DEFAULT 1,
  `priority` int(11) DEFAULT 1,
  `isDefault` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_productId` (`productId`),
  KEY `idx_providerId` (`providerId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 15. shipping_guides (Shipping_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `shipping_guides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoiceId` int(11) DEFAULT NULL,
  `storeId` int(11) DEFAULT NULL,
  `numeroPreenvio` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'creado',
  `estadoGuia` int(11) DEFAULT 0,
  `estadoNombre` varchar(100) DEFAULT NULL,
  `fechaEstado` datetime DEFAULT NULL,
  `ciudadDestinoNombre` varchar(255) DEFAULT NULL,
  `ciudadDestinoCodigo` varchar(50) DEFAULT NULL,
  `ciudadOrigenNombre` varchar(255) DEFAULT NULL,
  `ciudadOrigenCodigo` varchar(50) DEFAULT NULL,
  `destinatarioNombre` varchar(255) DEFAULT NULL,
  `destinatarioDocumento` varchar(50) DEFAULT NULL,
  `destinatarioDireccion` varchar(500) DEFAULT NULL,
  `destinatarioTelefono` varchar(50) DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT 0.00,
  `unidades` int(11) DEFAULT 1,
  `valorDeclarado` decimal(15,2) DEFAULT 0.00,
  `valorTotal` decimal(15,2) DEFAULT 0.00,
  `isContrapago` tinyint(1) DEFAULT 0,
  `contrapagoCost` decimal(15,2) DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `estimatedDelivery` date DEFAULT NULL,
  `actualDelivery` datetime DEFAULT NULL,
  `lastTrackingCheck` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoiceId` (`invoiceId`),
  KEY `idx_storeId` (`storeId`),
  KEY `idx_numeroPreenvio` (`numeroPreenvio`),
  KEY `idx_estadoGuia` (`estadoGuia`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 16. shipping_tracking_events (Shipping_model.php - getTrackingEvents, addTrackingEvent)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `shipping_tracking_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guideId` int(11) NOT NULL,
  `eventDate` datetime DEFAULT NULL,
  `statusCode` int(11) DEFAULT NULL,
  `statusName` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_guideId` (`guideId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 17. dane_municipalities (Shipping_model.php - searchMunicipality, seedMunicipalities)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `dane_municipalities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `shortName` varchar(255) DEFAULT NULL,
  `departmentCode` varchar(10) DEFAULT NULL,
  `departmentName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shortName` (`shortName`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 18. supplier_invoices (Supplierbills_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `supplier_invoices` (
  `idSupplierInvoice` int(11) NOT NULL AUTO_INCREMENT,
  `providerId` varchar(100) NOT NULL,
  `invoiceNumber` varchar(100) DEFAULT NULL,
  `invoiceDate` date DEFAULT NULL,
  `dueDate` date DEFAULT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax` decimal(15,2) DEFAULT 0.00,
  `paidAmount` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pendiente',
  `storeId` int(11) DEFAULT NULL,
  `received` tinyint(1) DEFAULT 0,
  `received_at` datetime DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idSupplierInvoice`),
  KEY `idx_providerId` (`providerId`),
  KEY `idx_status` (`status`),
  KEY `idx_dueDate` (`dueDate`),
  KEY `idx_storeId` (`storeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 19. supplier_invoice_details (Supplierinvoicedetails_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `supplier_invoice_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplierInvoiceId` int(11) NOT NULL,
  `productId` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `unitPrice` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_supplierInvoiceId` (`supplierInvoiceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 20. supplier_orders (Supplierorders_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `supplier_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderNumber` varchar(50) DEFAULT NULL,
  `providerId` varchar(100) NOT NULL,
  `storeId` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendiente',
  `orderDate` date DEFAULT NULL,
  `expectedDate` date DEFAULT NULL,
  `receivedDate` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_providerId` (`providerId`),
  KEY `idx_storeId` (`storeId`),
  KEY `idx_status` (`status`),
  KEY `idx_orderNumber` (`orderNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 21. supplier_order_details (Supplierorders_model.php - getDetails, saveDetail, saveBatch)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `supplier_order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` int(11) NOT NULL,
  `productId` varchar(100) NOT NULL,
  `quantityOrdered` int(11) DEFAULT 0,
  `quantityReceived` int(11) DEFAULT 0,
  `unitCost` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `idx_orderId` (`orderId`),
  KEY `idx_productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 22. supplier_payments (Supplierpayments_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `idSupplierPayment` int(11) NOT NULL AUTO_INCREMENT,
  `providerId` varchar(100) NOT NULL,
  `supplierInvoiceId` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paymentDate` date DEFAULT NULL,
  `paymentMethod` varchar(50) DEFAULT NULL,
  `referenceNumber` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ejecutado',
  `notes` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`idSupplierPayment`),
  KEY `idx_providerId` (`providerId`),
  KEY `idx_supplierInvoiceId` (`supplierInvoiceId`),
  KEY `idx_paymentDate` (`paymentDate`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 23. tracking_weekly (Tracking_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tracking_weekly` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `week` int(2) NOT NULL,
  `vendorId` varchar(100) NOT NULL,
  `ventas` decimal(15,2) DEFAULT 0.00,
  `cobros` decimal(15,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_month_week_vendor` (`year`, `month`, `week`, `vendorId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 24. tracking_weekly_extras (Tracking_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tracking_weekly_extras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `week` int(2) NOT NULL,
  `cartera_total` decimal(15,2) DEFAULT 0.00,
  `inventario` decimal(15,2) DEFAULT 0.00,
  `gastos_semana` decimal(15,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_month_week` (`year`, `month`, `week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 25. cierre_mensual (Tracking_model.php)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `cierre_mensual` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `ventas_brutas` decimal(15,2) DEFAULT 0.00,
  `cobros_total` decimal(15,2) DEFAULT 0.00,
  `cartera_total` decimal(15,2) DEFAULT 0.00,
  `inventario` decimal(15,2) DEFAULT 0.00,
  `gastos_operacionales` decimal(15,2) DEFAULT 0.00,
  `utilidad_neta` decimal(15,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_month` (`year`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- ALTER: Add cost_center_id to entries table if not exists
-- (Referenced in Entry_model.php getEntriesFiltered, getBalancesByAccount)
-- ============================================================================
-- Check and add column if needed (safe to run multiple times)
SET @dbname = DATABASE();
SET @tablename = 'entries';
SET @columnname = 'cost_center_id';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` int(11) DEFAULT NULL')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add deleted column to entries if not exists
SET @columnname = 'deleted';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` tinyint(1) DEFAULT 0')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
