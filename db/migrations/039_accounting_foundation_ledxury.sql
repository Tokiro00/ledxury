-- 039: Bootstrap del módulo contable de Ledxury
--
-- Estado actual: las migraciones 001-013 que diseñaron el PUC nunca se
-- aplicaron en local ni en producción. La tabla `accounting_settings` está
-- vacía, no hay subcuentas con `pucCode`, y el chart-of-accounts solo tiene
-- clases 1-3 (sin Ingresos, Gastos, Costos). Resultado: cada llamada a
-- recordInvoice/recordPayment/recordExpense falla en silencio.
--
-- Esta migración pone los cimientos limpios para una sola entidad (Ledxury
-- SAS), una sola bodega (Medellín, store=1), sin IVA ni retenciones (módulo
-- de impuestos se desarrolla luego). El balance inicial se carga desde la
-- UI existente de Apertura.
--
-- Idempotente: usa ADD COLUMN IF NOT EXISTS y NOT EXISTS-guards en INSERTs.

-- ── 1. Schema additions (pucCode, accountType en chart-of-accounts) ────────
ALTER TABLE subaccounts
    ADD COLUMN IF NOT EXISTS accountType VARCHAR(50) NULL COMMENT 'asset, liability, equity, revenue, expense, cost' AFTER accountStatement,
    ADD COLUMN IF NOT EXISTS pucCode     VARCHAR(20) NULL COMMENT 'Código PUC Colombia',
    ADD INDEX IF NOT EXISTS idx_puc_store (pucCode, store),
    ADD INDEX IF NOT EXISTS idx_puc_sub (pucCode);

ALTER TABLE auxiliary_subaccounts
    ADD COLUMN IF NOT EXISTS accountType VARCHAR(50) NULL COMMENT 'client, provider, employee, other' AFTER accountStatement;

ALTER TABLE accounts_class
    ADD COLUMN IF NOT EXISTS pucCode VARCHAR(20) NULL COMMENT 'Código PUC clase (1 dígito)',
    ADD INDEX IF NOT EXISTS idx_puc_class (pucCode);

ALTER TABLE accounts_group
    ADD COLUMN IF NOT EXISTS pucCode VARCHAR(20) NULL COMMENT 'Código PUC grupo (2 dígitos)',
    ADD INDEX IF NOT EXISTS idx_puc_group (pucCode);

ALTER TABLE accounts_accounts
    ADD COLUMN IF NOT EXISTS pucCode VARCHAR(20) NULL COMMENT 'Código PUC cuenta (4 dígitos)',
    ADD INDEX IF NOT EXISTS idx_puc_account (pucCode);

-- ── 2. Marcar pucCode en clases existentes (Activo/Pasivo/Patrimonio store=1) ─
UPDATE accounts_class SET pucCode='1' WHERE className LIKE '%Activo%'      AND store=1 AND pucCode IS NULL AND deleted=0;
UPDATE accounts_class SET pucCode='2' WHERE className LIKE '%Pasivo%'      AND store=1 AND pucCode IS NULL AND deleted=0;
UPDATE accounts_class SET pucCode='3' WHERE className LIKE '%Patrimonio%'  AND store=1 AND pucCode IS NULL AND deleted=0;

-- ── 3. Insertar clases 4 (Ingresos), 5 (Gastos), 6 (Costos) para Medellín ──
INSERT INTO accounts_class (classID, className, classDescription, store, pucCode, created_at)
SELECT * FROM (
    SELECT 4 AS classID, 'Ingresos Medellín'         AS className, 'Cuentas de ingresos operacionales y no operacionales' AS classDescription, 1 AS store, '4' AS pucCode, NOW() AS created_at
    UNION ALL SELECT 5, 'Gastos Medellín',            'Gastos operacionales de administración y ventas',                  1, '5', NOW()
    UNION ALL SELECT 6, 'Costos de Ventas Medellín',  'Costos asociados a la mercancía vendida',                          1, '6', NOW()
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_class c WHERE c.pucCode = tmp.pucCode AND c.store = tmp.store AND c.deleted = 0);

