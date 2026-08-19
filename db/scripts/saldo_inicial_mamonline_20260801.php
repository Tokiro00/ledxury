<?php
/**
 * SALDO INICIAL MAM-ONLINE — 01/08/2026 — $129.308.187
 *
 * Ejecutar en el server de ledxury:  php saldo_inicial_mamonline_20260801.php [--apply]
 * Sin --apply es SIMULACIÓN: muestra todo y no escribe nada.
 *
 * Contexto: Alex acordó con MAM el punto de partida de la deuda al arrancar
 * agosto: $129.308.187 (factura de MAM). La cifra cuadra (99,5%) con:
 * libro del canal en accesoriosmam ($53.493.003 desde 01/06) + costo histórico
 * de mercancía vendida 2024-2026 sin compra registrada ($76.510.482).
 *
 * Qué hace:
 *  1. ANULA los cierres de consignación viejos y su NC (facturas de proveedor
 *     MAM sin pagar: CIERRE-MAM-* y NC-MAM-*) con sus asientos — quedaban
 *     DENTRO del saldo inicial y dejarlos duplicaría $13.676.137.
 *  2. Crea la factura de proveedor SALDO-INICIAL-MAMONLINE-20260801 por
 *     $129.308.187 (recibida: no hay mercancía por recibir, ya está acá).
 *  3. Asientos al 01/08: CR proveedores (2205 + aux MAM) por el total, contra
 *     DR inventario (1435) hasta dejarlo en el valor físico a costo, y el
 *     resto DR utilidades acumuladas (3705) — es el costo de lo vendido en
 *     2024-2026 que nunca tuvo compra registrada. Reemplaza el "saneo del
 *     143501" que quedó diseñado en julio.
 *  4. Recalcula los saldos denormalizados (subcuentas 41/42/45 y auxiliar MAM).
 *
 * NO toca: cartera, banco, intercompany (contrapagos/fletes son cuentas aparte),
 * ni la migración 066 (contrapagos de julio), que sigue pendiente.
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

$TOTAL = 129308187.00;
$FECHA = '2026-08-01';
$UID   = '71339095';
$NUM   = 'SALDO-INICIAL-MAMONLINE-20260801';
$ACC_INV = 41;   // 1435 Inventario mercancías (mismo id para tránsito)
$ACC_PAY = 42;   // 2205 Proveedores nacionales
$ACC_RET = 45;   // 3705 Utilidades acumuladas
$AUX_MAM = 5790; // auxiliar MAM en proveedores

function one($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); exit(1); } return $r->fetch_assoc(); }
function run($m, $APPLY, $sql) {
    if (!$APPLY) { echo "  [sim] " . preg_replace('/\s+/', ' ', substr(trim($sql), 0, 170)) . "\n"; return; }
    if (!$m->query($sql)) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); $m->rollback(); exit(1); }
    echo "  [ok] filas: {$m->affected_rows}\n";
}
function money($v) { return '$' . number_format((float)$v, 2, ',', '.'); }

// ── Guardas ──────────────────────────────────────────────────────────────────
$ya = one($m, "SELECT COUNT(*) n FROM supplier_invoices WHERE invoiceNumber = '$NUM'");
if ((int)$ya['n'] > 0) { echo "ABORTA: la factura $NUM ya existe.\n"; exit(0); }

$bills = array();
$r = $m->query("SELECT idSupplierInvoice, invoiceNumber, total, paidAmount FROM supplier_invoices
                WHERE providerId = '12' AND deleted = 0
                  AND (invoiceNumber LIKE 'CIERRE-MAM-%' OR invoiceNumber LIKE 'NC-MAM-%')");
$sumViejas = 0;
while ($x = $r->fetch_assoc()) {
    if ((float)$x['paidAmount'] != 0) { echo "ABORTA: la factura {$x['invoiceNumber']} tiene pagos — revisar a mano.\n"; exit(1); }
    $bills[] = $x; $sumViejas += (float)$x['total'];
}
$billIds = implode(',', array_map(function($b){ return (int)$b['idSupplierInvoice']; }, $bills));
echo "Facturas viejas de MAM a anular (" . count($bills) . "): " . money($sumViejas) . "  [ids: $billIds]\n";
foreach ($bills as $b) echo "   - {$b['invoiceNumber']}  " . money($b['total']) . "\n";

// Inventario físico a costo, hoy
$inv = one($m, "SELECT COALESCE(SUM(i.stock * COALESCE(NULLIF(p.cost_cop,0), NULLIF(p.cost,0), 0)),0) v
                FROM inventory i JOIN products p ON p.idProduct = i.idProduct
                WHERE i.stock > 0 AND COALESCE(p.deleted,0) = 0");
$TARGET_INV = round((float)$inv['v'], 2);
echo "Inventario físico a costo (hoy): " . money($TARGET_INV) . "\n\n";

if ($APPLY) $m->begin_transaction();

// ── 1. Anular cierres viejos y sus asientos ─────────────────────────────────
echo "1) ANULAR FACTURAS VIEJAS Y SUS ASIENTOS\n";
run($m, $APPLY, "UPDATE supplier_invoices SET deleted = 1, deleted_at = NOW(),
    notes = CONCAT(COALESCE(notes,''), '\n[ANULADA 19/08/2026: absorbida por el saldo inicial $NUM]')
    WHERE idSupplierInvoice IN ($billIds)");
run($m, $APPLY, "UPDATE entries SET deleted = 1
    WHERE deleted = 0 AND entryTransactionType IN ('supplier_bill','supplier_return')
      AND entryTransactionId IN ($billIds)");

// ── 2. Factura del saldo inicial ────────────────────────────────────────────
echo "\n2) FACTURA $NUM por " . money($TOTAL) . "\n";
$notas = 'Saldo inicial acordado con MAM al 01/08/2026 (factura de MAM). Cubre: costo historico de mercancia vendida 2024-2026 sin compra registrada, el inventario en poder de Ledxury, y las remisiones del canal hasta el 31/07. Reemplaza los cierres de consignacion CIERRE-MAM-* y la NC-MAM-* (anulados). Las remisiones desde el 01/08 entran aparte via puente mamsync (REM-MAM-*).';
run($m, $APPLY, "INSERT INTO supplier_invoices
    (providerId, invoiceNumber, invoiceDate, dueDate, total, subtotal, tax, paidAmount, balance,
     status, storeId, received, received_at, received_by, notes, created_at)
    VALUES ('12', '$NUM', '$FECHA', '2026-08-31', $TOTAL, $TOTAL, 0, 0, $TOTAL,
     'pendiente', 1, 1, NOW(), '$UID', '" . $m->real_escape_string($notas) . "', NOW())");
$billNewId = $APPLY ? $m->insert_id : 0;

// ── 3. Asientos ─────────────────────────────────────────────────────────────
// Saldo de 1435 después de anular (desde asientos vivos)
$b1435 = one($m, "SELECT
    COALESCE(SUM(CASE WHEN entryDebitAccount = $ACC_INV THEN entryDebitBalance ELSE 0 END),0) -
    COALESCE(SUM(CASE WHEN entryCreditAccount = $ACC_INV THEN entryCreditBalance ELSE 0 END),0) AS bal
    FROM entries WHERE deleted = 0" . ($APPLY ? "" : " AND NOT (entryTransactionType IN ('supplier_bill','supplier_return') AND entryTransactionId IN ($billIds))"));
$bal1435 = round((float)$b1435['bal'], 2);
$X = round($TARGET_INV - $bal1435, 2);            // DR a inventario para dejarlo en el físico

echo "\n3) ASIENTOS AL $FECHA (CR 2205+auxMAM por el total)\n";
echo "   saldo 1435 tras anulaciones: " . money($bal1435) . "\n";
echo "   ajuste total que necesita 1435 para quedar en el físico: " . money($X) . "\n";
if ($X < 0) { echo "OJO: 1435 quedaría por ENCIMA del físico; el ajuste sale negativo. Revisar a mano.\n"; if ($APPLY) { $m->rollback(); } exit(1); }

$d1 = 'Saldo inicial MAM-Online 01/08/2026 - deuda total acordada con MAM: inventario en poder de Ledxury + costo historico de mercancia vendida 2024-2026 sin compra registrada (factura ' . $NUM . ')';
// E1: el saldo inicial completo entra por inventario (repara el hueco histórico
// del 1435 y deja en libros la mercancía en mano).
run($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditAuxaccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
    VALUES ('$UID', '" . $m->real_escape_string($d1) . "', '$FECHA', 1, 1,
    'supplier_bill', " . ($APPLY ? $billNewId : 0) . ", $ACC_INV, $TOTAL, $ACC_PAY, $AUX_MAM, $TOTAL, 1, '$UID', NOW(), 0)");

// E2: residuo para que 1435 quede EXACTO en el inventario físico. Si el hueco
// histórico es mayor que el saldo acordado, el residuo va con cargo a
// utilidades acumuladas (DR 1435 / CR 3705); si es menor, al revés.
$RESTO = round($X - $TOTAL, 2);
if (abs($RESTO) >= 1) {
    if ($RESTO > 0) {
        echo "   E2: DR 1435 / CR 3705 por " . money($RESTO) . " (el hueco histórico supera el saldo acordado; el exceso queda a favor del patrimonio)\n";
        $d2 = 'Saldo inicial MAM-Online 01/08/2026 - ajuste residual: el costo historico no reconocido supera el saldo acordado con MAM (conciliacion)';
        run($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
            entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
            entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
            VALUES ('$UID', '" . $m->real_escape_string($d2) . "', '$FECHA', 1, 1,
            'supplier_bill', " . ($APPLY ? $billNewId : 0) . ", $ACC_INV, $RESTO, $ACC_RET, $RESTO, 1, '$UID', NOW(), 0)");
    } else {
        $R = abs($RESTO);
        echo "   E2: DR 3705 / CR 1435 por " . money($R) . " (el saldo acordado supera el ajuste que necesita el inventario)\n";
        $d2 = 'Saldo inicial MAM-Online 01/08/2026 - ajuste residual contra utilidades acumuladas (conciliacion)';
        run($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
            entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
            entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
            VALUES ('$UID', '" . $m->real_escape_string($d2) . "', '$FECHA', 1, 1,
            'supplier_bill', " . ($APPLY ? $billNewId : 0) . ", $ACC_RET, $R, $ACC_INV, $R, 1, '$UID', NOW(), 0)");
    }
} else {
    echo "   E2: no se necesita (residuo < \$1)\n";
}

// ── 4. Recalcular saldos denormalizados ─────────────────────────────────────
echo "\n4) RECALCULO DE SALDOS (subcuentas 41, 42, 45 y auxiliar MAM $AUX_MAM)\n";
run($m, $APPLY, "UPDATE subaccounts s SET
    s.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAccount  = s.id AND e.deleted = 0),
    s.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAccount = s.id AND e.deleted = 0)
    WHERE s.id IN ($ACC_INV, $ACC_PAY, $ACC_RET)");
run($m, $APPLY, "UPDATE subaccounts SET
    accountBalance = CASE WHEN accountSide = '1' THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
    WHERE id IN ($ACC_INV, $ACC_PAY, $ACC_RET)");
run($m, $APPLY, "UPDATE auxiliary_subaccounts a SET a.accountBalance =
    (SELECT COALESCE(SUM(CASE WHEN e.entryCreditAuxaccount = a.id THEN e.entryCreditBalance ELSE 0 END),0) -
     COALESCE(SUM(CASE WHEN e.entryDebitAuxaccount  = a.id THEN e.entryDebitBalance  ELSE 0 END),0)
     FROM entries e WHERE e.deleted = 0)
    WHERE a.id = $AUX_MAM");

if ($APPLY) {
    $m->commit();
    echo "\n=== APLICADO — VERIFICACION ===\n";
    $v = one($m, "SELECT accountBalance FROM auxiliary_subaccounts WHERE id = $AUX_MAM");
    echo "Auxiliar MAM (debe ser " . money($TOTAL) . "): " . money($v['accountBalance']) . "\n";
    $v = one($m, "SELECT accountBalance FROM subaccounts WHERE id = $ACC_INV");
    echo "1435 inventario (debe ser " . money($TARGET_INV) . "): " . money($v['accountBalance']) . "\n";
    $v = one($m, "SELECT SUM(entryDebitBalance) - SUM(entryCreditBalance) d FROM entries WHERE deleted = 0");
    echo "Partida doble global (debe ser 0): " . money($v['d']) . "\n";
    $v = one($m, "SELECT SUM(balance) s FROM supplier_invoices WHERE providerId='12' AND deleted=0");
    echo "CxP MAM en facturas vivas (debe ser " . money($TOTAL) . "): " . money($v['s']) . "\n";
} else {
    echo "\n=== FIN SIMULACION — nada se escribió ===\n";
    echo "NOTA: en modo aplicar, el cálculo del ajuste E1/E2 se rehace con los\n";
    echo "asientos ya anulados; los valores de arriba son la foto equivalente.\n";
}
