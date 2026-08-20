-- ============================================================================
-- 070: Cierra la brecha entre el banco de tesorería y el banco contable
--
-- Se ejecuta contra mamdb (producción). Idempotente: cada paso trae en el
-- WHERE el estado viejo, así que correrlo dos veces no hace nada la segunda.
--
-- MEDICIÓN DE PARTIDA (db/scripts/conciliar_banco_tesoreria_vs_contable.php):
--   tesorería  $23.731.761,18
--   contable   $26.967.643,18   (subcuenta 39, PUC 111005)
--   brecha     $ 3.235.882,00
--
-- Se descompone exacta, sin residuo, en tres cosas:
--
--  1. Publicidad Facebook del 30/06, $2.660.000 (expense_records id 3).
--  2. Parqueadero y gasolina del 09/07, $41.133,71 (expense_records id 2).
--     Ninguno de los dos tiene asiento: se crearon antes de que el módulo de
--     Gastos empezara a postear a contabilidad (el hook entró entre las
--     16:00:31 y las 16:01:26 del 11/07/2026 — el gasto id 4, de 16:01:26, sí
--     tiene causación y pago). Además de inflar el banco, faltan en el estado
--     de resultados.
--  3. PAGO 11 contado dos veces, $575.882. Interrapidísimo consignó el 19/06,
--     antes del corte del asiento de apertura del 01/07, así que esa plata ya
--     venía en el saldo de apertura; el asiento 12165 volvió a debitar el
--     banco. En tesorería ya se corrigió (AJUSTE-DUP-PAGO11). El crédito a
--     cartera de ese asiento SÍ es correcto — las facturas se cobraron de
--     verdad — así que solo se reversa la parte del banco, contra utilidades
--     acumuladas, la misma cuenta que usó la apertura.
--
-- Y dos arreglos que no mueven el saldo total pero sí los períodos:
--
--  4. El asiento de apertura (12132) quedó corto en $41.133,71: se calculó
--     contando el parqueadero del 09/07 como si fuera de junio, y sin el gasto
--     de Facebook del 30/06 (que se creó después). Pasa a $7.534.014,94.
--  5. Los asientos de contrapago de los lotes 34, 35, 36 y 38 siguieron con la
--     fecha de digitación después de que la migración 066 movió los
--     movimientos de tesorería a la fecha real de la consignación. La misma
--     plata aparecía en días distintos en el libro diario y en el del banco.
--
-- RESULTADO ESPERADO: banco contable $23.731.761,18 = tesorería, partida doble
-- global en cero.
--
-- Cuentas: 39 = 111005 banco · 42/aux 6010 = 220505 proveedores SIN PROVEEDOR
--          45 = 370501 utilidades acumuladas · 55 = 513540 fletes
--          56 = 513550 publicidad
-- ============================================================================

START TRANSACTION;

-- ── 1. Gasto de publicidad Facebook del 30/06 ($2.660.000) ─────────────────

INSERT INTO entries (userID, entryDescription, entryType,
    entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
    entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
    entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
    entryTransactionType, entryTransactionId, entryDate)
SELECT '71339095', 'Causación gasto: Pago de publicidad en facebook junio', 1,
    56, NULL, '2660000.00',
    42, 6010, '2660000.00',
    1, '71339095', NOW(), 0, 1,
    'expense_accrual', 3, '2026-06-30'
FROM (SELECT 1) g
WHERE NOT EXISTS (SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'expense_accrual'
                    AND e.entryTransactionId = 3 AND e.deleted = 0);

INSERT INTO entries (userID, entryDescription, entryType,
    entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
    entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
    entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
    entryTransactionType, entryTransactionId, entryDate)
SELECT '71339095', 'Pago gasto: Pago de publicidad en facebook junio', 1,
    42, 6010, '2660000.00',
    39, NULL, '2660000.00',
    1, '71339095', NOW(), 0, 1,
    'expense_payment', 3, '2026-06-30'
FROM (SELECT 1) g
WHERE NOT EXISTS (SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'expense_payment'
                    AND e.entryTransactionId = 3 AND e.deleted = 0);

-- ── 2. Gasto de parqueadero y gasolina del 09/07 ($41.133,71) ──────────────

INSERT INTO entries (userID, entryDescription, entryType,
    entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
    entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
    entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
    entryTransactionType, entryTransactionId, entryDate)
SELECT '71339095', 'Causación gasto: parqueadero y gasolina', 1,
    55, NULL, '41133.71',
    42, 6010, '41133.71',
    1, '71339095', NOW(), 0, 1,
    'expense_accrual', 2, '2026-07-09'
FROM (SELECT 1) g
WHERE NOT EXISTS (SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'expense_accrual'
                    AND e.entryTransactionId = 2 AND e.deleted = 0);

INSERT INTO entries (userID, entryDescription, entryType,
    entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
    entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
    entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
    entryTransactionType, entryTransactionId, entryDate)
SELECT '71339095', 'Pago gasto: parqueadero y gasolina', 1,
    42, 6010, '41133.71',
    39, NULL, '41133.71',
    1, '71339095', NOW(), 0, 1,
    'expense_payment', 2, '2026-07-09'
FROM (SELECT 1) g
WHERE NOT EXISTS (SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'expense_payment'
                    AND e.entryTransactionId = 2 AND e.deleted = 0);

