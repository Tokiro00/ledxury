-- ============================================================================
-- MIGRACIÓN 003: Actualización de Datos PUC y Seed de Cuentas Faltantes
-- Fecha: 2026-01-25
-- Descripción: Actualiza datos existentes con códigos PUC y agrega
--              cuentas faltantes (Ingresos, Gastos, Costos)
-- ============================================================================

-- Crear backup antes de ejecutar:
-- mysqldump -u root mamdb > backup_mamdb_before_003.sql

-- ============================================================================
-- PASO 1: Actualizar accounts_class - Asignar códigos PUC a clases existentes
-- ============================================================================

UPDATE `accounts_class`
SET `pucCode` = '1'
WHERE `className` LIKE '%Activo%' AND `pucCode` IS NULL;

UPDATE `accounts_class`
SET `pucCode` = '2'
WHERE `className` LIKE '%Pasivo%' AND `pucCode` IS NULL;

UPDATE `accounts_class`
SET `pucCode` = '3'
WHERE `className` LIKE '%Patrimonio%' AND `pucCode` IS NULL;

-- ============================================================================
-- PASO 2: Actualizar accounts_group - Asignar códigos PUC a grupos existentes
-- ============================================================================

UPDATE `accounts_group`
SET `pucCode` = '11'
WHERE `groupName` LIKE '%Disponible%' AND `pucCode` IS NULL;

UPDATE `accounts_group`
SET `pucCode` = '13'
WHERE `groupName` LIKE '%Deudores%' AND `pucCode` IS NULL;

UPDATE `accounts_group`
SET `pucCode` = '14'
WHERE `groupName` LIKE '%Inventarios%' AND `pucCode` IS NULL;

UPDATE `accounts_group`
SET `pucCode` = '23'
WHERE `groupName` LIKE '%Cuentas por pagar%' AND `pucCode` IS NULL;

UPDATE `accounts_group`
SET `pucCode` = '31'
WHERE `groupName` LIKE '%Capital Social%' AND `pucCode` IS NULL;

UPDATE `accounts_group`
SET `pucCode` = '37'
WHERE `groupName` LIKE '%Resultados%' AND `pucCode` IS NULL;

-- ============================================================================
-- PASO 3: Actualizar accounts_accounts - Copiar accountID a pucCode
-- ============================================================================
-- IMPORTANTE: Los accountID ya usan códigos tipo PUC (1105, 1110, 1305, etc.)

UPDATE `accounts_accounts`
SET `pucCode` = `accountID`
WHERE `pucCode` IS NULL;

-- ============================================================================
-- PASO 4: Actualizar subaccounts - Asignar pucCode, accountType y store
-- ============================================================================

-- Actualizar subaccounts de Caja (accountType = 'asset', accountSide = '1')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'asset'
WHERE a.accountName LIKE '%Caja%' AND s.pucCode IS NULL;

-- Actualizar subaccounts de Bancos (accountType = 'asset')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'asset'
WHERE a.accountName LIKE '%Bancos%' AND s.pucCode IS NULL;

-- Actualizar subaccounts de Clientes (accountType = 'asset')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'asset'
WHERE a.accountName LIKE '%Clientes%' AND s.pucCode IS NULL;

-- Actualizar subaccounts de Productos/Inventarios (accountType = 'asset')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'asset'
WHERE a.accountName LIKE '%Productos%' AND s.pucCode IS NULL;

-- Actualizar subaccounts de Provisiones (accountType = 'asset')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'asset'
WHERE a.accountName LIKE '%Provisiones%' AND s.pucCode IS NULL;

-- Actualizar subaccounts de Proveedores/Cuentas Comerciales (accountType = 'liability')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'liability'
WHERE (a.accountName LIKE '%Cuentas Comerciales%' OR a.accountName LIKE '%Acreedores%')
  AND s.pucCode IS NULL;

-- Actualizar subaccounts de Capital (accountType = 'equity')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'equity'
WHERE a.accountName LIKE '%Capital%' AND s.pucCode IS NULL;

-- Actualizar subaccounts de Utilidades (accountType = 'equity')
UPDATE `subaccounts` s
INNER JOIN `accounts_accounts` a ON s.accountID = a.accountID
SET
    s.pucCode = CONCAT(a.pucCode, LPAD(s.id, 2, '0')),
    s.accountType = 'equity'
WHERE a.accountName LIKE '%Utilidades%' AND s.pucCode IS NULL;

