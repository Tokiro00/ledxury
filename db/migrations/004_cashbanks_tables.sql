-- ============================================================================
-- MIGRACIÓN 004: Tablas de Caja y Bancos
-- Fecha: 2026-01-31
-- Descripción: Crea las tablas necesarias para el módulo de Caja y Bancos
-- ============================================================================

-- ============================================================================
-- 1. Tabla: cashboxes (Cajas físicas y virtuales)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cashboxes` (
  `idCashbox` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la caja',
  `code` varchar(20) NOT NULL COMMENT 'Código único',
  `type` enum('principal','secundaria','chica') DEFAULT 'principal',
  `storeId` int(11) NOT NULL COMMENT 'Bodega asignada',
  `subaccountId` int(11) NULL COMMENT 'Vincula con contabilidad (subaccounts.id)',
  `initialBalance` decimal(15,2) DEFAULT 0.00,
  `currentBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo actual calculado',
  `responsibleUserId` varchar(100) NULL COMMENT 'Usuario responsable de la caja',
  `status` enum('abierta','cerrada','arqueo','bloqueada') DEFAULT 'cerrada',
  `openedAt` datetime NULL,
  `closedAt` datetime NULL,
  `openedBy` varchar(100) NULL,
  `closedBy` varchar(100) NULL,
  `notes` text NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idCashbox`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_store` (`storeId`),
  KEY `idx_subaccount` (`subaccountId`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cajas físicas y virtuales';

-- ============================================================================
-- 2. Tabla: bank_accounts (Cuentas bancarias)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `idBankAccount` int(11) NOT NULL AUTO_INCREMENT,
  `bankName` varchar(100) NOT NULL COMMENT 'Nombre del banco',
  `accountNumber` varchar(50) NOT NULL COMMENT 'Número de cuenta',
  `accountType` enum('ahorros','corriente','credito','otro') DEFAULT 'corriente',
  `currency` varchar(10) DEFAULT 'COP',
  `storeId` int(11) NOT NULL COMMENT 'Bodega asignada',
  `subaccountId` int(11) NULL COMMENT 'Vincula con contabilidad (subaccounts.id)',
  `initialBalance` decimal(15,2) DEFAULT 0.00,
  `currentBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo actual',
  `ownerName` varchar(150) NULL COMMENT 'Nombre del titular',
  `ownerIdNumber` varchar(50) NULL COMMENT 'NIT o CC del titular',
  `branchOffice` varchar(100) NULL COMMENT 'Sucursal del banco',
  `contactEmail` varchar(100) NULL,
  `contactPhone` varchar(50) NULL,
  `status` enum('activa','inactiva','bloqueada') DEFAULT 'activa',
  `notes` text NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idBankAccount`),
  UNIQUE KEY `accountNumber` (`accountNumber`),
  KEY `idx_store` (`storeId`),
  KEY `idx_subaccount` (`subaccountId`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cuentas bancarias';

-- ============================================================================
-- 3. Tabla: cash_movements (Movimientos de caja y banco)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cash_movements` (
  `idMovement` int(11) NOT NULL AUTO_INCREMENT,
  `movementType` enum('ingreso','egreso','transferencia','ajuste','apertura','cierre') NOT NULL COMMENT 'Tipo de movimiento',
  `sourceType` enum('caja','banco') NOT NULL COMMENT 'Tipo de origen',
  `sourceId` int(11) NOT NULL COMMENT 'ID de caja o banco origen',
  `destinationType` enum('caja','banco') NULL COMMENT 'Tipo de destino (solo transferencias)',
  `destinationId` int(11) NULL COMMENT 'ID de caja o banco destino',
  `amount` decimal(15,2) NOT NULL COMMENT 'Monto del movimiento',
  `concept` varchar(255) NOT NULL COMMENT 'Descripción del movimiento',
  `category` enum('venta','pago_proveedor','gasto','pago_cliente','nomina','impuestos','prestamo','otro') DEFAULT 'otro',
  `referenceType` varchar(50) NULL COMMENT 'Tipo de referencia: invoice, payment, expense',
  `referenceId` int(11) NULL COMMENT 'ID de la referencia',
  `paymentMethodId` int(11) NULL COMMENT 'Método de pago utilizado',
  `documentNumber` varchar(100) NULL COMMENT 'Número de cheque o transferencia',
  `entryId` int(11) NULL COMMENT 'Asiento contable generado (entries.entryId)',
  `executedBy` varchar(100) NOT NULL COMMENT 'Usuario que ejecutó',
  `authorizedBy` varchar(100) NULL COMMENT 'Usuario que autorizó',
  `movementDate` datetime NOT NULL COMMENT 'Fecha del movimiento',
  `notes` text NULL,
  `status` enum('pendiente','autorizado','ejecutado','rechazado','anulado') DEFAULT 'ejecutado',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idMovement`),
  KEY `idx_source` (`sourceType`,`sourceId`),
  KEY `idx_destination` (`destinationType`,`destinationId`),
  KEY `idx_type` (`movementType`),
  KEY `idx_date` (`movementDate`),
  KEY `idx_reference` (`referenceType`,`referenceId`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Movimientos de caja y banco';

-- ============================================================================
-- 4. Tabla: cashbox_closures (Cierres de caja)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cashbox_closures` (
  `idClosure` int(11) NOT NULL AUTO_INCREMENT,
  `cashboxId` int(11) NOT NULL COMMENT 'ID de la caja',
  `closureDate` datetime NOT NULL COMMENT 'Fecha del cierre',
  `openingBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo de apertura',
  `totalIngress` decimal(15,2) DEFAULT 0.00 COMMENT 'Total ingresos del período',
  `totalEgress` decimal(15,2) DEFAULT 0.00 COMMENT 'Total egresos del período',
  `expectedBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo esperado (calculado)',
  `actualBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo real contado',
  `difference` decimal(15,2) DEFAULT 0.00 COMMENT 'Diferencia (sobrante +, faltante -)',
  `billCount` text NULL COMMENT 'JSON con conteo de billetes y monedas',
  `notes` text NULL,
  `closedBy` varchar(100) NOT NULL COMMENT 'Usuario que cerró',
  `authorizedBy` varchar(100) NULL COMMENT 'Usuario que autorizó',
  `status` enum('borrador','cerrada','autorizada') DEFAULT 'borrador',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idClosure`),
  KEY `idx_cashbox` (`cashboxId`),
  KEY `idx_date` (`closureDate`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cierres de caja con arqueo';

-- ============================================================================
-- 5. Tabla: bank_reconciliations (Conciliaciones bancarias)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `idReconciliation` int(11) NOT NULL AUTO_INCREMENT,
  `bankAccountId` int(11) NOT NULL COMMENT 'ID de la cuenta bancaria',
  `reconciliationDate` date NOT NULL COMMENT 'Fecha de la conciliación',
  `statementDate` date NOT NULL COMMENT 'Fecha del extracto bancario',
  `bookBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo en libros',
  `bankBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo según extracto',
  `reconciledBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Saldo conciliado',
  `difference` decimal(15,2) DEFAULT 0.00 COMMENT 'Diferencia sin conciliar',
  `notes` text NULL,
  `reconciledBy` varchar(100) NOT NULL COMMENT 'Usuario que concilió',
  `authorizedBy` varchar(100) NULL,
  `status` enum('borrador','conciliada','autorizada') DEFAULT 'borrador',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idReconciliation`),
  KEY `idx_bank_account` (`bankAccountId`),
  KEY `idx_date` (`reconciliationDate`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Conciliaciones bancarias';

-- ============================================================================
-- 6. ALTER TABLE payments - Agregar vínculos con Caja y Bancos
-- ============================================================================

ALTER TABLE `payments`
  ADD COLUMN `originType` enum('caja','banco') NULL COMMENT 'Tipo de origen del pago' AFTER `comments`,
  ADD COLUMN `originId` int(11) NULL COMMENT 'ID de caja o banco origen' AFTER `originType`,
  ADD COLUMN `cashMovementId` int(11) NULL COMMENT 'Movimiento de caja generado' AFTER `originId`,
  ADD KEY `idx_origin` (`originType`,`originId`),
  ADD KEY `idx_cash_movement` (`cashMovementId`);

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================

-- Verificar que las tablas fueron creadas
SHOW TABLES LIKE 'cashboxes';
SHOW TABLES LIKE 'bank_accounts';
SHOW TABLES LIKE 'cash_movements';
SHOW TABLES LIKE 'cashbox_closures';
SHOW TABLES LIKE 'bank_reconciliations';
