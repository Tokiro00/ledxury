-- ============================================================================
-- MAM ERP - PUC: Ingresos (4), Gastos (5) y Costos (6)
-- Ejecutar sobre base de datos de produccion
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- CLASES
-- ============================================================================
INSERT IGNORE INTO accounts_class (classID, className, pucCode, store, created_at) VALUES
(4, 'Ingresos', '4', 1, NOW()),
(5, 'Gastos', '5', 1, NOW()),
(6, 'Costos de Ventas', '6', 1, NOW());

-- ============================================================================
-- GRUPOS
-- ============================================================================
-- Obtener IDs de las clases recien creadas
SET @classIngresos = (SELECT id FROM accounts_class WHERE pucCode = '4' AND deleted = 0 LIMIT 1);
SET @classGastos = (SELECT id FROM accounts_class WHERE pucCode = '5' AND deleted = 0 LIMIT 1);
SET @classCostos = (SELECT id FROM accounts_class WHERE pucCode = '6' AND deleted = 0 LIMIT 1);

INSERT IGNORE INTO accounts_group (groupID, groupName, pucCode, classID, created_at) VALUES
-- Ingresos
(41, 'Operacionales', '41', @classIngresos, NOW()),
(42, 'No Operacionales', '42', @classIngresos, NOW()),
-- Gastos
(51, 'Operacionales de Administracion', '51', @classGastos, NOW()),
(52, 'Operacionales de Ventas', '52', @classGastos, NOW()),
(53, 'No Operacionales', '53', @classGastos, NOW()),
-- Costos
(61, 'Costo de Ventas', '61', @classCostos, NOW());

-- ============================================================================
-- CUENTAS (4 digitos)
-- ============================================================================
INSERT IGNORE INTO accounts_accounts (accountID, accountName, pucCode, groupID, created_at)
SELECT t.accountID, t.accountName, t.pucCode, g.id, NOW()
FROM (
  -- Ingresos
  SELECT 4135 AS accountID, 'Comercio al por Mayor y Menor' AS accountName, '4135' AS pucCode, '41' AS groupPuc UNION ALL
  SELECT 4250, 'Descuentos Comerciales', '4250', '42' UNION ALL
  -- Gastos Admin
  SELECT 5105, 'Gastos de Personal', '5105', '51' UNION ALL
  SELECT 5110, 'Honorarios', '5110', '51' UNION ALL
  SELECT 5115, 'Impuestos', '5115', '51' UNION ALL
  SELECT 5120, 'Arrendamientos', '5120', '51' UNION ALL
  SELECT 5135, 'Servicios', '5135', '51' UNION ALL
  SELECT 5140, 'Gastos Legales', '5140', '51' UNION ALL
  SELECT 5145, 'Mantenimiento y Reparaciones', '5145', '51' UNION ALL
  SELECT 5195, 'Diversos', '5195', '51' UNION ALL
  -- Gastos Ventas
  SELECT 5205, 'Gastos de Personal Ventas', '5205', '52' UNION ALL
  SELECT 5235, 'Propaganda y Publicidad', '5235', '52' UNION ALL
  SELECT 5295, 'Diversos Ventas', '5295', '52' UNION ALL
  SELECT 5299, 'Provisiones', '5299', '52' UNION ALL
  -- Gastos No Operacionales
  SELECT 5305, 'Financieros', '5305', '53' UNION ALL
  SELECT 5310, 'Perdida en Retiro de Activos', '5310', '53' UNION ALL
  -- Costos
  SELECT 6135, 'Comercio al por Mayor y Menor', '6135', '61'
) AS t
INNER JOIN accounts_group g ON g.pucCode = t.groupPuc AND g.deleted = 0
LIMIT 100;