-- ============================================================================
-- PASO 5: Actualizar auxiliary_subaccounts - Asignar accountType
-- ============================================================================

-- Marcar auxiliares de gastos
UPDATE `auxiliary_subaccounts`
SET `accountType` = 'other'
WHERE `accountName` = 'GASTOS' AND `accountType` IS NULL;

-- Marcar auxiliares de clientes (los que no son "GASTOS" ni "CONTADO")
UPDATE `auxiliary_subaccounts`
SET `accountType` = 'client'
WHERE `accountName` NOT IN ('GASTOS', 'CONTADO')
  AND `accountType` IS NULL;

-- Marcar CONTADO como other
UPDATE `auxiliary_subaccounts`
SET `accountType` = 'client'
WHERE `accountName` = 'CONTADO' AND `accountType` IS NULL;

-- ============================================================================
-- PASO 6: Insertar clases faltantes (Ingresos, Gastos, Costos)
-- ============================================================================
-- IMPORTANTE: Solo insertar si no existen

-- Clase 4: INGRESOS para cada bodega
INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 4, 'Ingresos', '4', 3, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '4' AND `store` = 3
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 4, 'Ingresos Medellín', '4', 1, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '4' AND `store` = 1
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 4, 'Ingresos Cali', '4', 5, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '4' AND `store` = 5
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 4, 'Ingresos Barranquilla', '4', 7, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '4' AND `store` = 7
);

-- Clase 5: GASTOS para cada bodega
INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 5, 'Gastos', '5', 3, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '5' AND `store` = 3
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 5, 'Gastos Medellín', '5', 1, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '5' AND `store` = 1
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 5, 'Gastos Cali', '5', 5, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '5' AND `store` = 5
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 5, 'Gastos Barranquilla', '5', 7, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '5' AND `store` = 7
);

-- Clase 6: COSTOS para cada bodega
INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 6, 'Costos', '6', 3, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '6' AND `store` = 3
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 6, 'Costos Medellín', '6', 1, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '6' AND `store` = 1
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 6, 'Costos Cali', '6', 5, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '6' AND `store` = 5
);

INSERT INTO `accounts_class` (`classID`, `className`, `pucCode`, `store`, `deleted`, `created_at`)
SELECT 6, 'Costos Barranquilla', '6', 7, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_class` WHERE `pucCode` = '6' AND `store` = 7
);

-- ============================================================================
-- PASO 7: Insertar grupos necesarios para Ingresos, Gastos y Costos
-- ============================================================================

-- Grupo 41: Ingresos Operacionales
INSERT INTO `accounts_group` (`groupID`, `groupName`, `pucCode`, `classID`, `deleted`, `created_at`)
SELECT 41, 'Ingresos Operacionales', '41', 4, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_group` WHERE `pucCode` = '41'
);

-- Grupo 51: Gastos Operacionales
INSERT INTO `accounts_group` (`groupID`, `groupName`, `pucCode`, `classID`, `deleted`, `created_at`)
SELECT 51, 'Gastos Operacionales', '51', 5, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_group` WHERE `pucCode` = '51'
);

-- Grupo 61: Costo de Ventas
INSERT INTO `accounts_group` (`groupID`, `groupName`, `pucCode`, `classID`, `deleted`, `created_at`)
SELECT 61, 'Costo de Ventas', '61', 6, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_group` WHERE `pucCode` = '61'
);

-- ============================================================================
-- PASO 8: Insertar cuentas necesarias para contabilidad completa
-- ============================================================================

-- Cuenta 4135: Comercio al por Mayor y Menor (Ventas)
INSERT INTO `accounts_accounts` (`accountID`, `accountName`, `pucCode`, `groupID`, `deleted`, `created_at`)
SELECT 4135, 'Comercio al por Mayor y Menor', '4135', 41, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_accounts` WHERE `pucCode` = '4135'
);

-- Cuenta 4175: Devoluciones en Ventas
INSERT INTO `accounts_accounts` (`accountID`, `accountName`, `pucCode`, `groupID`, `deleted`, `created_at`)
SELECT 4175, 'Devoluciones en Ventas', '4175', 41, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_accounts` WHERE `pucCode` = '4175'
);