-- ── 4. Insertar grupos PUC ─────────────────────────────────────────────────
INSERT INTO accounts_group (groupID, classID, groupName, groupDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 11 AS groupID, 1 AS classID, 'Disponible'                       AS groupName, 'Caja, bancos'                     AS groupDescription, '11' AS pucCode, NOW() AS created_at
    UNION ALL SELECT 13, 1, 'Deudores',                          'Cuentas por cobrar',              '13', NOW()
    UNION ALL SELECT 14, 1, 'Inventarios',                       'Mercancías para la venta',        '14', NOW()
    UNION ALL SELECT 22, 2, 'Proveedores',                       'Obligaciones con proveedores',    '22', NOW()
    UNION ALL SELECT 31, 3, 'Capital social',                    'Aportes de capital',              '31', NOW()
    UNION ALL SELECT 36, 3, 'Resultados del ejercicio',          'Utilidad/pérdida del ejercicio',  '36', NOW()
    UNION ALL SELECT 37, 3, 'Resultados ejercicios anteriores',  'Utilidades acumuladas',           '37', NOW()
    UNION ALL SELECT 41, 4, 'Operacionales',                     'Ingresos operacionales',          '41', NOW()
    UNION ALL SELECT 51, 5, 'Operacionales de administración',   'Gastos operativos',               '51', NOW()
    UNION ALL SELECT 61, 6, 'Costo de ventas',                   'Costo de mercancía vendida',      '61', NOW()
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_group g WHERE g.pucCode = tmp.pucCode AND g.deleted = 0);

-- ── 5. Insertar cuentas (4 dígitos) ────────────────────────────────────────
INSERT INTO accounts_accounts (accountID, groupID, accountName, accountDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 1105 AS accountID, 11 AS groupID, 'Caja'                                          AS accountName, 'Dinero en efectivo'                          AS accountDescription, '1105' AS pucCode, NOW() AS created_at
    UNION ALL SELECT 1110, 11, 'Bancos',                                  'Cuentas bancarias',                          '1110', NOW()
    UNION ALL SELECT 1305, 13, 'Clientes',                                'Cuentas por cobrar a clientes',              '1305', NOW()
    UNION ALL SELECT 1435, 14, 'Mercancías no fabricadas por la empresa', 'Inventario para la venta',                   '1435', NOW()
    UNION ALL SELECT 2205, 22, 'Proveedores nacionales',                  'Obligaciones con proveedores nacionales',    '2205', NOW()
    UNION ALL SELECT 3105, 31, 'Capital suscrito y pagado',               'Capital pagado por los socios',              '3105', NOW()
    UNION ALL SELECT 3605, 36, 'Utilidad del ejercicio',                  'Utilidad neta del periodo',                  '3605', NOW()
    UNION ALL SELECT 3705, 37, 'Utilidades acumuladas',                   'Utilidades retenidas de periodos anteriores','3705', NOW()
    UNION ALL SELECT 4135, 41, 'Comercio al por menor',                   'Ventas al detal',                            '4135', NOW()
    UNION ALL SELECT 4175, 41, 'Devoluciones en ventas',                  'Notas crédito sobre ventas',                 '4175', NOW()
    UNION ALL SELECT 5135, 51, 'Servicios',                               'Servicios públicos, hosting, logística',     '5135', NOW()
    UNION ALL SELECT 5195, 51, 'Diversos',                                'Gastos varios no clasificados',              '5195', NOW()
    UNION ALL SELECT 6135, 61, 'Costo de comercio al por menor',          'Costo de mercancía vendida al detal',        '6135', NOW()
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts a WHERE a.pucCode = tmp.pucCode AND a.deleted = 0);

