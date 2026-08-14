-- ============================================================================
-- 066: Corrección de los contrapagos de julio 2026 (lotes 33, 34, 35, 36, 38)
--
-- Se ejecuta UNA sola vez, contra mamdb (producción). Es idempotente: si ya
-- corrió, los INSERT no se repiten y los UPDATE no encuentran filas.
--
-- QUÉ SE CORRIGE
--
-- 1) Lote 38 (PAGO 15, consignado el 24/07). Interrapidísimo cruzó dos facturas
--    de flete contra la consignación (Fra. 210430 $2.889.538,32 + Fra. 210579
--    $3.036.913 = $5.926.451,32) pero el lote se registró con descuento 0: el
--    banco quedó con $9.993.764 cuando a Bancolombia entraron $4.505.554,09.
--    Causa: la detección del descuento sólo entendía el formato "Dcto Factura
--    #x Por valor de $y" y el archivo venía como "Fra. 210430 $2.889.538,32 /
--    Fra. 210579 $3.036.913" (corregido en el código, commit f7b9006).
--
-- 2) Lotes 38 y 36: la plata que Interrapidísimo consignó por guías que no son
--    de Ledxury nunca se registró, aunque entró a la cuenta. Son $416.200 en el
--    lote 38 (3 guías MAM-Online $361.200 + guía 240055486543 $55.000 sin
--    factura) y $570.000 en el lote 36 (guía MAM-Online $515.000 + guía
--    240055812027 $55.000 cuya factura #3941 ya estaba cobrada). Queda como
--    CxP a compañías vinculadas (2230): es plata recibida que no es nuestra.
--
-- 3) Lote 36 (PAGO 14, consignado el 17/07): el banco quedó con $1.990.506
--    cuando entraron $2.558.226 — la diferencia es la plata de terceros del
--    punto anterior más su 4x1000.
--
-- 4) El 4x1000 del lote 38 se calculó sobre el bruto sin descontar los fletes
--    ($40.136) cuando el real fue $18.094,59.
--
-- 5) Lote 33 (PAGO 11): Interrapidísimo consignó el 19/06, o sea que esa plata
--    ya venía dentro del ajuste de saldo del 30/06 (movimiento 62, creado el
--    09/07 para igualar el extracto). Al registrarlo el 11/07 quedó contado dos
--    veces: $575.882. Se corrige sólo en tesorería con un ajuste, porque la
--    contabilidad del lote sí está bien.
--
-- 6) Lotes 34 y 35 (PAGO 12 y 13) y 36/38: los movimientos quedaron con la
--    fecha del registro en vez de la de la consignación, y el libro del banco
--    mostraba saldo negativo entre consignaciones. Se re-fechan al día real.
--
-- NO SE TOCA la cartera: los pagos a facturas de clientes están correctos en
-- los cuatro lotes (Interrapidísimo sí les cobró a esos clientes).
--
-- Cuadre de los asientos nuevos: DR 5135 $5.926.451,32 + DR 1110 $1.005.961,41
-- + DR 5305 $3.944,80 = CR 1110 $5.926.451,32 + CR 5305 $23.706,21 + CR 2230
-- $986.200,00 = $6.936.357,53.
-- Efecto neto en el banco: 1110 −$4.920.489,91 y tesorería −$5.496.371,91
-- (la diferencia son los $575.882 del duplicado, que no pasa por contabilidad).
-- ============================================================================

START TRANSACTION;

-- Guarda de idempotencia: 0 = todavía no se aplicó.
SET @ya := (SELECT COUNT(*) FROM entries
            WHERE entryTransactionType = 'contrapago_ajuste' AND entryTransactionId IN (36, 38));

-- ── 1. Asientos de corrección ──────────────────────────────────────────────
-- Cuentas: 39=1110 Bancolombia, 55=5135 Fletes, 58=5305 Gastos bancarios,
--          61=2230 CxP a compañías vinculadas.

INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
SELECT '71339095', 'Fletes Interrapidísimo descontados de la consignación — Fra. 210430 + Fra. 210579 (lote #38: se había registrado sin descuento)',
       '2026-07-24', 1, 1, 'contrapago_ajuste', 38, 55, 5926451.32, 39, 5926451.32, 1, '71339095', NOW(), 0
FROM (SELECT 1) g WHERE @ya = 0;

INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
SELECT '71339095', 'Contrapagos cobrados por cuenta de terceros — 3 guías MAM-Online + guía 240055486543 sin factura (lote #38)',
       '2026-07-24', 1, 1, 'contrapago_ajuste', 38, 39, 414535.20, 61, 414535.20, 1, '71339095', NOW(), 0
