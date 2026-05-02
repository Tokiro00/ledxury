-- 041: Categorías de gasto + cuentas PUC complementarias para Ledxury
--
-- El usuario aportó la siguiente lista:
--   UTILIDADES ACUMULADAS                              → 3705 (existe, patrimonio)
--   VENTAS, PARTES, PIEZAS, Y ACCESORIOS DE VEHICULOS  → 4135 (existe, renombramos)
--   COSTO DE VENTA                                      → 6135 (existe, costo)
--   DEVOLUCIONES                                        → 4175 (existe, ingresos)
--   FLETES                                              → 513540 (NUEVO, gasto)
--   SUELDOS                                             → 510506 (NUEVO, gasto)
--   GASTOS BANCARIOS                                    → 530525 (NUEVO, gasto)
--   COMISIONES                                          → 510527 (NUEVO, gasto)
--   PUBLICIDAD Y FACEBOOK                               → 513550 (NUEVO, gasto)
--   COMISION SISTECREDITO                               → 530520 (NUEVO, gasto)
--
-- Solo las 6 últimas son "categorías de gasto" reales (egresos operativos).
-- Las primeras 4 son cuentas no-de-gasto que ya existen en el PUC del 039.
--
-- Esta migración:
--   1. Renombra 413505 al nombre real del negocio.
--   2. Crea cuenta 5105 (Gastos personal) y grupo 53 / cuenta 5305 (No operacionales).
--   3. Inserta 6 subcuentas postables nuevas con sus pucCode.
--   4. Mapea 6 settings keys nuevas en accounting_settings.
--   5. Inserta 6 expense_categories listas para usar desde la UI.

-- ── 1. Renombrar 413505 al nombre real del negocio ─────────────────────────
UPDATE subaccounts
SET accountName = 'Ventas, partes, piezas y accesorios de vehículos'
WHERE pucCode = '413505' AND store = 1 AND deleted = 0;

UPDATE accounts_accounts
SET accountName = 'Comercio partes y accesorios de vehículos',
    accountDescription = 'Ventas de partes, piezas y accesorios de vehículos al por menor'
WHERE pucCode = '4135' AND deleted = 0;

-- ── 2. Cuentas y grupos PUC nuevos ─────────────────────────────────────────
-- 5105 Gastos de personal (bajo grupo 51 que ya existe)
INSERT INTO accounts_accounts (accountID, groupID, accountName, accountDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 5105 AS accountID, 51 AS groupID, 'Gastos de personal' AS accountName,
           'Sueldos, comisiones a empleados, prestaciones' AS accountDescription,
           '5105' AS pucCode, NOW() AS created_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE pucCode='5105' AND deleted=0);

-- Grupo 53 (No operacionales) bajo clase 5
INSERT INTO accounts_group (groupID, classID, groupName, groupDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 53 AS groupID, 5 AS classID, 'No operacionales' AS groupName,
           'Gastos financieros y otros no operativos' AS groupDescription,
           '53' AS pucCode, NOW() AS created_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_group WHERE pucCode='53' AND deleted=0);

-- 5305 Financieros (bajo grupo 53)
INSERT INTO accounts_accounts (accountID, groupID, accountName, accountDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 5305 AS accountID, 53 AS groupID, 'Financieros' AS accountName,
           'Gastos bancarios, comisiones financieras, intereses' AS accountDescription,
           '5305' AS pucCode, NOW() AS created_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE pucCode='5305' AND deleted=0);

-- ── 3. Subcuentas postables nuevas ─────────────────────────────────────────
-- accountSide '1' (débito), accountStatement '2' (Estado de Resultados), accountType 'expense'
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, pucCode, store, created_at)
SELECT * FROM (
    SELECT 5105 AS accountID, 'Sueldos'                          AS accountName, 510506 AS accountAccount, '1' AS accountSide, 0.00 AS accountBalance, 0.00 AS accountDebit, 0.00 AS accountCredit, 14 AS accountOrder, 1 AS accountStatus, '2' AS accountStatement, 'expense' AS accountType, '510506' AS pucCode, 1 AS store, NOW() AS created_at
    UNION ALL SELECT 5105, 'Comisiones a empleados',                     510527, '1', 0.00, 0.00, 0.00, 15, 1, '2', 'expense', '510527', 1, NOW()
    UNION ALL SELECT 5135, 'Fletes (correo, portes y transportes)',      513540, '1', 0.00, 0.00, 0.00, 16, 1, '2', 'expense', '513540', 1, NOW()
    UNION ALL SELECT 5135, 'Publicidad y propaganda (incl. Facebook)',   513550, '1', 0.00, 0.00, 0.00, 17, 1, '2', 'expense', '513550', 1, NOW()
    UNION ALL SELECT 5305, 'Comisiones financieras (Sistecrédito, etc)', 530520, '1', 0.00, 0.00, 0.00, 18, 1, '2', 'expense', '530520', 1, NOW()
    UNION ALL SELECT 5305, 'Gastos bancarios (4x1000, otros)',           530525, '1', 0.00, 0.00, 0.00, 19, 1, '2', 'expense', '530525', 1, NOW()
) tmp
WHERE NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode = tmp.pucCode AND store = tmp.store AND deleted = 0);

