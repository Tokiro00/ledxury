-- ============================================================================
-- MIGRACIÓN 008: Módulo de Gastos con Categorías
-- Fecha: 2026-02-13
-- Descripción: Crea tablas para categorías de gastos y registro de gastos
--              con integración contable completa
-- ============================================================================

-- 1. Tabla de Categorías de Gastos
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `accounting_account_id` INT(11) NULL COMMENT 'FK a accounts_accounts.id',
    `accounting_subaccount_id` INT(11) NULL COMMENT 'FK a subaccounts.id',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted` TINYINT(4) NOT NULL DEFAULT 0,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 2. Tabla de Gastos (expense_records para no conflictar con tabla expenses existente)
CREATE TABLE IF NOT EXISTS `expense_records` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL,
    `description` TEXT NOT NULL,
    `provider_id` INT(11) NOT NULL COMMENT 'FK a providers.idProvider',
    `expense_category_id` INT(11) NOT NULL COMMENT 'FK a expense_categories.id',
    `amount` DECIMAL(15,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `source_type` ENUM('caja','banco') NULL COMMENT 'Origen del dinero',
    `source_id` INT(11) NULL COMMENT 'FK a cashboxes o bank_accounts',
    `payment_method` VARCHAR(50) NULL COMMENT 'efectivo, transferencia, cheque, otro',
    `voucher_reference` VARCHAR(100) NULL COMMENT 'Número de comprobante',
    `observations` TEXT NULL,
    `status` ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente',
    `store_id` INT(11) NOT NULL,
    `cash_movement_id` INT(11) NULL COMMENT 'FK a cash_movements.idMovement',
    `entry_id` INT(11) NULL COMMENT 'FK a entries.entryID',
    `created_by` VARCHAR(100) NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `deleted` TINYINT(4) NOT NULL DEFAULT 0,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    INDEX `idx_category` (`expense_category_id`),
    INDEX `idx_provider` (`provider_id`),
    INDEX `idx_store` (`store_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`expense_date`),
    INDEX `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 3. Categorías por defecto (comunes en Colombia)
INSERT INTO `expense_categories` (`code`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
('CAT001', 'Servicios Públicos', 'Agua, luz, gas, teléfono, internet', 1, NOW(), NOW()),
('CAT002', 'Arriendo', 'Arriendo de local, bodega, oficina', 1, NOW(), NOW()),
('CAT003', 'Transporte', 'Fletes, envíos, combustible, peajes', 1, NOW(), NOW()),
('CAT004', 'Suministros de Oficina', 'Papelería, tinta, útiles de oficina', 1, NOW(), NOW()),
('CAT005', 'Mantenimiento y Reparaciones', 'Reparaciones de equipos, instalaciones', 1, NOW(), NOW()),
('CAT006', 'Alimentación', 'Alimentación de empleados, refrigerios', 1, NOW(), NOW()),
('CAT007', 'Aseo y Cafetería', 'Productos de aseo, café, agua', 1, NOW(), NOW()),
('CAT008', 'Publicidad y Marketing', 'Publicidad, volantes, redes sociales', 1, NOW(), NOW()),
('CAT009', 'Honorarios Profesionales', 'Contador, abogado, consultoría', 1, NOW(), NOW()),
('CAT010', 'Otros Gastos', 'Gastos varios no categorizados', 1, NOW(), NOW());