-- ── 6. Subcuentas postables (las hojas que tocan los asientos) ─────────────
-- accountSide: '1' = Débito (naturaleza débito), '2' = Crédito (naturaleza crédito)
-- accountStatement: '1' = Balance, '2' = Estado de Resultados (P&L)
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, pucCode, store, created_at)
SELECT * FROM (
    -- ACTIVOS (Débito, Balance)
    SELECT 1105 AS accountID, 'Caja general'                              AS accountName, 110505 AS accountAccount, '1' AS accountSide, 0.00 AS accountBalance, 0.00 AS accountDebit, 0.00 AS accountCredit, 1  AS accountOrder, 1 AS accountStatus, '1' AS accountStatement, 'asset'     AS accountType, '110505' AS pucCode, 1 AS store, NOW() AS created_at
    UNION ALL SELECT 1110, 'Bancolombia cuenta corriente',         111005, '1', 0.00, 0.00, 0.00, 2,  1, '1', 'asset',     '111005', 1, NOW()
    UNION ALL SELECT 1305, 'Clientes nacionales',                  130505, '1', 0.00, 0.00, 0.00, 3,  1, '1', 'asset',     '130505', 1, NOW()
    UNION ALL SELECT 1435, 'Inventario mercancías',                143501, '1', 0.00, 0.00, 0.00, 4,  1, '1', 'asset',     '143501', 1, NOW()
    -- PASIVOS (Crédito, Balance)
    UNION ALL SELECT 2205, 'Proveedores nacionales',               220505, '2', 0.00, 0.00, 0.00, 5,  1, '1', 'liability', '220505', 1, NOW()
    -- PATRIMONIO (Crédito, Balance)
    UNION ALL SELECT 3105, 'Capital suscrito y pagado',            310501, '2', 0.00, 0.00, 0.00, 6,  1, '1', 'equity',    '310501', 1, NOW()
    UNION ALL SELECT 3605, 'Utilidad del ejercicio',               360501, '2', 0.00, 0.00, 0.00, 7,  1, '1', 'equity',    '360501', 1, NOW()
    UNION ALL SELECT 3705, 'Utilidades acumuladas',                370501, '2', 0.00, 0.00, 0.00, 8,  1, '1', 'equity',    '370501', 1, NOW()
    -- INGRESOS (Crédito, P&L)
    UNION ALL SELECT 4135, 'Ventas comercio al por menor',         413505, '2', 0.00, 0.00, 0.00, 9,  1, '2', 'revenue',   '413505', 1, NOW()
    UNION ALL SELECT 4175, 'Devoluciones en ventas',               417505, '1', 0.00, 0.00, 0.00, 10, 1, '2', 'revenue',   '417505', 1, NOW()
    -- GASTOS (Débito, P&L)
    UNION ALL SELECT 5135, 'Servicios (logística, hosting, etc)',  513505, '1', 0.00, 0.00, 0.00, 11, 1, '2', 'expense',   '513505', 1, NOW()
    UNION ALL SELECT 5195, 'Gastos diversos',                      519505, '1', 0.00, 0.00, 0.00, 12, 1, '2', 'expense',   '519505', 1, NOW()
    -- COSTO DE VENTAS (Débito, P&L)
    UNION ALL SELECT 6135, 'Costo de mercancía vendida',           613501, '1', 0.00, 0.00, 0.00, 13, 1, '2', 'cost',      '613501', 1, NOW()
) tmp
WHERE NOT EXISTS (SELECT 1 FROM subaccounts s WHERE s.pucCode = tmp.pucCode AND s.store = tmp.store AND s.deleted = 0);

-- ── 7. accounting_settings: mapeo key → subaccount.id ──────────────────────
INSERT INTO accounting_settings (setting_key, subaccount_id, created_at, updated_at)
SELECT setting_key, sub_id, NOW() AS ca, NOW() AS ua FROM (
    SELECT 'account_cash'          AS setting_key, (SELECT id FROM subaccounts WHERE pucCode='110505' AND store=1 AND deleted=0 LIMIT 1) AS sub_id
    UNION ALL SELECT 'account_bank',          (SELECT id FROM subaccounts WHERE pucCode='111005' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_receivable',    (SELECT id FROM subaccounts WHERE pucCode='130505' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_inventory',     (SELECT id FROM subaccounts WHERE pucCode='143501' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_payable',       (SELECT id FROM subaccounts WHERE pucCode='220505' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_capital',       (SELECT id FROM subaccounts WHERE pucCode='310501' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_profit_period', (SELECT id FROM subaccounts WHERE pucCode='360501' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_retained',      (SELECT id FROM subaccounts WHERE pucCode='370501' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_revenue',       (SELECT id FROM subaccounts WHERE pucCode='413505' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_refund',        (SELECT id FROM subaccounts WHERE pucCode='417505' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_services',      (SELECT id FROM subaccounts WHERE pucCode='513505' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_diversos',      (SELECT id FROM subaccounts WHERE pucCode='519505' AND store=1 AND deleted=0 LIMIT 1)
    UNION ALL SELECT 'account_cogs',          (SELECT id FROM subaccounts WHERE pucCode='613501' AND store=1 AND deleted=0 LIMIT 1)
) tmp
ON DUPLICATE KEY UPDATE subaccount_id = VALUES(subaccount_id), updated_at = NOW();
