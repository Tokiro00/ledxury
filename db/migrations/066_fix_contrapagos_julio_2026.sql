-- ============================================================================
-- 066: Fechas reales de los contrapagos + duplicado de PAGO 11 (julio 2026)
--
-- Se ejecuta UNA sola vez, contra mamdb (producción). Idempotente.
--
-- OJO — ALCANCE REDUCIDO (20/08/2026):
-- La versión original de esta migración también corregía los montos y los
-- asientos de los lotes 36 (PAGO 14) y 38 (PAGO 15). Eso quedó SUPERADO por
-- db/scripts/corregir_contrapagos_registrados.php, que hace lo mismo de forma
-- genérica y calculada para los cinco lotes de julio–agosto (36, 38, 39, 40,
-- 41) y ya se aplicó en producción. Esas secciones se quitaron de aquí para
-- no corregir dos veces. Lo que queda son las piezas que ese script no toca:
--
--   1) Fechas: los movimientos de PAGO 12 a 15 quedaron con la fecha del día
--      en que se registraron (11/07 y 29/07) en vez de la de la consignación.
--      El libro del banco mostraba saldo negativo entre consignaciones.
--   2) PAGO 11 contado dos veces ($575.882): Interrapidísimo consignó el
--      19/06, o sea que esa plata ya venía dentro del ajuste de saldo del
--      30/06 (movimiento 62, creado el 09/07 para igualar el extracto), y al
--      registrarlo el 11/07 quedó duplicada. Se corrige solo en tesorería
--      porque la contabilidad del lote sí está bien.
--   3) Asientos de pagos de factura fechados el día de la digitación.
--   4) Textos "Dcto Inter:" -> "Dcto Interrapidísimo:".
-- ============================================================================

START TRANSACTION;

-- ── 1. Fechas reales de la consignación ────────────────────────────────────
-- Los UPDATE traen la fecha vieja en el WHERE, así que correrlos dos veces no
-- hace nada la segunda.

UPDATE cash_movements SET movementDate = '2026-07-03 12:00:00', updated_at = NOW()
WHERE idMovement = 65 AND DATE(movementDate) = '2026-07-11';   -- PAGO 12

UPDATE cash_movements SET movementDate = '2026-07-10 12:00:00', updated_at = NOW()
WHERE idMovement = 66 AND DATE(movementDate) = '2026-07-11';   -- PAGO 13

UPDATE cash_movements SET movementDate = '2026-07-17 12:00:00', updated_at = NOW()
WHERE idMovement = 72 AND DATE(movementDate) = '2026-07-29';   -- PAGO 14

UPDATE cash_movements SET movementDate = '2026-07-24 12:00:00', updated_at = NOW()
WHERE idMovement = 71 AND DATE(movementDate) = '2026-07-29';   -- PAGO 15

-- ── 2. Ajuste por el duplicado de PAGO 11 (sólo tesorería) ─────────────────
INSERT INTO cash_movements (sourceType, sourceId, movementType, amount, concept, category,
    documentNumber, movementDate, status, referenceType, referenceId, created_at, updated_at)
SELECT 'banco', 1, 'ajuste', -575882.00,
       'Ajuste: el contrapago PAGO 11 (Interrapidísimo consignó el 19/06) ya estaba incluido en el ajuste de saldo del 30/06 y volvió a registrarse el 11/07 — se descuenta el duplicado. No afecta contabilidad.',
       'ajuste', 'AJUSTE-DUP-PAGO11', '2026-07-11 12:18:13', 'activo', 'contrapago', 33, NOW(), NOW()
FROM (SELECT 1) g
WHERE NOT EXISTS (SELECT 1 FROM cash_movements c WHERE c.documentNumber = 'AJUSTE-DUP-PAGO11');

-- ── 3. Asientos de pagos de factura fechados el día de la digitación ───────
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

-- ── 4. Textos: la transportadora se llama Interrapidísimo, no "Inter" ──────
UPDATE cash_movements SET concept = REPLACE(concept, 'Dcto Inter:', 'Dcto Interrapidísimo:')
WHERE concept LIKE '%Dcto Inter:%';

UPDATE entries SET entryDescription = REPLACE(entryDescription, 'Dcto Inter:', 'Dcto Interrapidísimo:')
WHERE entryDescription LIKE '%Dcto Inter:%';

-- ── 5. Saldo de la cuenta bancaria ─────────────────────────────────────────
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
-- VERIFICACIÓN (correr después)
-- ============================================================================
-- SELECT idMovement, amount, movementDate FROM cash_movements
--   WHERE idMovement IN (65,66,71,72) OR documentNumber = 'AJUSTE-DUP-PAGO11'
--   ORDER BY movementDate;
--   -- esperado: 65 -> 03/07, 66 -> 10/07, 72 -> 17/07, 71 -> 24/07,
--   --           y el ajuste de -$575.882 el 11/07
--
-- SELECT SUM(entryDebitBalance) - SUM(entryCreditBalance) AS descuadre
--   FROM entries WHERE deleted = 0;   -- esperado 0
--
-- SELECT currentBalance FROM bank_accounts WHERE idBankAccount = 1;
