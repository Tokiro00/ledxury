-- ============================================================================
-- Migration 005: Accounts Payable (Cuentas por Pagar)
-- Date: 2026-02-01
-- Description: Creates tables for supplier invoices and payments
-- ============================================================================

-- Tabla: supplier_invoices (Facturas de Proveedor / Cuentas por Pagar)
CREATE TABLE IF NOT EXISTS `supplier_invoices` (
  `idSupplierInvoice` int(11) NOT NULL AUTO_INCREMENT,
  `invoiceNumber` varchar(100) NOT NULL COMMENT 'Numero de factura del proveedor',
  `providerId` int(11) NOT NULL COMMENT 'FK a providers',
  `storeId` int(11) NOT NULL COMMENT 'Bodega que recibe',
  `invoiceDate` date NOT NULL COMMENT 'Fecha de la factura',
  `dueDate` date NOT NULL COMMENT 'Fecha de vencimiento',
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `taxAmount` decimal(15,2) DEFAULT 0.00 COMMENT 'IVA u otros impuestos',
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paidAmount` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto pagado',
  `balance` decimal(15,2) GENERATED ALWAYS AS (`total` - `paidAmount`) STORED COMMENT 'Saldo pendiente',
  `status` enum('pendiente','parcial','pagada','vencida','anulada') DEFAULT 'pendiente',
  `concept` varchar(255) NULL COMMENT 'Concepto o descripcion',
  `notes` text NULL,
  `documentPath` varchar(255) NULL COMMENT 'Ruta al documento escaneado',
  `entryId` int(11) NULL COMMENT 'FK a entries (asiento contable)',
  `created_by` varchar(100) NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idSupplierInvoice`),
  KEY `providerId` (`providerId`),
  KEY `storeId` (`storeId`),
  KEY `status` (`status`),
  KEY `dueDate` (`dueDate`),
  KEY `invoiceDate` (`invoiceDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Facturas de proveedores / Cuentas por pagar';

-- Tabla: supplier_payments (Pagos a Proveedores)
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `idSupplierPayment` int(11) NOT NULL AUTO_INCREMENT,
  `supplierInvoiceId` int(11) NULL COMMENT 'FK opcional (puede ser anticipo)',
  `providerId` int(11) NOT NULL COMMENT 'FK a providers',
  `paymentDate` datetime NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paymentMethod` enum('efectivo','transferencia','cheque','otro') DEFAULT 'transferencia',
  `sourceType` enum('caja','banco') NOT NULL COMMENT 'Origen del pago',
  `sourceId` int(11) NOT NULL COMMENT 'FK a cashbox o bank_account',
  `cashMovementId` int(11) NULL COMMENT 'FK a cash_movements',
  `entryId` int(11) NULL COMMENT 'FK a entries',
  `reference` varchar(100) NULL COMMENT 'Numero de cheque o transferencia',
  `notes` text NULL,
  `status` enum('pendiente','autorizado','ejecutado','anulado') DEFAULT 'ejecutado',
  `created_by` varchar(100) NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idSupplierPayment`),
  KEY `supplierInvoiceId` (`supplierInvoiceId`),
  KEY `providerId` (`providerId`),
  KEY `sourceType_sourceId` (`sourceType`,`sourceId`),
  KEY `paymentDate` (`paymentDate`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Pagos a proveedores';

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