-- Cuenta 5195: Gastos Diversos
INSERT INTO `accounts_accounts` (`accountID`, `accountName`, `pucCode`, `groupID`, `deleted`, `created_at`)
SELECT 5195, 'Diversos', '5195', 51, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_accounts` WHERE `pucCode` = '5195'
);

-- Cuenta 6135: Comercio al por Mayor (Costo)
INSERT INTO `accounts_accounts` (`accountID`, `accountName`, `pucCode`, `groupID`, `deleted`, `created_at`)
SELECT 6135, 'Comercio al por Mayor', '6135', 61, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `accounts_accounts` WHERE `pucCode` = '6135'
);

-- ============================================================================
-- PASO 9: Insertar subcuentas necesarias para operaciones mínimas
-- ============================================================================

-- 413505: Ventas de Mercancías
INSERT INTO `subaccounts` (`accountID`, `accountName`, `accountSide`, `accountStatement`, `accountType`, `pucCode`, `deleted`, `created_at`)
SELECT 4135, 'Ventas de Mercancías', '2', '2', 'revenue', '413505', 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `subaccounts` WHERE `pucCode` = '413505'
);

-- 417505: Devoluciones en Ventas
INSERT INTO `subaccounts` (`accountID`, `accountName`, `accountSide`, `accountStatement`, `accountType`, `pucCode`, `deleted`, `created_at`)
SELECT 4175, 'Devoluciones en Ventas', '1', '2', 'revenue', '417505', 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `subaccounts` WHERE `pucCode` = '417505'
);

-- 519505: Comisiones
INSERT INTO `subaccounts` (`accountID`, `accountName`, `accountSide`, `accountStatement`, `accountType`, `pucCode`, `deleted`, `created_at`)
SELECT 5195, 'Comisiones', '1', '2', 'expense', '519505', 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `subaccounts` WHERE `pucCode` = '519505'
);

-- 613505: Costo de Mercancías
INSERT INTO `subaccounts` (`accountID`, `accountName`, `accountSide`, `accountStatement`, `accountType`, `pucCode`, `deleted`, `created_at`)
SELECT 6135, 'Costo de Mercancías', '1', '2', 'cost', '613505', 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `subaccounts` WHERE `pucCode` = '613505'
);

-- ============================================================================
-- VERIFICACIÓN POST-MIGRACIÓN
-- ============================================================================

-- Contar clases por código PUC
SELECT 'Clases por PUC' as reporte, pucCode, COUNT(*) as cantidad, GROUP_CONCAT(store) as bodegas
FROM accounts_class
WHERE deleted = 0
GROUP BY pucCode
ORDER BY pucCode;

-- Contar grupos por código PUC
SELECT 'Grupos por PUC' as reporte, pucCode, COUNT(*) as cantidad
FROM accounts_group
WHERE deleted = 0
GROUP BY pucCode
ORDER BY pucCode;

-- Contar cuentas con pucCode asignado
SELECT 'Cuentas con PUC' as reporte,
    COUNT(*) as total,
    SUM(CASE WHEN pucCode IS NOT NULL THEN 1 ELSE 0 END) as con_puc,
    SUM(CASE WHEN pucCode IS NULL THEN 1 ELSE 0 END) as sin_puc
FROM accounts_accounts
WHERE deleted = 0;

-- Contar subcuentas con pucCode y accountType
SELECT 'Subcuentas actualizadas' as reporte,
    COUNT(*) as total,
    SUM(CASE WHEN pucCode IS NOT NULL THEN 1 ELSE 0 END) as con_puc,
    SUM(CASE WHEN accountType IS NOT NULL THEN 1 ELSE 0 END) as con_tipo,
    SUM(CASE WHEN pucCode IS NOT NULL AND accountType IS NOT NULL THEN 1 ELSE 0 END) as completas
FROM subaccounts
WHERE deleted = 0;

-- Ver subcuentas por tipo
SELECT accountType, COUNT(*) as cantidad
FROM subaccounts
WHERE deleted = 0
GROUP BY accountType;

-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
-- 1. Este script actualiza datos existentes sin eliminar nada
-- 2. Usa INSERT ... WHERE NOT EXISTS para evitar duplicados
-- 3. Los pucCode de subaccounts se generan concatenando pucCode de cuenta + id
-- 4. accountSide: '1' = Débito, '2' = Crédito (VARCHAR)
-- 5. accountStatement: '1' = Balance, '2' = Estado de Resultados (VARCHAR)
-- 6. Se insertan clases/grupos/cuentas mínimas para Ingresos, Gastos y Costos
-- ============================================================================