-- Enlazar los asientos con el gasto, como hace el módulo.
UPDATE expense_records r
SET r.entry_id = (SELECT e.entryID FROM entries e
                  WHERE e.entryTransactionType = 'expense_accrual'
                    AND e.entryTransactionId = r.id AND e.deleted = 0 LIMIT 1),
    r.payment_entry_id = (SELECT e.entryID FROM entries e
                  WHERE e.entryTransactionType = 'expense_payment'
                    AND e.entryTransactionId = r.id AND e.deleted = 0 LIMIT 1),
    r.updated_at = NOW()
WHERE r.id IN (2, 3) AND r.deleted = 0;

-- ── 3. Reverso del banco en el duplicado de PAGO 11 ($575.882) ─────────────

INSERT INTO entries (userID, entryDescription, entryType,
    entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
    entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
    entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
    entryTransactionType, entryTransactionId, entryDate)
SELECT '71339095',
    'Reverso del banco en PAGO 11: Interrapidísimo consignó el 19/06, antes del corte, así que esa plata ya venía en el saldo de apertura del 01/07. El cobro a cartera sí es correcto y se conserva.',
    1,
    45, NULL, '575882.00',
    39, NULL, '575882.00',
    1, '71339095', NOW(), 0, 1,
    'contrapago_dup_reverso', 33, '2026-07-11'
FROM (SELECT 1) g
WHERE NOT EXISTS (SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'contrapago_dup_reverso'
                    AND e.entryTransactionId = 33 AND e.deleted = 0);

-- ── 4. Ajuste del asiento de apertura del banco ────────────────────────────
-- El WHERE trae el valor viejo: si ya se ajustó, no hace nada.

UPDATE entries
SET entryDebitBalance  = '7534014.94',
    entryCreditBalance = '7534014.94',
    entryDescription = CONCAT(entryDescription,
        ' [ajustado 20/08/2026: +41.133,71 — el cálculo original contó el parqueadero del 09/07 como si fuera de junio y no incluyó el gasto de Facebook del 30/06]'),
    updated_at = NOW()
WHERE entryID = 12132
  AND entryTransactionType = 'opening_balance'
  AND CAST(entryDebitBalance AS DECIMAL(18,2)) = 7492881.23;

-- ── 5. Fechas de los asientos de contrapago = fecha del movimiento ─────────

UPDATE entries e
JOIN cash_movements c ON c.referenceType = 'contrapago'
                     AND c.referenceId = e.entryTransactionId
                     AND c.movementType = 'ingreso'
                     AND c.deleted = 0 AND c.status <> 'anulado'
SET e.entryDate = DATE(c.movementDate), e.updated_at = NOW()
WHERE e.deleted = 0
  AND e.entryTransactionType LIKE 'contrapago%'
  AND e.entryDate <> DATE(c.movementDate);

-- ── 6. Recalcular saldos de subcuentas y auxiliares ────────────────────────

UPDATE subaccounts s SET
  s.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))), 0)
                     FROM entries e WHERE e.deleted = 0 AND e.entryDebitAccount  = s.id),
  s.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))), 0)
                     FROM entries e WHERE e.deleted = 0 AND e.entryCreditAccount = s.id)
WHERE s.deleted = 0;

UPDATE subaccounts
SET accountBalance = CASE WHEN accountSide = '1'
    THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
WHERE deleted = 0;

UPDATE auxiliary_subaccounts a SET
  a.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))), 0)
                     FROM entries e WHERE e.deleted = 0 AND e.entryDebitAuxaccount  = a.id),
  a.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))), 0)
                     FROM entries e WHERE e.deleted = 0 AND e.entryCreditAuxaccount = a.id)
WHERE a.deleted = 0;

UPDATE auxiliary_subaccounts
SET accountBalance = CASE WHEN accountSide = '1'
    THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
WHERE deleted = 0;

COMMIT;

-- ============================================================================
-- VERIFICACIÓN (correr después)
-- ============================================================================
-- SELECT (SELECT accountBalance FROM subaccounts WHERE id = 39)                  AS banco_contable,
--        (SELECT currentBalance FROM bank_accounts WHERE idBankAccount = 1)      AS banco_tesoreria,
--        (SELECT accountBalance FROM subaccounts WHERE id = 39)
--      - (SELECT currentBalance FROM bank_accounts WHERE idBankAccount = 1)      AS brecha;
--   -- esperado: 23731761.18 / 23731761.18 / 0.00
--
-- SELECT COALESCE(SUM(CAST(entryDebitBalance AS DECIMAL(18,2)))
--               - SUM(CAST(entryCreditBalance AS DECIMAL(18,2))), 0) AS descuadre
--   FROM entries WHERE deleted = 0;   -- esperado 0
--
-- SELECT id, pucCode, accountName, accountBalance FROM subaccounts
--   WHERE id IN (39,42,45,55,56) ORDER BY pucCode;
--   -- 513540 fletes  13.241.293,03   (13.200.159,32 + 41.133,71)
--   -- 513550 publicidad 4.460.000,00 (1.800.000 + 2.660.000)
--
-- SELECT id, code, entry_id, payment_entry_id, amount FROM expense_records WHERE id IN (2,3,4);
--   -- los tres deben tener causación y pago
