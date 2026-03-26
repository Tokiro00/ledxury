-- ============================================================================
-- MAM ERP - Migracion Completa para Produccion
-- Ejecutar sobre la base de datos de produccion existente
-- Fecha: 2026-03-21
-- IMPORTANTE: Ejecutar con SET FOREIGN_KEY_CHECKS = 0
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. NUEVAS COLUMNAS EN TABLAS EXISTENTES
-- ============================================================================

-- 1.1 roles: puc_code
ALTER TABLE `roles` ADD COLUMN IF NOT EXISTS `puc_code` VARCHAR(20) NULL COMMENT 'Codigo PUC asociado al rol';

-- 1.2 users: bot columns
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `bot_api_key` VARCHAR(255) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `bot_sheet_id` VARCHAR(255) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `bot_script_url` VARCHAR(500) NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `bot_gid` VARCHAR(50) DEFAULT '0';

-- 1.3 payments: origen caja/banco
ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `originType` ENUM('caja','banco') NULL;
ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `originId` INT(11) NULL;
ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `cashMovementId` INT(11) NULL;

-- 1.4 entries: tienda, centro de costo, transaccion
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `entryStoreId` INT(11) NULL;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `cost_center_id` INT(11) NULL;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `entryTransactionType` VARCHAR(50) NULL;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `entryTransactionId` BIGINT(20) NULL;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `entryDate` DATE NULL;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `deleted` TINYINT(4) DEFAULT 0;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL;
ALTER TABLE `entries` ADD COLUMN IF NOT EXISTS `created_by` VARCHAR(100) NULL;

-- 1.5 subaccounts: PUC
ALTER TABLE `subaccounts` ADD COLUMN IF NOT EXISTS `pucCode` VARCHAR(20) NULL;
ALTER TABLE `subaccounts` ADD COLUMN IF NOT EXISTS `store` INT(11) NULL DEFAULT 1;
ALTER TABLE `subaccounts` ADD COLUMN IF NOT EXISTS `accountType` VARCHAR(50) NULL;

-- 1.6 accounts_class: PUC
ALTER TABLE `accounts_class` ADD COLUMN IF NOT EXISTS `pucCode` VARCHAR(20) NULL;
ALTER TABLE `accounts_class` ADD COLUMN IF NOT EXISTS `store` INT(11) NOT NULL DEFAULT 1;

-- 1.7 accounts_group/accounts: PUC
ALTER TABLE `accounts_group` ADD COLUMN IF NOT EXISTS `pucCode` VARCHAR(20) NULL;
ALTER TABLE `accounts_accounts` ADD COLUMN IF NOT EXISTS `pucCode` VARCHAR(20) NULL;

-- 1.8 providers: puc_code
ALTER TABLE `providers` ADD COLUMN IF NOT EXISTS `puc_code` VARCHAR(20) NULL;

-- 1.9 budgets: budget_type
ALTER TABLE `budgets` ADD COLUMN IF NOT EXISTS `budget_type` ENUM('venta','devolucion','garantia') NOT NULL DEFAULT 'venta';

-- ============================================================================
-- 2. POBLAR PUC CODES
-- ============================================================================

UPDATE `accounts_class` SET `pucCode` = CAST(`classID` AS CHAR) WHERE `pucCode` IS NULL;
UPDATE `accounts_group` SET `pucCode` = CAST(`groupID` AS CHAR) WHERE `pucCode` IS NULL;
UPDATE `accounts_accounts` SET `pucCode` = CAST(`accountID` AS CHAR) WHERE `pucCode` IS NULL;
UPDATE `subaccounts` SET `pucCode` = CAST(`accountID` AS CHAR) WHERE `pucCode` IS NULL;

