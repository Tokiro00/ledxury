<?php
/**
 * Cierra los pendientes de julio en tesorería y contabilidad que la corrección
 * de montos (corregir_contrapagos_registrados.php) no toca.
 *
 * Ejecutar en el server de ledxury:
 *   php corregir_fechas_contrapagos_julio.php            (simulación)
 *   php corregir_fechas_contrapagos_julio.php --apply
 *
 * QUÉ HACE
 *  1. Fechas reales de la consignación en los movimientos de PAGO 12 a 15.
 *     Quedaron con la fecha en que se digitaron (11/07 y 29/07), y por eso el
 *     libro del banco mostraba saldo negativo entre consignaciones.
 *  2. Ajuste por el duplicado de PAGO 11 ($575.882). Interrapidísimo consignó
 *     el 19/06, así que esa plata ya venía dentro del ajuste de saldo del 30/06
 *     (movimiento 62, creado el 09/07 para igualar el extracto); al registrar
 *     el lote el 11/07 quedó contada dos veces. Solo tesorería: la
 *     contabilidad del lote sí está bien.
 *  3. Asientos de pagos de factura fechados el día de la digitación.
 *     recordPayment() no le pasaba la fecha del pago a createEntry, así que los
 *     pagos del 30 y 31 de julio quedaron contabilizados en agosto.
 *  4. Textos "Dcto Inter:" -> "Dcto Interrapidísimo:".
 *  5. Recalcula currentBalance de la cuenta con la misma fórmula del libro.
 *
 * Idempotente: cada paso trae en el WHERE el estado viejo, así que correrlo dos
 * veces no hace nada la segunda.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n\n" : "=== SIMULACION (sin --apply no escribe nada) ===\n\n";

function q($m, $sql) { $r = $m->query($sql); if ($r === false) { echo "   ERROR: {$m->error}\n"; return false; } return $r; }
function rows($m, $sql) { $r = q($m, $sql); if (!$r) return array(); $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o; }
function money($v) { return '$' . number_format((float)$v, 2, ',', '.'); }

$run = function ($m, $APPLY, $label, $sql) {
    echo "  {$label}\n";
    if (!$APPLY) { echo "     [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 110)) . "\n"; return 0; }
    if (q($m, $sql) === false) return -1;
    $af = $m->affected_rows;
    echo "     -> {$af} fila" . ($af == 1 ? '' : 's') . "\n";
    return $af;
};

// ── 1. Fechas reales de la consignación ─────────────────────────────────────
echo "── 1. Fechas de consignación (PAGO 12 a 15)\n";
$fechas = array(
    65 => array('2026-07-03', '2026-07-11', 'PAGO 12'),
    66 => array('2026-07-10', '2026-07-11', 'PAGO 13'),
    72 => array('2026-07-17', '2026-07-29', 'PAGO 14'),
    71 => array('2026-07-24', '2026-07-29', 'PAGO 15'),
);
foreach ($fechas as $id => $f) {
    list($nueva, $vieja, $nom) = $f;
    $run($m, $APPLY, "mov {$id} {$nom}: {$vieja} -> {$nueva}",
        "UPDATE cash_movements SET movementDate = '{$nueva} 12:00:00', updated_at = NOW()
         WHERE idMovement = {$id} AND DATE(movementDate) = '{$vieja}'");
}

// ── 2. Duplicado de PAGO 11 ─────────────────────────────────────────────────
echo "\n── 2. Duplicado de PAGO 11\n";
$ya = rows($m, "SELECT idMovement FROM cash_movements WHERE documentNumber = 'AJUSTE-DUP-PAGO11'");
if ($ya) {
    echo "  ya existe el ajuste (mov {$ya[0]['idMovement']}), no se repite\n";
} else {
    $concepto = "Ajuste: el contrapago PAGO 11 (Interrapidísimo consignó el 19/06) ya estaba incluido en el ajuste de saldo del 30/06 y volvió a registrarse el 11/07 — se descuenta el duplicado. No afecta contabilidad.";
    $run($m, $APPLY, "ajuste de " . money(-575882) . " el 11/07 (solo tesorería)",
        "INSERT INTO cash_movements (sourceType, sourceId, movementType, amount, concept, category,
            documentNumber, movementDate, status, referenceType, referenceId, created_at, updated_at)
         VALUES ('banco', 1, 'ajuste', -575882.00, '" . $m->real_escape_string($concepto) . "',
            'ajuste', 'AJUSTE-DUP-PAGO11', '2026-07-11 12:18:13', 'activo', 'contrapago', 33, NOW(), NOW())");
}

// ── 3. Asientos de pagos con fecha de digitación ────────────────────────────
echo "\n── 3. Asientos de pagos fechados el día de la digitación\n";
$malos = rows($m, "SELECT e.entryID, e.entryDate, DATE(p.date) real_date, e.entryTransactionId
                   FROM entries e JOIN payments p ON p.idPayment = e.entryTransactionId
                   WHERE e.entryTransactionType = 'payment' AND e.deleted = 0 AND p.deleted = 0
                     AND p.date >= '2026-07-01' AND e.entryDate <> DATE(p.date)");
if (!$malos) {
    echo "  no hay asientos descuadrados de fecha\n";
} else {
    foreach ($malos as $x) echo "  asiento {$x['entryID']} (pago {$x['entryTransactionId']}): {$x['entryDate']} -> {$x['real_date']}\n";
    $run($m, $APPLY, count($malos) . " asiento(s) a corregir",
        "UPDATE entries e JOIN payments p ON p.idPayment = e.entryTransactionId
         SET e.entryDate = DATE(p.date)
         WHERE e.entryTransactionType = 'payment' AND e.deleted = 0 AND p.deleted = 0
           AND p.date >= '2026-07-01' AND e.entryDate <> DATE(p.date)");
}

// ── 4. "Dcto Inter:" -> "Dcto Interrapidísimo:" ─────────────────────────────
echo "\n── 4. Nombre de la transportadora en los textos\n";
$run($m, $APPLY, "conceptos de movimientos",
    "UPDATE cash_movements SET concept = REPLACE(concept, 'Dcto Inter:', 'Dcto Interrapidísimo:')
     WHERE concept LIKE '%Dcto Inter:%'");
$run($m, $APPLY, "descripciones de asientos",
    "UPDATE entries SET entryDescription = REPLACE(entryDescription, 'Dcto Inter:', 'Dcto Interrapidísimo:')
     WHERE entryDescription LIKE '%Dcto Inter:%'");

// ── 5. Saldo de la cuenta ───────────────────────────────────────────────────
echo "\n── 5. Saldo de la cuenta (misma fórmula del libro)\n";
$run($m, $APPLY, "recalcular currentBalance de la cuenta 1",
    "UPDATE bank_accounts b SET b.currentBalance = (
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
    ) WHERE b.idBankAccount = 1");

// ── Verificación ────────────────────────────────────────────────────────────
echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if ($APPLY) {
    foreach (rows($m, "SELECT idMovement, amount, movementDate, documentNumber FROM cash_movements
                       WHERE idMovement IN (62,65,66,71,72) OR documentNumber = 'AJUSTE-DUP-PAGO11'
                       ORDER BY movementDate, idMovement") as $r) {
        printf("  mov %-4s %-12s %18s  %s\n", $r['idMovement'], substr($r['movementDate'], 0, 10),
            money($r['amount']), $r['documentNumber']);
    }
    $d = rows($m, "SELECT COALESCE(SUM(entryDebitBalance) - SUM(entryCreditBalance),0) t FROM entries WHERE deleted = 0");
    echo "  partida doble global: " . money($d[0]['t']) . " (debe ser 0)\n";
    $b = rows($m, "SELECT currentBalance t FROM bank_accounts WHERE idBankAccount = 1");
    echo "  saldo Bancolombia: " . money($b[0]['t']) . "\n";
}
