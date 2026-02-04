-- =====================================================
-- 006 - Tabla de Períodos Contables (Cierre Contable)
-- =====================================================

-- Tabla para gestionar períodos contables y su cierre
CREATE TABLE IF NOT EXISTS `accounting_periods` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `storeId` INT(11) NULL COMMENT 'Bodega (NULL = todas)',
    `periodYear` INT(4) NOT NULL COMMENT 'Año del período',
    `periodMonth` INT(2) NOT NULL COMMENT 'Mes del período (1-12)',
    `periodType` ENUM('monthly', 'yearly') NOT NULL DEFAULT 'monthly' COMMENT 'Tipo de cierre',
    `status` ENUM('open', 'closed', 'reopened') NOT NULL DEFAULT 'open' COMMENT 'Estado del período',
    `startDate` DATE NOT NULL COMMENT 'Fecha inicio del período',
    `endDate` DATE NOT NULL COMMENT 'Fecha fin del período',
    `closingEntryId` BIGINT NULL COMMENT 'ID del asiento de cierre generado',
    `closedBy` VARCHAR(100) NULL COMMENT 'Usuario que cerró el período',
    `closedAt` DATETIME NULL COMMENT 'Fecha/hora de cierre',
    `reopenedBy` VARCHAR(100) NULL COMMENT 'Usuario que reabrió el período',
    `reopenedAt` DATETIME NULL COMMENT 'Fecha/hora de reapertura',
    `notes` TEXT NULL COMMENT 'Notas del cierre',
    `totalIncome` DECIMAL(15,2) DEFAULT 0 COMMENT 'Total ingresos al cierre',
    `totalExpenses` DECIMAL(15,2) DEFAULT 0 COMMENT 'Total gastos al cierre',
    `totalCosts` DECIMAL(15,2) DEFAULT 0 COMMENT 'Total costos al cierre',
    `netIncome` DECIMAL(15,2) DEFAULT 0 COMMENT 'Utilidad/Pérdida neta',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted` TINYINT(4) DEFAULT 0,
    `deleted_at` DATETIME NULL,
    UNIQUE KEY `idx_period_unique` (`storeId`, `periodYear`, `periodMonth`, `periodType`),
    INDEX `idx_period_status` (`status`),
    INDEX `idx_period_dates` (`startDate`, `endDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar cuenta PUC para Utilidad del Ejercicio si no existe
-- Clase 3: Patrimonio, Grupo 36: Resultados del Ejercicio
INSERT IGNORE INTO `accounts_class` (`classID`, `className`, `store`, `pucCode`)
SELECT '3', 'PATRIMONIO', 1, '3'
WHERE NOT EXISTS (SELECT 1 FROM `accounts_class` WHERE `classID` = '3' OR `pucCode` = '3');

INSERT IGNORE INTO `accounts_group` (`groupID`, `groupName`, `classID`, `pucCode`)
SELECT '36', 'RESULTADOS DEL EJERCICIO', (SELECT id FROM accounts_class WHERE classID = '3' LIMIT 1), '36'
WHERE NOT EXISTS (SELECT 1 FROM `accounts_group` WHERE `pucCode` = '36');

INSERT IGNORE INTO `accounts_accounts` (`accountID`, `accountName`, `groupID`, `pucCode`)
SELECT '3605', 'UTILIDAD DEL EJERCICIO', (SELECT id FROM accounts_group WHERE pucCode = '36' LIMIT 1), '3605'
WHERE NOT EXISTS (SELECT 1 FROM `accounts_accounts` WHERE `pucCode` = '3605');

INSERT IGNORE INTO `subaccounts` (`accountID`, `accountName`, `accountAccount`, `accountSide`, `accountStatement`, `pucCode`, `accountType`)
SELECT '360505', 'Utilidad del Ejercicio', (SELECT id FROM accounts_accounts WHERE pucCode = '3605' LIMIT 1), '2', '1', '360505', 'equity'
WHERE NOT EXISTS (SELECT 1 FROM `subaccounts` WHERE `pucCode` = '360505');

INSERT IGNORE INTO `subaccounts` (`accountID`, `accountName`, `accountAccount`, `accountSide`, `accountStatement`, `pucCode`, `accountType`)
SELECT '360510', 'Pérdida del Ejercicio', (SELECT id FROM accounts_accounts WHERE pucCode = '3605' LIMIT 1), '1', '1', '360510', 'equity'
WHERE NOT EXISTS (SELECT 1 FROM `subaccounts` WHERE `pucCode` = '360510');
