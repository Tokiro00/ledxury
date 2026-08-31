<?php
/**
 * RECUPERACIÓN — causa las comisiones de los lotes PAGO 18 (21/08) y
 * PAGO 19 (28/08), los únicos cobros posteriores al corte de comisiones
 * pagadas. Asiento igual al del sistema (bot_commission_accrual):
 *   DR 510528 (comisiones operadores bot) / CR 233525 + auxiliar por persona
 * Base por guía = cobrado − flete del corte. 7% al vendedor del bot,
 * 1% Christina y 3% Jorge sobre la base total.
 *
 * NO paga nada: solo deja el pendiente visible en Liquidaciones, por orden
 * de Alex, hasta identificar todas las facturas.
 *
 * INCREMENTAL: se puede re-correr después de reconstruir más facturas (las
 * del bot de Christina): calcula el objetivo por lote+persona y causa solo
 * la DIFERENCIA contra lo ya causado.
 *
 *   php causar_comisiones_pago18_19.php            (simulacion)
 *   php causar_comisiones_pago18_19.php --apply
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n" : "=== SIMULACION ===\n";

$UID = '71339095';
// cuentas (por codigo, no por id)
$gasto = $m->query("SELECT id FROM subaccounts WHERE pucCode = '510528' AND deleted = 0")->fetch_assoc();
$cxp   = $m->query("SELECT id FROM subaccounts WHERE pucCode = '233525' AND deleted = 0")->fetch_assoc();
if (!$gasto || !$cxp) { echo "ABORTA: faltan 510528/233525\n"; exit(1); }
$GASTO = (int)$gasto['id']; $CXP = (int)$cxp['id'];

// auxiliares de comision por usuario
$aux = array();
$r = $m->query("SELECT accountAccount u, id FROM auxiliary_subaccounts WHERE accountType = 'bot_commission' AND deleted = 0");
while ($x = $r->fetch_assoc()) $aux[$x['u']] = (int)$x['id'];

// datos por lote: base por vendedor de bot
$LOTES = array('PAGO 18' => '2026-08-21', 'PAGO 19' => '2026-08-28');
$FIJOS = array(
    // idUser => [nombre, pct] sobre la base total del lote (todos los canales)
    '5210750'  => array('Christina Morales', 1.0),
    '71211970' => array('Jorge Cano', 3.0),
);

$totCausar = 0;
if ($APPLY) $m->begin_transaction();
foreach ($LOTES as $lote => $fechaPago) {
    $r = $m->query("
        SELECT i.vendorId, u.name vendedor, u.commission_perc pct,
               SUM(cp.valorTotal) cobrado,
               SUM(LEAST(COALESCE((SELECT SUM(cii.valor_total) FROM contrapago_invoice_items cii
                    WHERE REGEXP_REPLACE(cii.numero_guia,'[^0-9]','') = REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','')),0), cp.valorTotal)) flete
        FROM contrapago_payments cp
        JOIN contrapago_batches b ON b.id = cp.batch_id
        JOIN invoices i ON i.idInvoice = cp.invoice_id
        JOIN users u ON u.idUser = i.vendorId
        WHERE b.sheet_name = '" . $m->real_escape_string($lote) . "'
        GROUP BY i.vendorId, u.name, u.commission_perc");
    $baseTotal = 0;
    $objetivos = array();  // idUser => [nombre, monto]
    while ($x = $r->fetch_assoc()) {
        $base = (float)$x['cobrado'] - (float)$x['flete'];
        if ($base <= 0) continue;
        $baseTotal += $base;
        $objetivos[$x['vendorId']] = array($x['vendedor'], round($base * ((float)$x['pct'] / 100), 2));
    }
    foreach ($FIJOS as $idU => $f) {
        $monto = round($baseTotal * ($f[1] / 100), 2);
        if ($monto > 0) $objetivos[$idU] = array($f[0], (isset($objetivos[$idU]) ? $objetivos[$idU][1] : 0) + $monto);
    }
    printf("\n%s (pagado %s) — base total de canales: %s\n", $lote, $fechaPago, number_format($baseTotal, 0, ',', '.'));
    foreach ($objetivos as $idU => $obj) {
        list($nombre, $target) = $obj;
        if (!isset($aux[$idU])) { echo "  OJO: $nombre ($idU) sin auxiliar de comision — saltado\n"; continue; }
        $auxId = $aux[$idU];
        $desc = "Comisión bots $lote (recuperación) - $nombre";
        // ya causado con esa descripcion
        $ya = $m->query("SELECT COALESCE(SUM(CAST(entryCreditBalance AS DECIMAL(18,2))),0) t FROM entries
                         WHERE deleted = 0 AND entryTransactionType = 'bot_commission_accrual'
                           AND entryCreditAuxaccount = $auxId
                           AND entryDescription = '" . $m->real_escape_string($desc) . "'")->fetch_assoc();
        $delta = round($target - (float)$ya['t'], 2);
        if (abs($delta) < 1) { printf("  %-28s objetivo %10s — ya causado, sin cambio\n", $nombre, number_format($target, 0, ',', '.')); continue; }
        if ($delta < 0) { printf("  %-28s OJO: causado (%s) > objetivo (%s) — revisar a mano\n", $nombre,
            number_format($ya['t'], 0, ',', '.'), number_format($target, 0, ',', '.')); continue; }
        printf("  %-28s causar %12s  (DR 510528 / CR 233525 aux %d, fecha %s)\n",
            $nombre, number_format($delta, 0, ',', '.'), $auxId, $fechaPago);
        if ($APPLY) {
            $v = number_format($delta, 2, '.', '');
            $d = $m->real_escape_string($desc);
            $ok = $m->query("INSERT INTO entries (userID, entryDescription, entryType,
                    entryDebitAccount, entryDebitBalance,
                    entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
                    entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
                    entryTransactionType, entryTransactionId, entryDate)
                VALUES ('$UID', '$d', 1, $GASTO, '$v', $CXP, $auxId, '$v',
                    1, '$UID', NOW(), 0, 1, 'bot_commission_accrual', 0, '$fechaPago')");
            if (!$ok) { echo "  ERROR: {$m->error}\n"; $m->rollback(); exit(1); }
        }
        $totCausar += $delta;
    }
}
if ($APPLY) {
    // resincronizar denormalizados (Liquidaciones lee el aux)
    $auxIds = implode(',', array_values($aux));
    $m->query("UPDATE auxiliary_subaccounts a SET
        a.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAuxaccount  = a.id AND e.deleted = 0),
        a.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAuxaccount = a.id AND e.deleted = 0)
        WHERE a.id IN ($auxIds)");
    $m->query("UPDATE auxiliary_subaccounts SET accountBalance = accountCredit - accountDebit WHERE id IN ($auxIds)");
    $m->query("UPDATE subaccounts s SET
        s.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAccount  = s.id AND e.deleted = 0),
        s.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAccount = s.id AND e.deleted = 0)
        WHERE s.id IN ($GASTO, $CXP)");
    $m->query("UPDATE subaccounts SET accountBalance = CASE WHEN accountSide = '1' THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
        WHERE id IN ($GASTO, $CXP)");
    $m->commit();
    $x = $m->query("SELECT SUM(entryDebitBalance) - SUM(entryCreditBalance) d FROM entries WHERE deleted = 0")->fetch_assoc();
    echo "\npartida doble global: " . number_format($x['d'], 2, ',', '.') . " (debe ser 0)\n";
}
printf("\ntotal causado en esta corrida: %s\n", number_format($totCausar, 0, ',', '.'));
echo "Re-correr este script tras reconstruir mas facturas: causa solo la diferencia.\n";
