-- 043: Brechas finales antes de la apertura inicial
--
-- 1. Agrega categoría de gasto "Otros gastos diversos" (faltaba mapear a 519505)
-- 2. Crea cuentas y subcuentas para intercompañías (vinculados económicos):
--    - 1325 / 132505 — CxC Vinculados (saldo a favor con MAM-Online, etc)
--    - 2230 / 223005 — CxP Vinculadas (deuda con MAM Medellín, etc)
-- 3. Mapea 2 keys nuevas en accounting_settings:
--    - account_intercompany_receivable → 132505
--    - account_intercompany_payable    → 223005
--
-- Conviven con la tabla intercompany_movements (creada en migración 034)
-- que tracker los movimientos operativos entre Ledxury <-> MAM. Las
-- subcuentas 132505/223005 son la representación contable consolidada
-- en el GL/Balance.

-- ── 1. Cuenta 1325 bajo grupo 13 (Deudores) ────────────────────────────────
INSERT INTO accounts_accounts (accountID, groupID, accountName, accountDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 1325 AS accountID, 13 AS groupID, 'Cuentas por cobrar a vinculados económicos' AS accountName,
           'CxC con compañías vinculadas (MAM Medellín, MAM-Online, etc)' AS accountDescription,
           '1325' AS pucCode, NOW() AS created_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE pucCode='1325' AND deleted=0);

-- ── 2. Cuenta 2230 bajo grupo 22 (Proveedores → ampliado a obligaciones) ──
INSERT INTO accounts_accounts (accountID, groupID, accountName, accountDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 2230 AS accountID, 22 AS groupID, 'Compañías vinculadas' AS accountName,
           'CxP con compañías vinculadas (MAM Medellín, MAM-Online, etc)' AS accountDescription,
           '2230' AS pucCode, NOW() AS created_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE pucCode='2230' AND deleted=0);

-- ── 3. Subcuentas postables ────────────────────────────────────────────────
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, pucCode, store, created_at)
SELECT * FROM (
    -- 132505 — Activo (débito), Balance (statement '1')
    SELECT 1325 AS accountID, 'CxC vinculados económicos' AS accountName, 132505 AS accountAccount, '1' AS accountSide, 0.00 AS accountBalance, 0.00 AS accountDebit, 0.00 AS accountCredit, 20 AS accountOrder, 1 AS accountStatus, '1' AS accountStatement, 'asset' AS accountType, '132505' AS pucCode, 1 AS store, NOW() AS created_at
    -- 223005 — Pasivo (crédito), Balance (statement '1')
    UNION ALL SELECT 2230, 'CxP a compañías vinculadas',          223005, '2', 0.00, 0.00, 0.00, 21, 1, '1', 'liability', '223005', 1, NOW()
) tmp
WHERE NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode = tmp.pucCode AND store = tmp.store AND deleted = 0);

-- ── 4. accounting_settings ─────────────────────────────────────────────────
INSERT INTO accounting_settings (setting_key, subaccount_id, created_at, updated_at)
SELECT setting_key, sub_id, NOW(), NOW() FROM (
    SELECT 'account_intercompany_receivable' AS setting_key, (SELECT id FROM subaccounts WHERE pucCode='132505' AND store=1 AND deleted=0 LIMIT 1) AS sub_id
    UNION ALL SELECT 'account_intercompany_payable', (SELECT id FROM subaccounts WHERE pucCode='223005' AND store=1 AND deleted=0 LIMIT 1)
) tmp
ON DUPLICATE KEY UPDATE subaccount_id = VALUES(subaccount_id), updated_at = NOW();

-- ── 5. expense_category faltante: Otros gastos diversos ────────────────────
INSERT INTO expense_categories (code, name, description, accounting_account_id, accounting_subaccount_id, is_active, created_at, updated_at)
SELECT * FROM (
    SELECT 'GAS-OTR' AS code, 'Otros gastos diversos' AS name,
           'Gastos varios no clasificados en categorías específicas' AS description,
           (SELECT id FROM accounts_accounts WHERE pucCode='5195' AND deleted=0 LIMIT 1) AS accounting_account_id,
           (SELECT id FROM subaccounts WHERE pucCode='519505' AND store=1 AND deleted=0 LIMIT 1) AS accounting_subaccount_id,
           1 AS is_active, NOW() AS created_at, NOW() AS updated_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM expense_categories WHERE code = tmp.code AND deleted = 0);
