<?php
/**
 * Corrige TODOS los lotes de contrapago ya registrados para que el movimiento
 * del banco cuadre con el total real del Excel de Interrapidísimo.
 *
 * Ejecutar en el server de ledxury:
 *   php corregir_contrapagos_registrados.php            (simulación)
 *   php corregir_contrapagos_registrados.php --apply
 *
 * EL PROBLEMA
 * registrarIngreso solo llevaba al banco la porción de guías que cruzan con
 * una factura de Ledxury. Pero Interrapidísimo consigna el depósito completo:
 * también la plata de las guías de MAM / MAM-Online y de las que no cruzaron.
 * Resultado: el banco quedó corto en cada lote. Y en PAGO 15, además, se
 * registró sin el descuento de fletes, así que quedó inflado.
 *
 * Depósito real de un lote = (bruto total − descuento de fletes) − 4x1000.
 *
 * QUÉ HACE, por lote registrado:
 *   1. Recalcula el depósito real desde contrapago_payments + los descuentos
 *      vinculados en contrapago_invoice_payments. No hay cifras hardcodeadas.
 *   2. Ajusta el monto y el concepto del movimiento del banco.
 *   3. Asienta la diferencia:
 *        · porción de terceros:  DR banco + DR 4x1000 / CR 2230 CxP vinculadas
 *        · descuento de fletes:  DR 5135 fletes / CR banco
 *        · 4x1000 mal calculado: se corrige contra 5305
 *   4. Ajusta el saldo del banco por la diferencia neta.
 *
 * NO toca cartera: los pagos a facturas de clientes están bien.
 * Idempotente: si ya corrió, aborta (marca por asientos 'contrapago_ajuste2').
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

$UID = '71339095';
$ACC_BANK = 39;   // 1110 Bancolombia
$ACC_FREIGHT = 55; // 5135 Fletes
$ACC_GMF = 58;     // 5305 Gastos bancarios (4x1000)
$ACC_ICPAY = 61;   // 2230 CxP a compañías vinculadas

function one($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); exit(1); } return $r->fetch_assoc(); }
function all($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); exit(1); } $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o; }
function run($m, $APPLY, $sql) {
    if (!$APPLY) { echo "     [sim] " . preg_replace('/\s+/', ' ', substr(trim($sql), 0, 120)) . "\n"; return; }
    if (!$m->query($sql)) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); $m->rollback(); exit(1); }
}
function mny($v) { return '$' . number_format((float)$v, 2, ',', '.'); }
function entry($m, $APPLY, $UID, $lote, $fecha, $dr, $cr, $monto, $tipo, $desc) {
    if (round($monto, 2) <= 0) return;
    echo "     asiento: DR $dr / CR $cr  " . mny($monto) . "  — $desc\n";
    run($m, $APPLY, sprintf(
        "INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
            entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
            entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
         VALUES ('%s', '%s', '%s', 1, 1, '%s', %d, %d, %.2f, %d, %.2f, 1, '%s', NOW(), 0)",
        $UID, $m->real_escape_string($desc), $fecha, $tipo, $lote, $dr, $monto, $cr, $monto, $UID));
}

// Guarda de idempotencia
$ya = one($m, "SELECT COUNT(*) n FROM entries WHERE entryTransactionType IN ('contrapago_ajuste2','contrapago_ajuste2_gmf')");
if ((int)$ya['n'] > 0) { echo "ABORTA: la corrección ya fue aplicada.\n"; exit(0); }

// ALCANCE: solo los lotes auditados contra el Excel de Interrapidísimo
// (julio–agosto 2026). Se probó correrlo sobre todo el histórico y da cifras
// falsas en los lotes viejos, porque:
//   · en varios el descuento de fletes se escribió a mano en el formulario y no
//     quedó vinculado en contrapago_invoice_payments, o al revés: quedó
//     vinculada una factura completa que no se descontó en ese depósito
//     (a PAGO 5 y PAGO 6 les daría depósito real $0);
//   · el cruce de "qué guías son de Ledxury" se detecta por el comentario del
//     pago ("Lote #N"), formato que los lotes de marzo/abril no usan, así que
//     el lote CONCILIACION saldría con $16M de terceros y subiría el banco por
//     ese valor.
// Esos lotes hay que revisarlos uno por uno contra su Excel; este script no
// los toca.
$SCOPE = array(36, 38, 39, 40, 41);
$in = implode(',', $SCOPE);
$lotes = all($m, "SELECT b.id, b.sheet_name, b.total_valor, b.fecha_pago, b.cash_movement_id,
                         cm.amount AS banco_actual, cm.concept, cm.movementDate
                  FROM contrapago_batches b
                  JOIN cash_movements cm ON cm.idMovement = b.cash_movement_id
                  WHERE b.status = 'registrado' AND cm.deleted = 0 AND cm.status <> 'anulado'
                    AND b.id IN ($in)
                  ORDER BY b.id");

$deltaBancoTotal = 0; $plan = array();

foreach ($lotes as $L) {
    $id = (int)$L['id'];

    // Bruto real del lote: todas las guías menos las duplicadas (esas ya se
    // cobraron en otro lote y no vienen en este depósito).
    $br = one($m, "SELECT COALESCE(SUM(valorTotal),0) t FROM contrapago_payments
                   WHERE batch_id = $id AND status <> 'duplicada'");
    $brutoTotal = (float)$br['t'];

    // Porción que cruza con factura de Ledxury (la que ya está en cartera)
    $ap = one($m, "SELECT COALESCE(SUM(p.valorTotal),0) t FROM contrapago_payments p
                   JOIN invoices i ON i.idInvoice = p.invoice_id
                   WHERE p.batch_id = $id AND p.status = 'conciliado' AND p.invoice_id IS NOT NULL
                     AND EXISTS (SELECT 1 FROM payments pay WHERE pay.invoiceId = p.invoice_id
                                 AND pay.paymentMethod = 5 AND pay.deleted = 0
                                 AND pay.comments LIKE CONCAT('%Lote #', $id, ' %'))");
    $brutoLedxury  = (float)$ap['t'];
    $brutoTerceros = round($brutoTotal - $brutoLedxury, 2);

    // Descuento de fletes que Interrapidísimo cruzó contra esta consignación
    $dc = one($m, "SELECT COALESCE(SUM(monto_cobrado),0) t FROM contrapago_invoice_payments WHERE batch_id = $id");
    $descuento = (float)$dc['t'];

    $base   = round($brutoTotal - $descuento, 2);
    $gmf    = round($base * 0.004, 2);
    $depReal = round($base - $gmf, 2);
    $bancoActual = (float)$L['banco_actual'];
    $delta = round($depReal - $bancoActual, 2);

    printf("── #%-3s %-9s bruto %14s  dcto %12s  4x1000 %10s  →  depósito real %14s | banco %14s | dif %s\n",
        $id, $L['sheet_name'], mny($brutoTotal), mny($descuento), mny($gmf), mny($depReal), mny($bancoActual), mny($delta));
    printf("        de los cuales terceros: %s\n", mny($brutoTerceros));

    if (abs($delta) < 0.01 && $descuento == 0) { echo "        ya está correcto, se omite\n"; continue; }

    $plan[] = array('id' => $id, 'sheet' => $L['sheet_name'], 'fecha' => substr($L['movementDate'], 0, 10),
        'brutoTotal' => $brutoTotal, 'brutoTerceros' => $brutoTerceros, 'descuento' => $descuento,
        'gmf' => $gmf, 'depReal' => $depReal, 'bancoActual' => $bancoActual, 'delta' => $delta,
        'movId' => (int)$L['cash_movement_id'], 'concept' => $L['concept']);
    $deltaBancoTotal += $delta;
}

echo "\nEfecto neto en el saldo del banco: " . mny($deltaBancoTotal) . "\n\n";
if (empty($plan)) { echo "Nada por corregir.\n"; exit(0); }

if ($APPLY) $m->begin_transaction();

foreach ($plan as $p) {
    echo "── corrigiendo #{$p['id']} {$p['sheet']}\n";
    $id = $p['id']; $fecha = $p['fecha'];

    // Reparto del 4x1000 entre Ledxury y terceros, a prorrata del bruto
    $propT = $p['brutoTotal'] > 0 ? ($p['brutoTerceros'] / $p['brutoTotal']) : 0;
    $gmfT  = round($p['gmf'] * $propT, 2);
    $netoT = round($p['brutoTerceros'] - $gmfT, 2);

    // 1) Porción de terceros que nunca entró: DR banco + DR 4x1000 / CR 2230
    if ($netoT > 0) {
        $comp = all($m, "SELECT COALESCE(NULLIF(company,''),'por identificar') comp, SUM(valorTotal) t
                         FROM contrapago_payments WHERE batch_id = $id AND status <> 'duplicada'
                           AND (company IS NULL OR company <> 'ledxury') GROUP BY comp");
        $det = array();
        foreach ($comp as $x) $det[] = strtoupper(str_replace('_', '-', $x['comp'])) . ' ' . mny($x['t']);
        $d = 'Contrapagos cobrados por cuenta de terceros - ' . $p['sheet'] . ' (' . implode(' + ', $det) . ')';
        entry($m, $APPLY, $UID, $id, $fecha, $ACC_BANK, $ACC_ICPAY, $netoT, 'contrapago_ajuste2', $d);
        entry($m, $APPLY, $UID, $id, $fecha, $ACC_GMF, $ACC_ICPAY, $gmfT, 'contrapago_ajuste2_gmf',
            '4x1000 sobre la porción de terceros - ' . $p['sheet']);
    }

    // 2) Lado Ledxury: el banco debe quedar en (bruto de Ledxury − descuento
    //    de fletes − su parte del 4x1000). La diferencia contra lo registrado
    //    se descompone en dos piezas, sin adivinar:
    //      · fletes que faltan por asentar = descuento − lo ya asentado como
    //        contrapago_freight en el registro original;
    //      · el resto es corrección del 4x1000, que se había calculado sobre
    //        una base parcial.
    $gmfL     = round($p['gmf'] - $gmfT, 2);
    $brutoLdx = round($p['brutoTotal'] - $p['brutoTerceros'], 2);
    $netoLdxOk = round($brutoLdx - $p['descuento'] - $gmfL, 2);
    $deltaLdx  = round($netoLdxOk - $p['bancoActual'], 2);

    $fy = one($m, "SELECT COALESCE(SUM(entryDebitBalance),0) t FROM entries
                   WHERE entryTransactionType = 'contrapago_freight' AND entryTransactionId = $id AND deleted = 0");
    $freteFaltante = round($p['descuento'] - (float)$fy['t'], 2);
    if ($freteFaltante > 0.01) {
        entry($m, $APPLY, $UID, $id, $fecha, $ACC_FREIGHT, $ACC_BANK, $freteFaltante, 'contrapago_ajuste2',
            'Fletes Interrapidísimo descontados de la consignación - ' . $p['sheet'] . ' (no se había registrado el descuento)');
    }
    $gmfPlug = round($deltaLdx + $freteFaltante, 2);
    if ($gmfPlug > 0.01) {
        entry($m, $APPLY, $UID, $id, $fecha, $ACC_BANK, $ACC_GMF, $gmfPlug, 'contrapago_ajuste2',
            'Reverso del 4x1000 sobrestimado (se calculó sobre el bruto sin descontar fletes) - ' . $p['sheet']);
    } elseif ($gmfPlug < -0.01) {
        entry($m, $APPLY, $UID, $id, $fecha, $ACC_GMF, $ACC_BANK, abs($gmfPlug), 'contrapago_ajuste2',
            'Ajuste del 4x1000: se había calculado solo sobre la porción de Ledxury - ' . $p['sheet']);
    }

    // 3) Movimiento del banco al depósito real
    $nuevoConcepto = preg_replace('/\s*\|\s*Incluye .*$/u', '', $p['concept']);
    $nuevoConcepto .= ' = $' . number_format($p['depReal'], 0, ',', '.');
    if ($p['brutoTerceros'] > 0) {
        $nuevoConcepto .= ' | Incluye $' . number_format($p['brutoTerceros'], 0, ',', '.') . ' cobrados por cuenta de terceros';
    }
    echo "     movimiento {$p['movId']}: " . mny($p['bancoActual']) . " → " . mny($p['depReal']) . "\n";
    run($m, $APPLY, sprintf("UPDATE cash_movements SET amount = %.2f, concept = '%s', updated_at = NOW()
        WHERE idMovement = %d", $p['depReal'], $m->real_escape_string($nuevoConcepto), $p['movId']));
}

// 4) Saldo del banco
echo "\n── saldo de la cuenta: " . mny($deltaBancoTotal) . "\n";
run($m, $APPLY, sprintf("UPDATE bank_accounts SET currentBalance = currentBalance + %.2f WHERE idBankAccount = 1", $deltaBancoTotal));

// 5) Saldos denormalizados de las 4 subcuentas tocadas
run($m, $APPLY, "UPDATE subaccounts s SET
    s.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAccount  = s.id AND e.deleted = 0),
    s.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAccount = s.id AND e.deleted = 0)
    WHERE s.id IN ($ACC_BANK, $ACC_FREIGHT, $ACC_GMF, $ACC_ICPAY)");
run($m, $APPLY, "UPDATE subaccounts SET
    accountBalance = CASE WHEN accountSide = '1' THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
    WHERE id IN ($ACC_BANK, $ACC_FREIGHT, $ACC_GMF, $ACC_ICPAY)");

if ($APPLY) {
    $m->commit();
    echo "\n=== APLICADO — VERIFICACION ===\n";
    foreach (all($m, "SELECT b.id, b.sheet_name, cm.amount FROM contrapago_batches b
                      JOIN cash_movements cm ON cm.idMovement = b.cash_movement_id
                      WHERE b.status='registrado' AND cm.deleted=0 ORDER BY b.id") as $r) {
        printf("  #%-3s %-9s banco %s\n", $r['id'], $r['sheet_name'], mny($r['amount']));
    }
    $v = one($m, "SELECT SUM(entryDebitBalance)-SUM(entryCreditBalance) d FROM entries WHERE deleted=0");
    echo "  partida doble global: " . mny($v['d']) . " (debe ser 0)\n";
    $v = one($m, "SELECT accountBalance b FROM subaccounts WHERE id = $ACC_ICPAY");
    echo "  2230 CxP a compañías vinculadas: " . mny($v['b']) . "\n";
    $v = one($m, "SELECT currentBalance b FROM bank_accounts WHERE idBankAccount = 1");
    echo "  saldo Bancolombia: " . mny($v['b']) . "\n";
} else {
    echo "\n=== FIN SIMULACION — nada se escribió ===\n";
}
