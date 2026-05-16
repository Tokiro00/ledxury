-- 056_bot_commission_accounting.sql
-- Agrega cuentas PUC para devengar comisiones de bots por factura cobrada.
--
-- Modelo:
--   Cuando una factura se marca como pagada (state=2), se genera un asiento
--   por cada bot_commission_config activa:
--     DR: 510528 Comisiones operadores bot         (gasto, clase 5)
--     CR: 233525 Comisiones bots por pagar         (pasivo, clase 2)
--                + aux por persona (Germam, Cano, Christina, etc.)
--
--   Cuando se le paga a la persona:
--     DR: 233525 Comisiones bots por pagar + aux
--     CR: Caja/Banco
--
-- Las cuentas se crean separadas de 510527 (Comisiones a empleados nómina)
-- y 220505 (Proveedores) para que aparezcan como líneas propias en Estado
-- de Resultados y Balance — los operadores de bot son contratistas, no
-- empleados ni proveedores ordinarios.

-- Jerarquía PUC: class(1) → group(2) → account(4) → subaccount(6)
-- Ya existe group=23 "Cuentas por pagar"; agregamos account=2335 y subaccount=233525.

-- 1) accounts_accounts row para 2335 (account level 4 bajo grupo 23)
INSERT INTO accounts_accounts (accountID, groupID, accountName, pucCode, created_at)
SELECT 2335, 23, 'Costos y gastos por pagar', '2335', NOW()
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE accountID=2335);

-- 2) Subcuenta 510528 Comisiones operadores bot (DR, gasto, bajo account=5105)
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, store, pucCode, created_at)
SELECT 5105, 'Comisiones operadores bot', 510528, 1, 0, 0, 0, 16, 1, 2, 'expense', 1, '510528', NOW()
WHERE NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode='510528' AND store=1);

-- 3) Subcuenta 233525 Comisiones bots por pagar (CR, pasivo, bajo account=2335)
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, store, pucCode, created_at)
SELECT 2335, 'Comisiones bots por pagar', 233525, 2, 0, 0, 0, 5, 1, 1, 'liability', 1, '233525', NOW()
WHERE NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode='233525' AND store=1);

-- Verificación
SELECT id, accountID, pucCode, accountName, accountSide, accountType, store
FROM subaccounts
WHERE pucCode IN ('510528','233525')
ORDER BY pucCode;
