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
--    Usa FROM DUAL + WHERE NOT EXISTS para compatibilidad con MariaDB.
INSERT INTO accounts_accounts (accountID, groupID, accountName, pucCode, created_at)
SELECT * FROM (SELECT 2335 AS accountID, 23 AS groupID, 'Costos y gastos por pagar' AS accountName, '2335' AS pucCode, NOW() AS created_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE accountID=2335);

-- 2) Subcuenta 510528 Comisiones operadores bot (DR, gasto, bajo account=5105)
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, store, pucCode, created_at)
SELECT * FROM (SELECT 5105 AS accountID, 'Comisiones operadores bot' AS accountName, 510528 AS accountAccount, 1 AS accountSide, 0 AS accountBalance, 0 AS accountDebit, 0 AS accountCredit, 16 AS accountOrder, 1 AS accountStatus, 2 AS accountStatement, 'expense' AS accountType, 1 AS store, '510528' AS pucCode, NOW() AS created_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode='510528' AND store=1);

-- 3) Subcuenta 233525 Comisiones bots por pagar (CR, pasivo, bajo account=2335)
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, store, pucCode, created_at)
SELECT * FROM (SELECT 2335 AS accountID, 'Comisiones bots por pagar' AS accountName, 233525 AS accountAccount, 2 AS accountSide, 0 AS accountBalance, 0 AS accountDebit, 0 AS accountCredit, 5 AS accountOrder, 1 AS accountStatus, 1 AS accountStatement, 'liability' AS accountType, 1 AS store, '233525' AS pucCode, NOW() AS created_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode='233525' AND store=1);

-- Verificación
SELECT id, accountID, pucCode, accountName, accountSide, accountType, store
FROM subaccounts
WHERE pucCode IN ('510528','233525')
ORDER BY pucCode;
