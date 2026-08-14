-- =============================================================================
-- 057: Categoría de gasto "Devoluciones a clientes"
-- =============================================================================
-- Opción A (recomendada): vincular a la subcuenta 4175 / 417505 existente,
-- que es la contra-cuenta de ingresos en PUC Colombia. El asiento al
-- registrar un gasto de esta categoría será:
--   DR: 417505 Devoluciones en ventas (reduce ingresos netos)
--   CR: Caja/Banco
-- Esto mantiene el reporte de Ventas Netas correcto (bruto − devoluciones)
-- sin inflar la línea de gastos operativos.
-- =============================================================================

-- Verificar que la subcuenta existe antes de insertar.
-- Si por alguna razón en prod no existiera la subaccount id=47, ajustar.
INSERT INTO expense_categories
    (code, name, description, accounting_account_id, accounting_subaccount_id, is_active, deleted, created_at, updated_at)
SELECT
    'GAS-DEV',
    'Devoluciones a clientes',
    'Reembolsos y devoluciones de dinero a clientes. Contablemente reduce ingresos netos (PUC 4175).',
    aa.id,
    sa.id,
    1,
    0,
    NOW(),
    NOW()
FROM accounts_accounts aa
JOIN subaccounts sa ON sa.accountID = aa.accountID
WHERE aa.accountID = '4175' AND aa.accountName LIKE '%Devoluciones%'
  AND sa.pucCode = '417505'
  AND NOT EXISTS (SELECT 1 FROM expense_categories WHERE code = 'GAS-DEV')
LIMIT 1;

SELECT id, code, name, accounting_account_id, accounting_subaccount_id
FROM expense_categories WHERE code = 'GAS-DEV';