FROM (SELECT 1) g WHERE @ya = 0;

INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
SELECT '71339095', '4x1000 sobre la porción cobrada por cuenta de terceros (lote #38)',
       '2026-07-24', 1, 1, 'contrapago_ajuste', 38, 58, 1664.80, 61, 1664.80, 1, '71339095', NOW(), 0
FROM (SELECT 1) g WHERE @ya = 0;

INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
SELECT '71339095', 'Reverso del 4x1000 calculado sobre el bruto sin descontar los fletes (lote #38)',
       '2026-07-24', 1, 1, 'contrapago_ajuste', 38, 39, 23706.21, 58, 23706.21, 1, '71339095', NOW(), 0
FROM (SELECT 1) g WHERE @ya = 0;

INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
SELECT '71339095', 'Contrapagos cobrados por cuenta de terceros — guía MAM-Online $515.000 + guía 240055812027 $55.000 por identificar (lote #36)',
       '2026-07-17', 1, 1, 'contrapago_ajuste', 36, 39, 567720.00, 61, 567720.00, 1, '71339095', NOW(), 0
FROM (SELECT 1) g WHERE @ya = 0;

INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
SELECT '71339095', '4x1000 sobre la porción cobrada por cuenta de terceros (lote #36)',
       '2026-07-17', 1, 1, 'contrapago_ajuste', 36, 58, 2280.00, 61, 2280.00, 1, '71339095', NOW(), 0
FROM (SELECT 1) g WHERE @ya = 0;

-- ── 2. Movimientos de tesorería ────────────────────────────────────────────
-- Los UPDATE traen el valor viejo en el WHERE, así que correrlos dos veces no
-- hace nada la segunda.

UPDATE cash_movements SET
    amount = 4505554.09,
    movementDate = '2026-07-24 12:00:00',
    concept = 'Pago contrapago Interrapidísimo - PAGO 15 (101 guías) | Mov: 29072026 | Dcto Interrapidísimo: Fra. 210430 $2.889.538 + Fra. 210579 $3.036.913 | Bruto: $10.450.100 - Dcto: $5.926.451 - 4x1000: $18.095 = $4.505.554 | Incluye $416.200 cobrados por cuenta de terceros',
    updated_at = NOW()
WHERE idMovement = 71 AND amount = 9993764.00;

UPDATE cash_movements SET
    amount = 2558226.00,
    movementDate = '2026-07-17 12:00:00',
    concept = 'Pago contrapago Interrapidísimo - PAGO 14 (25 guías) | Mov: 29072026 | Bruto: $2.568.500 - 4x1000: $10.274 = $2.558.226 | Incluye $570.000 cobrados por cuenta de terceros',
    updated_at = NOW()
WHERE idMovement = 72 AND amount = 1990506.00;

-- PAGO 12 y PAGO 13: fecha real de la consignación.
UPDATE cash_movements SET movementDate = '2026-07-03 12:00:00', updated_at = NOW()
WHERE idMovement = 65 AND DATE(movementDate) = '2026-07-11';

UPDATE cash_movements SET movementDate = '2026-07-10 12:00:00', updated_at = NOW()
WHERE idMovement = 66 AND DATE(movementDate) = '2026-07-11';

-- ── 3. Ajuste por el duplicado de PAGO 11 (sólo tesorería) ─────────────────
INSERT INTO cash_movements (sourceType, sourceId, movementType, amount, concept, category,
    documentNumber, movementDate, status, referenceType, referenceId, created_at, updated_at)
SELECT 'banco', 1, 'ajuste', -575882.00,
       'Ajuste: el contrapago PAGO 11 (Interrapidísimo consignó el 19/06) ya estaba incluido en el ajuste de saldo del 30/06 y volvió a registrarse el 11/07 — se descuenta el duplicado. No afecta contabilidad.',
       'ajuste', 'AJUSTE-DUP-PAGO11', '2026-07-11 12:18:13', 'activo', 'contrapago', 33, NOW(), NOW()
FROM (SELECT 1) g
WHERE NOT EXISTS (SELECT 1 FROM cash_movements c WHERE c.documentNumber = 'AJUSTE-DUP-PAGO11');

-- ── 4. Textos: la transportadora se llama Interrapidísimo, no "Inter" ──────
UPDATE cash_movements SET concept = REPLACE(concept, 'Dcto Inter:', 'Dcto Interrapidísimo:')
WHERE concept LIKE '%Dcto Inter:%';