-- ============================================================================
-- SUBCUENTAS (6 digitos) - Gastos operacionales
-- ============================================================================
INSERT IGNORE INTO subaccounts (accountID, accountName, pucCode, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountStatement, accountType, store, created_by, created_at)
SELECT t.pucCode, t.accountName, t.pucCode, acc.id, '1', 0, 0, 0, '2', 'expense', 1, 'sistema', NOW()
FROM (
  SELECT '510506' AS pucCode, 'Sueldos' AS accountName, '5105' AS parentPuc UNION ALL
  SELECT '510521', 'Viaticos', '5105' UNION ALL
  SELECT '510548', 'Bonificaciones empleados', '5105' UNION ALL
  SELECT '510569', 'Seguridad Social', '5105' UNION ALL
  SELECT '511025', 'Honorarios profesionales', '5110' UNION ALL
  SELECT '511570', 'Impuestos', '5115' UNION ALL
  SELECT '512010', 'Arrendamientos', '5120' UNION ALL
  SELECT '513530', 'Servicios publicos', '5135' UNION ALL
  SELECT '513535', 'Telefonos y celulares', '5135' UNION ALL
  SELECT '513550', 'Fletes y transporte', '5135' UNION ALL
  SELECT '514015', 'Licencias y software', '5140' UNION ALL
  SELECT '514510', 'Mantenimiento y reparaciones', '5145' UNION ALL
  SELECT '514540', 'Mantenimiento de vehiculos', '5145' UNION ALL
  SELECT '519525', 'Cafeteria y restaurante', '5195' UNION ALL
  SELECT '519530', 'Elementos de aseo y papeleria', '5195' UNION ALL
  SELECT '519540', 'Empaques y ferreteria', '5195' UNION ALL
  SELECT '520518', 'Comisiones Vendedores', '5205' UNION ALL
  SELECT '523560', 'Publicidad y propaganda', '5235' UNION ALL
  SELECT '529520', 'Gastos de representacion', '5295' UNION ALL
  SELECT '529595', 'Imprevistos', '5295' UNION ALL
  SELECT '529915', 'Faltantes inventarios', '5299' UNION ALL
  SELECT '530505', 'Intereses y gastos bancarios', '5305' UNION ALL
  SELECT '531010', 'Castigo de cartera', '5310'
) AS t
INNER JOIN accounts_accounts acc ON acc.pucCode = t.parentPuc AND acc.deleted = 0;

-- ============================================================================
-- SUBCUENTAS - Ingresos
-- ============================================================================
INSERT IGNORE INTO subaccounts (accountID, accountName, pucCode, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountStatement, accountType, store, created_by, created_at)
SELECT t.pucCode, t.accountName, t.pucCode, acc.id, '2', 0, 0, 0, '2', 'revenue', 1, 'sistema', NOW()
FROM (
  SELECT '413506' AS pucCode, 'Venta de accesorios' AS accountName, '4135' AS parentPuc UNION ALL
  SELECT '425030', 'Descuentos comerciales', '4250'
) AS t
INNER JOIN accounts_accounts acc ON acc.pucCode = t.parentPuc AND acc.deleted = 0;

-- ============================================================================
-- SUBCUENTAS - Costos
-- ============================================================================
INSERT IGNORE INTO subaccounts (accountID, accountName, pucCode, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountStatement, accountType, store, created_by, created_at)
SELECT t.pucCode, t.accountName, t.pucCode, acc.id, '1', 0, 0, 0, '2', 'cost', 1, 'sistema', NOW()
FROM (
  SELECT '613506' AS pucCode, 'Costo de venta accesorios' AS accountName, '6135' AS parentPuc
) AS t
INNER JOIN accounts_accounts acc ON acc.pucCode = t.parentPuc AND acc.deleted = 0;

-- ============================================================================
-- Actualizar accounting_settings con los nuevos IDs
-- ============================================================================
UPDATE accounting_settings AS s
  INNER JOIN subaccounts AS sub ON sub.pucCode = s.puc_code AND sub.deleted = 0
SET s.subaccount_id = sub.id
WHERE s.subaccount_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICACION: Ejecutar despues para confirmar
-- SELECT COUNT(*) as total FROM subaccounts WHERE pucCode LIKE '5%' AND deleted = 0;
-- Debe mostrar 23 subcuentas de gastos
-- ============================================================================