-- ── 4. accounting_settings: mapeo key → subaccount.id ──────────────────────
INSERT INTO accounting_settings (setting_key, subaccount_id, created_at, updated_at)
SELECT setting_key, sub_id, NOW(), NOW() FROM (
    SELECT 'account_payroll'             AS setting_key, (SELECT id FROM subaccounts WHERE pucCode='510506' AND store=1 AND deleted=0 LIMIT 1) AS sub_id
    UNION ALL SELECT 'account_employee_commission',  (SELECT id FROM subaccounts WHERE pucCode='510527' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_freight',              (SELECT id FROM subaccounts WHERE pucCode='513540' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_advertising',          (SELECT id FROM subaccounts WHERE pucCode='513550' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_finance_commission',   (SELECT id FROM subaccounts WHERE pucCode='530520' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_bank_fees',            (SELECT id FROM subaccounts WHERE pucCode='530525' AND store=1 AND deleted=0 LIMIT 1)
) tmp
ON DUPLICATE KEY UPDATE subaccount_id = VALUES(subaccount_id), updated_at = NOW();

-- ── 5. expense_categories: catálogo listo para usar desde la UI ────────────
-- Cada categoría queda mapeada a su subcuenta PUC para que el posteo
-- automático en _processExpenseAccrual sepa qué cuenta debitar.
INSERT INTO expense_categories (code, name, description, accounting_account_id, accounting_subaccount_id, is_active, created_at, updated_at)
SELECT * FROM (
    SELECT 'GAS-FLE' AS code, 'Fletes' AS name, 'Correo, portes y transportes operativos' AS description,
           (SELECT id FROM accounts_accounts WHERE pucCode='5135' AND deleted=0 LIMIT 1) AS accounting_account_id,
           (SELECT id FROM subaccounts WHERE pucCode='513540' AND store=1 AND deleted=0 LIMIT 1) AS accounting_subaccount_id,
           1 AS is_active, NOW() AS created_at, NOW() AS updated_at
    UNION ALL SELECT 'GAS-SUE', 'Sueldos', 'Sueldos del personal',
           (SELECT id FROM accounts_accounts WHERE pucCode='5105' AND deleted=0 LIMIT 1),
           (SELECT id FROM subaccounts WHERE pucCode='510506' AND store=1 AND deleted=0 LIMIT 1),
           1, NOW(), NOW()
    UNION ALL SELECT 'GAS-COM', 'Comisiones', 'Comisiones a vendedores/empleados',
           (SELECT id FROM accounts_accounts WHERE pucCode='5105' AND deleted=0 LIMIT 1),
           (SELECT id FROM subaccounts WHERE pucCode='510527' AND store=1 AND deleted=0 LIMIT 1),
           1, NOW(), NOW()
    UNION ALL SELECT 'GAS-PUB', 'Publicidad y Facebook', 'Pauta digital, redes y publicidad',
           (SELECT id FROM accounts_accounts WHERE pucCode='5135' AND deleted=0 LIMIT 1),
           (SELECT id FROM subaccounts WHERE pucCode='513550' AND store=1 AND deleted=0 LIMIT 1),
           1, NOW(), NOW()
    UNION ALL SELECT 'GAS-BAN', 'Gastos bancarios', '4x1000, comisiones y otros cargos bancarios',
           (SELECT id FROM accounts_accounts WHERE pucCode='5305' AND deleted=0 LIMIT 1),
           (SELECT id FROM subaccounts WHERE pucCode='530525' AND store=1 AND deleted=0 LIMIT 1),
           1, NOW(), NOW()
    UNION ALL SELECT 'GAS-SIS', 'Comisión Sistecrédito', 'Comisión cobrada por Sistecrédito por cada venta financiada',
           (SELECT id FROM accounts_accounts WHERE pucCode='5305' AND deleted=0 LIMIT 1),
           (SELECT id FROM subaccounts WHERE pucCode='530520' AND store=1 AND deleted=0 LIMIT 1),
           1, NOW(), NOW()
) tmp
WHERE NOT EXISTS (SELECT 1 FROM expense_categories WHERE code = tmp.code AND deleted = 0);