UPDATE entries SET entryDescription = REPLACE(entryDescription, 'Dcto Inter:', 'Dcto Interrapidísimo:')
WHERE entryDescription LIKE '%Dcto Inter:%';

-- ── 4b. Asientos de pagos de factura fechados el día de la digitación ──────
-- recordPayment() no le pasaba la fecha del pago a createEntry, así que el
-- asiento salía con date('Y-m-d') = hoy. Los pagos del 30 y 31 de julio
-- ($808.000) quedaron contabilizados el 04/08, o sea en agosto: al 31/07 el
-- banco contable estaba corto y la cartera inflada en ese valor.
-- (El código ya quedó corregido; esto arregla los asientos ya creados.)
UPDATE entries e
JOIN payments p ON p.idPayment = e.entryTransactionId
SET e.entryDate = DATE(p.date)
WHERE e.entryTransactionType = 'payment'
  AND e.deleted = 0 AND p.deleted = 0
  AND p.date >= '2026-07-01'
  AND e.entryDate <> DATE(p.date);

-- ── 5. Saldos denormalizados ───────────────────────────────────────────────
-- Se recalculan desde los asientos (idempotente). Si traían arrastre previo,
-- este paso también lo corrige: comparar con el SELECT de verificación de abajo.
UPDATE subaccounts s SET
    s.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance), 0)  FROM entries e WHERE e.entryDebitAccount  = s.id AND e.deleted = 0),
    s.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance), 0) FROM entries e WHERE e.entryCreditAccount = s.id AND e.deleted = 0)
WHERE s.id IN (39, 55, 58, 61);

UPDATE subaccounts SET
    accountBalance = CASE WHEN accountSide = '1' THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
WHERE id IN (39, 55, 58, 61);

-- ── 6. Saldo de la cuenta bancaria ─────────────────────────────────────────
-- currentBalance = saldo inicial + efecto de los movimientos, con la misma
-- fórmula de Cashmovements_model::getLedgerBySource. Esto también cierra el
-- arrastre de $508.000 que traía la cuenta.
UPDATE bank_accounts b SET b.currentBalance = (
    SELECT b.initialBalance + COALESCE(SUM(CASE
        WHEN c.movementType IN ('ingreso','apertura') AND c.sourceType = 'banco' AND c.sourceId = b.idBankAccount THEN c.amount
        WHEN c.movementType IN ('egreso','cierre')    AND c.sourceType = 'banco' AND c.sourceId = b.idBankAccount THEN -c.amount
        WHEN c.movementType = 'transferencia'         AND c.sourceType = 'banco' AND c.sourceId = b.idBankAccount THEN -c.amount
        WHEN c.movementType = 'transferencia'         AND c.destinationType = 'banco' AND c.destinationId = b.idBankAccount THEN c.amount
        WHEN c.movementType = 'ajuste'                AND c.sourceType = 'banco' AND c.sourceId = b.idBankAccount THEN c.amount
        ELSE 0 END), 0)
    FROM cash_movements c
    WHERE c.deleted = 0 AND c.status <> 'anulado'
      AND ((c.sourceType = 'banco' AND c.sourceId = b.idBankAccount)
        OR (c.destinationType = 'banco' AND c.destinationId = b.idBankAccount AND c.movementType = 'transferencia'))
) WHERE b.idBankAccount = 1;

COMMIT;

-- ============================================================================
-- VERIFICACIÓN (correr después; deben cuadrar)
-- ============================================================================
-- SELECT idMovement, amount, movementDate FROM cash_movements WHERE idMovement IN (65,66,71,72)
--   OR documentNumber = 'AJUSTE-DUP-PAGO11' ORDER BY movementDate;
--   -- esperado: 65 -> 03/07, 66 -> 10/07, 72 -> 17/07 $2.558.226, 71 -> 24/07 $4.505.554,09,
--   --           ajuste -$575.882 el 11/07
--
-- SELECT sa.accountID, sa.accountName, sa.accountDebit, sa.accountCredit, sa.accountBalance
--   FROM subaccounts sa WHERE sa.id IN (39,55,58,61);
--   -- esperado 2230 CxP vinculadas con saldo crédito $986.200
--
-- SELECT b.currentBalance FROM bank_accounts b WHERE b.idBankAccount = 1;
--
-- SELECT SUM(entryDebitBalance) - SUM(entryCreditBalance) AS descuadre FROM entries WHERE deleted = 0;
--   -- esperado 0