-- ============================================================================
-- 3. TESORERIA (Cajas y Bancos)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cashboxes` (
  `idCashbox` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `type` ENUM('principal','secundaria','chica') DEFAULT 'principal',
  `storeId` INT(11) NOT NULL,
  `subaccountId` INT(11) NULL,
  `initialBalance` DECIMAL(15,2) DEFAULT 0.00,
  `currentBalance` DECIMAL(15,2) DEFAULT 0.00,
  `responsibleUserId` VARCHAR(100) NULL,
  `status` ENUM('abierta','cerrada','arqueo','bloqueada') DEFAULT 'cerrada',
  `openedAt` DATETIME NULL, `closedAt` DATETIME NULL,
  `openedBy` VARCHAR(100) NULL, `closedBy` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL, `deleted` TINYINT(4) DEFAULT 0,
  PRIMARY KEY (`idCashbox`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_store` (`storeId`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `idBankAccount` INT(11) NOT NULL AUTO_INCREMENT,
  `bankName` VARCHAR(100) NOT NULL,
  `accountNumber` VARCHAR(50) NOT NULL,
  `accountType` ENUM('ahorros','corriente','credito','otro') DEFAULT 'corriente',
  `currency` VARCHAR(10) DEFAULT 'COP',
  `storeId` INT(11) NOT NULL,
  `subaccountId` INT(11) NULL,
  `initialBalance` DECIMAL(15,2) DEFAULT 0.00,
  `currentBalance` DECIMAL(15,2) DEFAULT 0.00,
  `ownerName` VARCHAR(150) NULL, `ownerIdNumber` VARCHAR(50) NULL,
  `branchOffice` VARCHAR(100) NULL,
  `contactEmail` VARCHAR(100) NULL, `contactPhone` VARCHAR(50) NULL,
  `status` ENUM('activa','inactiva','bloqueada') DEFAULT 'activa',
  `notes` TEXT NULL,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL, `deleted` TINYINT(4) DEFAULT 0,
  PRIMARY KEY (`idBankAccount`),
  UNIQUE KEY `accountNumber` (`accountNumber`),
  KEY `idx_store` (`storeId`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cash_movements` (
  `idMovement` INT(11) NOT NULL AUTO_INCREMENT,
  `movementType` ENUM('ingreso','egreso','transferencia','ajuste','apertura','cierre') NOT NULL,
  `sourceType` ENUM('caja','banco') NOT NULL,
  `sourceId` INT(11) NOT NULL,
  `destinationType` ENUM('caja','banco') NULL,
  `destinationId` INT(11) NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `concept` VARCHAR(255) NOT NULL,
  `category` ENUM('venta','pago_proveedor','gasto','pago_cliente','nomina','impuestos','prestamo','otro') DEFAULT 'otro',
  `referenceType` VARCHAR(50) NULL, `referenceId` INT(11) NULL,
  `paymentMethodId` INT(11) NULL, `documentNumber` VARCHAR(100) NULL,
  `entryId` INT(11) NULL,
  `executedBy` VARCHAR(100) NOT NULL DEFAULT '',
  `authorizedBy` VARCHAR(100) NULL,
  `movementDate` DATETIME NOT NULL,
  `notes` TEXT NULL,
  `status` ENUM('pendiente','autorizado','ejecutado','rechazado','anulado') DEFAULT 'ejecutado',
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL, `deleted` TINYINT(4) DEFAULT 0,
  PRIMARY KEY (`idMovement`),
  KEY `idx_source` (`sourceType`,`sourceId`),
  KEY `idx_date` (`movementDate`),
  KEY `idx_reference` (`referenceType`,`referenceId`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cashbox_closures` (
  `idClosure` INT(11) NOT NULL AUTO_INCREMENT,
  `cashboxId` INT(11) NOT NULL,
  `closureDate` DATETIME NOT NULL,
  `openingBalance` DECIMAL(15,2) DEFAULT 0.00,
  `totalIngress` DECIMAL(15,2) DEFAULT 0.00,
  `totalEgress` DECIMAL(15,2) DEFAULT 0.00,
  `expectedBalance` DECIMAL(15,2) DEFAULT 0.00,
  `actualBalance` DECIMAL(15,2) DEFAULT 0.00,
  `difference` DECIMAL(15,2) DEFAULT 0.00,
  `billCount` TEXT NULL, `notes` TEXT NULL,
  `closedBy` VARCHAR(100) NOT NULL, `authorizedBy` VARCHAR(100) NULL,
  `status` ENUM('borrador','cerrada','autorizada') DEFAULT 'borrador',
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL, `deleted` TINYINT(4) DEFAULT 0,
  PRIMARY KEY (`idClosure`),
  KEY `idx_cashbox` (`cashboxId`),
  KEY `idx_date` (`closureDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `idReconciliation` INT(11) NOT NULL AUTO_INCREMENT,
  `bankAccountId` INT(11) NOT NULL,
  `reconciliationDate` DATE NOT NULL, `statementDate` DATE NOT NULL,
  `bookBalance` DECIMAL(15,2) DEFAULT 0.00,
  `bankBalance` DECIMAL(15,2) DEFAULT 0.00,
  `reconciledBalance` DECIMAL(15,2) DEFAULT 0.00,
  `difference` DECIMAL(15,2) DEFAULT 0.00,
  `notes` TEXT NULL,
  `reconciledBy` VARCHAR(100) NOT NULL, `authorizedBy` VARCHAR(100) NULL,
  `status` ENUM('borrador','conciliada','autorizada') DEFAULT 'borrador',
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL, `deleted` TINYINT(4) DEFAULT 0,
  PRIMARY KEY (`idReconciliation`),
  KEY `idx_bank_account` (`bankAccountId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- 4. CUENTAS POR PAGAR
-- ============================================================================

CREATE TABLE IF NOT EXISTS `supplier_invoices` (
  `idSupplierInvoice` INT(11) NOT NULL AUTO_INCREMENT,
  `invoiceNumber` VARCHAR(100) NOT NULL,
  `providerId` INT(11) NOT NULL, `storeId` INT(11) NOT NULL,
  `invoiceDate` DATE NOT NULL, `dueDate` DATE NOT NULL,
  `subtotal` DECIMAL(15,2) DEFAULT 0.00,
  `taxAmount` DECIMAL(15,2) DEFAULT 0.00,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `paidAmount` DECIMAL(15,2) DEFAULT 0.00,
  `balance` DECIMAL(15,2) GENERATED ALWAYS AS (`total` - `paidAmount`) STORED,
  `status` ENUM('pendiente','parcial','pagada','vencida','anulada') DEFAULT 'pendiente',
  `concept` VARCHAR(255) NULL, `notes` TEXT NULL,
  `received` TINYINT(1) NOT NULL DEFAULT 0,
  `received_at` DATETIME NULL, `received_by` VARCHAR(100) NULL,
  `destination_store` INT(11) NULL,
  `documentPath` VARCHAR(255) NULL, `entryId` INT(11) NULL,
  `created_by` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL, `deleted` TINYINT(4) DEFAULT 0,
  PRIMARY KEY (`idSupplierInvoice`),
  KEY `idx_provider` (`providerId`), KEY `idx_store` (`storeId`),
  KEY `idx_status` (`status`), KEY `idx_due_date` (`dueDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `supplier_invoice_details` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `supplierInvoiceId` INT(11) NOT NULL,
  `productId` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `unitCost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_supplier_invoice` (`supplierInvoiceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `idSupplierPayment` INT(11) NOT NULL AUTO_INCREMENT,
  `supplierInvoiceId` INT(11) NULL, `providerId` INT(11) NOT NULL,
  `paymentDate` DATETIME NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `paymentMethod` ENUM('efectivo','transferencia','cheque','otro') DEFAULT 'transferencia',
  `sourceType` ENUM('caja','banco') NOT NULL DEFAULT 'caja',
  `sourceId` INT(11) NOT NULL DEFAULT 0,
  `cashMovementId` INT(11) NULL, `entryId` INT(11) NULL,
  `reference` VARCHAR(100) NULL, `notes` TEXT NULL,
  `status` ENUM('pendiente','autorizado','ejecutado','anulado') DEFAULT 'ejecutado',
  `created_by` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL, `deleted` TINYINT(4) DEFAULT 0,
  PRIMARY KEY (`idSupplierPayment`),
  KEY `idx_provider` (`providerId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- 5. GASTOS OPERACIONALES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL, `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `accounting_account_id` INT(11) NULL, `accounting_subaccount_id` INT(11) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(4) NOT NULL DEFAULT 0, `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `expense_categories` (`code`,`name`,`description`,`is_active`,`created_at`) VALUES
('CAT001','Servicios Publicos','Agua, luz, gas, telefono, internet',1,NOW()),
('CAT002','Arriendo','Arriendo de local, bodega, oficina',1,NOW()),
('CAT003','Transporte','Fletes, envios, combustible, peajes',1,NOW()),
('CAT004','Suministros de Oficina','Papeleria, tinta, utiles',1,NOW()),
('CAT005','Mantenimiento y Reparaciones','Reparaciones de equipos',1,NOW()),
('CAT006','Alimentacion','Alimentacion de empleados',1,NOW()),
('CAT007','Aseo y Cafeteria','Productos de aseo, cafe',1,NOW()),
('CAT008','Publicidad y Marketing','Publicidad, redes sociales',1,NOW()),
('CAT009','Honorarios Profesionales','Contador, abogado',1,NOW()),
('CAT010','Otros Gastos','Gastos varios',1,NOW());

CREATE TABLE IF NOT EXISTS `expense_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL, `description` TEXT NOT NULL,
  `provider_id` INT(11) NOT NULL, `expense_category_id` INT(11) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL, `expense_date` DATE NOT NULL,
  `source_type` ENUM('caja','banco') NULL, `source_id` INT(11) NULL,
  `payment_method` VARCHAR(50) NULL, `voucher_reference` VARCHAR(100) NULL,
  `observations` TEXT NULL,
  `status` ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente',
  `store_id` INT(11) NOT NULL,
  `cash_movement_id` INT(11) NULL, `entry_id` INT(11) NULL,
  `created_by` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(4) NOT NULL DEFAULT 0, `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_code` (`code`),
  INDEX `idx_category` (`expense_category_id`),
  INDEX `idx_store` (`store_id`), INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- 6. PERIODOS CONTABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `accounting_periods` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `storeId` INT(11) NULL,
  `periodYear` INT(4) NOT NULL, `periodMonth` INT(2) NOT NULL,
  `periodType` ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `status` ENUM('open','closed','reopened') NOT NULL DEFAULT 'open',
  `startDate` DATE NOT NULL, `endDate` DATE NOT NULL,
  `closingEntryId` BIGINT NULL,
  `closedBy` VARCHAR(100) NULL, `closedAt` DATETIME NULL,
  `reopenedBy` VARCHAR(100) NULL, `reopenedAt` DATETIME NULL,
  `notes` TEXT NULL,
  `totalIncome` DECIMAL(15,2) DEFAULT 0, `totalExpenses` DECIMAL(15,2) DEFAULT 0,
  `totalCosts` DECIMAL(15,2) DEFAULT 0, `netIncome` DECIMAL(15,2) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NULL,
  `deleted` TINYINT(4) DEFAULT 0, `deleted_at` DATETIME NULL,
  UNIQUE KEY `idx_period_unique` (`storeId`,`periodYear`,`periodMonth`,`periodType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- 7. CENTROS DE COSTO
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cost_centers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL, `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `type` ENUM('tienda','departamento','proyecto') NOT NULL DEFAULT 'departamento',
  `parent_id` INT(11) NULL, `store_id` INT(11) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  `deleted` TINYINT(4) NOT NULL DEFAULT 0, `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- 8. CONFIGURACION CONTABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `accounting_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(50) NOT NULL,
  `subaccount_id` INT(11) NULL,
  `puc_code` VARCHAR(10) NULL,
  `description` VARCHAR(100) NOT NULL,
  `group_name` VARCHAR(30) NOT NULL DEFAULT 'otros',
  `updated_at` DATETIME NULL, `updated_by` VARCHAR(50) NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `accounting_settings` (`setting_key`,`puc_code`,`description`,`group_name`) VALUES
('account_cash','110505','Cajas','activos'),
('account_bank','111005','Bancos','activos'),
('account_receivable','130505','Cuentas por cobrar clientes','activos'),
('account_receivable_related','132015','Cuentas por cobrar relacionados','activos'),
('account_receivable_partners','132505','Cuentas por cobrar Socios','activos'),
('account_receivable_shareholders','132510','Cuentas por cobrar Accionistas','activos'),
('account_receivable_employees','136595','Cuentas por cobrar empleados','activos'),
('account_inventory','143501','Inventario','activos'),
('account_inventory_transit','143505','Mercancia en transito','activos'),
('account_vendor_payable','136595','Cuentas por cobrar vendedores','activos'),
('account_payable_banks','210510','Cuentas por pagar Bancos','pasivos'),
('account_payable','220501','Cuentas por pagar Proveedores','pasivos'),
('account_payable_china','221001','Cuentas por pagar China','pasivos'),
('account_payable_partners','231001','Cuentas por Pagar Socios','pasivos'),
('account_capital','310505','Capital','patrimonio'),
('account_profit','360501','Utilidad del ejercicio','patrimonio'),
('account_retained_earnings','370501','Utilidades acumuladas','patrimonio'),
('account_revenue','413506','Venta de mercancias','ingresos'),
('account_discounts','425030','Descuentos','ingresos'),
('account_salaries','510506','Sueldos','gastos'),
('account_travel','510521','Viaticos','gastos'),
('account_bonuses','510548','Bonificaciones empleados','gastos'),
('account_social_security','510569','Seguridad Social','gastos'),
('account_professional_fees','511025','Honorarios profesionales','gastos'),
('account_taxes','511570','Impuestos','gastos'),
('account_rent','512010','Arrendamientos','gastos'),
('account_utilities','513530','Servicios publicos','gastos'),
('account_phone','513535','Telefonos y celulares','gastos'),
('account_freight','513550','Fletes','gastos'),
('account_licenses','514015','Licencias y software','gastos'),
('account_maintenance','514510','Mantenimiento y reparaciones','gastos'),
('account_vehicle_maintenance','514540','Mantenimiento de vehiculos','gastos'),
('account_cafeteria','519525','Cafeteria','gastos'),
('account_cleaning','519530','Elementos de aseo y papeleria','gastos'),
('account_packaging','519540','Empaques y ferreteria','gastos'),
('account_commission','520518','Comisiones Vendedores','gastos'),
('account_advertising','523560','Publicidad y propaganda','gastos'),
('account_representation','529520','Gastos de representacion','gastos'),
('account_contingency','529595','Imprevistos','gastos'),
('account_inventory_shortage','529915','Faltantes inventarios','gastos'),
('account_bank_charges','530505','Intereses y gastos bancarios','gastos'),
('account_bad_debt','531010','Castigo de cartera','gastos'),
('account_cost_of_sales','613506','Costo de venta de mercancias','costos');

-- Resolver subaccount_id
UPDATE `accounting_settings` AS s
  INNER JOIN `subaccounts` AS sub ON sub.pucCode = s.puc_code AND sub.deleted = 0
SET s.subaccount_id = sub.id;

-- ============================================================================
-- 9. DEPARTAMENTOS Y KPIs
-- ============================================================================

CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL, `description` TEXT NULL,
  `leader_user_id` VARCHAR(100) NULL, `store_id` INT(11) NULL,
  `budget` DECIMAL(15,2) DEFAULT 0.00,
  `bonus_base` DECIMAL(15,2) DEFAULT 0,
  `bonus_cumpl` DECIMAL(15,2) DEFAULT 0,
  `bonus_elite` DECIMAL(15,2) DEFAULT 0,
  `bonus_max_annual` DECIMAL(15,2) DEFAULT 0,
  `min_score` DECIMAL(5,2) DEFAULT 60,
  `extra_condition` TEXT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `department_kpis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `department_id` INT(11) NOT NULL,
  `name` VARCHAR(100) NOT NULL, `description` TEXT NULL,
  `target_value` DECIMAL(15,2) DEFAULT 0.00,
  `current_value` DECIMAL(15,2) DEFAULT 0.00,
  `unit` VARCHAR(20) DEFAULT '%',
  `direction` ENUM('higher_better','lower_better') DEFAULT 'higher_better',
  `weight` DECIMAL(5,2) DEFAULT 1.00,
  `sort_order` INT(11) DEFAULT 0,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL, `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `bonus_calculations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `department_id` INT(11) NOT NULL,
  `year` INT(4) NOT NULL, `quarter` INT(1) NOT NULL,
  `compliance_score` DECIMAL(5,2) DEFAULT 0.00,
  `bonus_amount` DECIMAL(15,2) DEFAULT 0.00,
  `bonus_tier` VARCHAR(20) NULL,
  `kpis_above_70` INT DEFAULT 0,
  `calculated_by` VARCHAR(100) NULL, `notes` TEXT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- 10. ROLES Y PERMISOS
-- ============================================================================

-- Nuevos roles (si no existen)
INSERT IGNORE INTO `roles` (`idRoles`,`name`,`description`,`created_at`) VALUES
(8,'cartera','Cartera / Cobros',NOW()),
(9,'logistica','Logistica / Despachos',NOW());

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL,
  `module_key` VARCHAR(50) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_role_module` (`role_id`,`module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- 11. BOT IMPORTS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `bot_imports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(50) DEFAULT 'google_sheets',
  `raw_data` TEXT NULL, `client_name` VARCHAR(150) NULL,
  `client_id` INT(11) NULL, `vendor_id` VARCHAR(100) NULL,
  `store_id` INT(11) NULL,
  `total` DECIMAL(15,2) DEFAULT 0.00,
  `status` ENUM('pending','processed','error','skipped') DEFAULT 'pending',
  `invoice_id` INT(11) NULL, `error_message` TEXT NULL,
  `processed_at` DATETIME NULL, `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- FIN
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- POST-MIGRACION:
-- 1. Ejecutar desde el ERP: Configuracion > Permisos y Roles > Guardar
-- 2. Crear cajas: Tesoreria > Cajas > Nueva
-- 3. Crear cuentas bancarias si aplica
-- 4. Configurar departamentos y KPIs por ciudad
-- 5. Configurar metas de vendedores en Departamentos > Ventas > Ver
-- ============================================================================
