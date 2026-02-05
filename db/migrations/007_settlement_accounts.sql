-- Migration 007: Settlement Accounts (Cuentas para Liquidaciones de Vendedores)
-- Creates the necessary accounts for vendor settlements
-- PUC 2365: Costos y Gastos por Pagar
-- PUC 236505: Costos y Gastos por Pagar (subcuenta)

-- =====================================================
-- GRUPO 23: CUENTAS POR PAGAR
-- =====================================================

-- Verificar si ya existe el grupo 23 (Cuentas por Pagar)
SET @groupId23 = NULL;
SELECT id INTO @groupId23 FROM accounts_group WHERE pucCode = '23' LIMIT 1;

-- Si no existe, insertar grupo 23
INSERT INTO accounts_group (groupName, pucCode, deleted, created_at)
SELECT '23', '23', 0, NOW()
WHERE @groupId23 IS NULL
AND NOT EXISTS (SELECT 1 FROM accounts_group WHERE pucCode = '23');

-- Obtener el ID del grupo 23
SELECT id INTO @groupId23 FROM accounts_group WHERE pucCode = '23' LIMIT 1;

-- =====================================================
-- CUENTA 2365: COSTOS Y GASTOS POR PAGAR
-- =====================================================

-- Verificar si ya existe la cuenta 2365
SET @accountId2365 = NULL;
SELECT id INTO @accountId2365 FROM accounts_accounts WHERE pucCode = '2365' LIMIT 1;

-- Si no existe y tenemos el grupo, insertar cuenta 2365
INSERT INTO accounts_accounts (accountName, groupId, pucCode, deleted, created_at)
SELECT 'Costos y Gastos por Pagar', @groupId23, '2365', 0, NOW()
WHERE @groupId23 IS NOT NULL
AND @accountId2365 IS NULL
AND NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE pucCode = '2365');

-- Obtener el ID de la cuenta 2365
SELECT id INTO @accountId2365 FROM accounts_accounts WHERE pucCode = '2365' LIMIT 1;

-- =====================================================
-- SUBCUENTA 236505: COSTOS Y GASTOS POR PAGAR (VENDEDORES)
-- =====================================================

-- Verificar si ya existe la subcuenta 236505
SET @subaccountId236505 = NULL;
SELECT id INTO @subaccountId236505 FROM subaccounts WHERE pucCode = '236505' LIMIT 1;

-- Si no existe, insertar subcuenta 236505
INSERT INTO subaccounts (
    accountID,
    accountName,
    accountAccount,
    accountSide,
    accountBalance,
    accountDebit,
    accountCredit,
    accountOrder,
    accountStatus,
    accountStatement,
    accountType,
    pucCode,
    store,
    created_by,
    created_at,
    updated_at,
    deleted
)
SELECT
    236505,
    'Costos y Gastos por Pagar - Vendedores',
    @accountId2365,
    '2',        -- Crédito (pasivo)
    0.00,
    0.00,
    0.00,
    1,
    1,
    '1',        -- Balance
    'liability',
    '236505',
    1,          -- Store ID 1 (default)
    'system',
    NOW(),
    NOW(),
    0
WHERE @accountId2365 IS NOT NULL
AND @subaccountId236505 IS NULL
AND NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode = '236505');

-- =====================================================
-- ACTUALIZAR CUENTA DE COMISIONES SI FALTA accountType
-- =====================================================

UPDATE subaccounts
SET accountType = 'expense'
WHERE pucCode = '519505'
AND (accountType IS NULL OR accountType = '');

-- =====================================================
-- MENSAJE DE VERIFICACIÓN
-- =====================================================

SELECT
    'Verificación de cuentas para liquidaciones:' as mensaje,
    (SELECT COUNT(*) FROM subaccounts WHERE pucCode = '519505') as cuenta_comisiones_519505,
    (SELECT COUNT(*) FROM subaccounts WHERE pucCode = '236505') as cuenta_por_pagar_236505;
