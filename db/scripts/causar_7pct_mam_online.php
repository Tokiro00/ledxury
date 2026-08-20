<?php
/**
 * Termina de separar el canal MAM-Online: mueve sus facturas ya cobradas al
 * vendedor del canal y causa el 7% que nunca se registró.
 *
 *   php causar_7pct_mam_online.php            (simulación)
 *   php causar_7pct_mam_online.php --apply
 *
 * EL BUG QUE DESTAPÓ ESTO
 * Christina tenía dos comisiones activas: 1% de todos los canales (ads_manager)
 * y 7% del canal MAM-Online (operator). En el auxiliar solo hay asientos del
 * 1%: los 1.026 asientos, $921.038 completos. El 7% NUNCA se causó.
 *
 * La causa está en Accounting_lib::recordBotCommissionAccrual(): su chequeo de
 * idempotencia se llavea por (factura, usuario), sin mirar de qué configuración
 * viene. recordBotCommissionsForInvoice() recorre las configs en orden de id, y
 * la #5 (1%) va antes que la #6 (7%): al llegar la segunda, el chequeo encuentra
 * el asiento del 1% y devuelve ese mismo id sin crear nada. Peor: como devuelve
 * un id truthy, la función lo cuenta como creado y nadie se enteró.
 *
 * Al darle al canal su propio vendedor ese choque desaparece (son usuarios
 * distintos, auxiliares distintos), así que de aquí en adelante las dos
 * comisiones se causan bien. Este script arregla el histórico.
 *
 * QUÉ HACE
 *  1. Mueve al vendedor MAM-Online las 6 facturas del canal ya cobradas
 *     (estado 2, 2026). Las 13 pendientes ya se movieron antes.
 *  2. Crea el auxiliar de comisión de bot del vendedor MAM-Online.
 *  3. Causa el 7% de cada una de esas 6 facturas a ese auxiliar, con la misma
 *     forma que recordBotCommissionAccrual (DR 510528 / CR 233525 + aux) y con
 *     la fecha de cobro de la factura.
 *
 * NO toca los asientos del 1% de Christina: su configuración es 'all', así que
 * ese 1% le corresponde igual sin importar de qué canal venga la venta. Su
 * generada, su pagada y su pendiente quedan como están.
 *
 * Idempotente: si el asiento del 7% de una factura ya existe, la salta.
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

$VENDOR    = '5000005';   // usuario MAM-Online
$NOMBRE    = 'MAM-Online';
$CHRISTINA = '5210750';
$BOT_ID    = 5;
$PCT       = 7.00;
$ACC_GASTO   = 64;   // 510528 Comisiones operadores bot
$ACC_PASIVO  = 65;   // 233525 Comisiones bots por pagar
$ACTOR     = 'system';

$errores = 0;
function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function money($v) { return '$' . number_format((float)$v, 2, ',', '.'); }
function exec_sql($m, $APPLY, $sql, &$errores) {
    if (!$APPLY) { echo "       [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 100)) . "\n"; return true; }
    if ($m->query($sql) === false) { echo "       ERROR: {$m->error}\n"; $errores++; return false; }
    return true;
}

// Comprobar que las subcuentas son las esperadas antes de asentar nada.
foreach (array($ACC_GASTO => '510528', $ACC_PASIVO => '233525') as $id => $puc) {
    $s = one($m, "SELECT pucCode, accountName FROM subaccounts WHERE id = {$id} AND deleted = 0");
    if (!$s || $s['pucCode'] !== $puc) {
        echo "ABORTA: la subcuenta {$id} no es {$puc} (es '" . ($s ? $s['pucCode'] : 'inexistente') . "')\n";
        exit(1);
    }
    echo "  cuenta {$id} = {$s['pucCode']} {$s['accountName']}\n";
}
echo "\n";

if ($APPLY) $m->begin_transaction();

// ── 1. Auxiliar de comisión del vendedor del canal ─────────────────────────
echo "── 1. Auxiliar de comisión de bot para {$NOMBRE}\n";
$aux = one($m, "SELECT id FROM auxiliary_subaccounts
                WHERE accountAccount = '{$VENDOR}' AND accountType = 'bot_commission' AND deleted = 0");
if ($aux) {
    $auxId = (int)$aux['id'];
    echo "     ya existe (aux {$auxId})\n";
} else {
    exec_sql($m, $APPLY, "INSERT INTO auxiliary_subaccounts
        (accountID, accountName, accountAccount, accountSide, accountStatement, accountType,
         accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, deleted, created_at)
        VALUES (233525, '" . $m->real_escape_string($NOMBRE) . "', '{$VENDOR}', '2', '1', 'bot_commission',
         0, 0, 0, 0, 1, 0, NOW())", $errores);
    $auxId = $APPLY ? (int)$m->insert_id : 0;
    echo "     creado: aux {$auxId}\n";
}

// ── 2. Facturas cobradas del canal que siguen con Christina ────────────────
echo "\n── 2. Facturas cobradas del canal (estado 2, 2026)\n";
$facturas = rows($m, "
    SELECT i.idInvoice, i.total, i.vendorId, DATE(i.updated_at) cobrada,
           LEAST(COALESCE(sg.flete, 0), i.total) flete,
           (i.total - LEAST(COALESCE(sg.flete, 0), i.total)) base,
           cl.name cliente
    FROM invoices i
    LEFT JOIN (SELECT invoiceId, SUM(valorTotal) flete FROM shipping_guides GROUP BY invoiceId) sg
           ON sg.invoiceId = i.idInvoice
    LEFT JOIN clients cl ON cl.idClient = i.clientId
    WHERE i.vendorId IN ('{$CHRISTINA}', '{$VENDOR}')
      AND i.state = 2 AND i.total > 0
      AND (i.deleted IS NULL OR i.deleted = 0)
      AND i.updated_at >= '2026-01-01'
    ORDER BY i.updated_at");

if (!$facturas) { echo "     no hay facturas por procesar\n"; }

$totalBase = 0; $totalCom = 0; $mover = array();
foreach ($facturas as $f) {
    $com = round((float)$f['base'] * $PCT / 100);
    $totalBase += (float)$f['base'];
    $totalCom  += $com;
    if ($f['vendorId'] !== $VENDOR) $mover[] = (int)$f['idInvoice'];
    printf("     #%-6s %s  total %13s  flete %11s  base %13s  7%% = %11s  %s\n",
        $f['idInvoice'], $f['cobrada'], money($f['total']), money($f['flete']),
        money($f['base']), money($com), substr((string)$f['cliente'], 0, 24));
}
printf("     %-52s base %13s  7%% = %11s\n", 'TOTAL', money($totalBase), money($totalCom));

if ($mover) {
    echo "\n     moviendo " . count($mover) . " factura(s) al vendedor {$VENDOR}\n";
    exec_sql($m, $APPLY, "UPDATE invoices SET vendorId = '{$VENDOR}', updated_at = updated_at
                          WHERE idInvoice IN (" . implode(',', $mover) . ")", $errores);
} else {
    echo "\n     todas ya están con el vendedor del canal\n";
}

// ── 3. Causar el 7% de cada factura ────────────────────────────────────────
echo "\n── 3. Causando el 7% al auxiliar de {$NOMBRE}\n";
$creados = 0; $saltados = 0;
foreach ($facturas as $f) {
    $com = round((float)$f['base'] * $PCT / 100);
    if ($com <= 0) { $saltados++; continue; }
    $desc = sprintf('Comisión bot %s%% factura #%d (%s)',
        number_format($PCT, 2), (int)$f['idInvoice'], $f['cliente'] ?: 'cliente');

    if ($auxId) {
        $ya = one($m, "SELECT entryID FROM entries
                       WHERE deleted = 0 AND entryTransactionType = 'bot_commission_accrual'
                         AND entryTransactionId = " . (int)$f['idInvoice'] . "
                         AND entryCreditAuxaccount = {$auxId} LIMIT 1");
        if ($ya) { echo "     factura #{$f['idInvoice']}: ya causada (asiento {$ya['entryID']})\n"; $saltados++; continue; }
    }

    printf("     factura #%-6s %s  DR %s / CR %s aux %s  %s\n",
        $f['idInvoice'], $f['cobrada'], $ACC_GASTO, $ACC_PASIVO, $auxId ?: '?', money($com));
    $v = number_format($com, 2, '.', '');
    $auxSql = $auxId ?: 'NULL';
    exec_sql($m, $APPLY, "INSERT INTO entries
        (userID, entryDescription, entryType, entryDebitAccount, entryDebitBalance,
         entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
         entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
         entryTransactionType, entryTransactionId, entryDate)
        VALUES ('{$ACTOR}', '" . $m->real_escape_string($desc) . "', 1,
         {$ACC_GASTO}, '{$v}', {$ACC_PASIVO}, {$auxSql}, '{$v}',
         1, '{$ACTOR}', NOW(), 0, 1,
         'bot_commission_accrual', " . (int)$f['idInvoice'] . ", '{$f['cobrada']}')", $errores);
    $creados++;
}
echo "     creados {$creados}, saltados {$saltados}\n";

// ── 4. Recalcular saldos ───────────────────────────────────────────────────
echo "\n── 4. Recalculando saldos de subcuentas y auxiliares\n";
foreach (array(
    "UPDATE subaccounts s SET
       s.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAccount  = s.id),
       s.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAccount = s.id)
     WHERE s.deleted = 0",
    "UPDATE subaccounts SET accountBalance = CASE WHEN accountSide = '1'
       THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END WHERE deleted = 0",
    "UPDATE auxiliary_subaccounts a SET
       a.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAuxaccount  = a.id),
       a.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAuxaccount = a.id)
     WHERE a.deleted = 0",
    "UPDATE auxiliary_subaccounts SET accountBalance = CASE WHEN accountSide = '1'
       THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END WHERE deleted = 0",
) as $sql) exec_sql($m, $APPLY, $sql, $errores);

if ($APPLY) {
    if ($errores) { echo "\n{$errores} error(es): ROLLBACK, no se cambió nada.\n"; $m->rollback(); exit(1); }
    $m->commit();
}

echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if (!$APPLY) exit(0);

echo "\n  comisión de bot por persona (auxiliar 233525):\n";
foreach (rows($m, "SELECT a.accountAccount uid, u.name, a.accountCredit gen, a.accountDebit pag, a.accountBalance saldo
                   FROM auxiliary_subaccounts a LEFT JOIN users u ON u.idUser = a.accountAccount
                   WHERE a.accountType = 'bot_commission' AND a.deleted = 0
                   ORDER BY a.accountBalance DESC") as $r)
    printf("    %-12s %-30s generada %14s pagada %14s pendiente %14s\n", $r['uid'],
        substr((string)$r['name'], 0, 30), money($r['gen']), money($r['pag']), money($r['saldo']));

echo "\n  facturas 2026 del canal por vendedor y estado:\n";
foreach (rows($m, "SELECT i.vendorId, u.name, i.state, COUNT(*) n, COALESCE(SUM(i.total),0) t
                   FROM invoices i LEFT JOIN users u ON u.idUser = i.vendorId
                   WHERE i.vendorId IN ('{$CHRISTINA}', '{$VENDOR}')
                     AND (i.deleted IS NULL OR i.deleted = 0) AND i.created_at >= '2026-01-01'
                   GROUP BY i.vendorId, i.state ORDER BY u.name, i.state") as $r)
    printf("    %-12s %-24s estado %-3s %4s factura(s) %16s\n", $r['vendorId'],
        substr((string)$r['name'], 0, 24), $r['state'], $r['n'], money($r['t']));

$d = one($m, "SELECT COALESCE(SUM(CAST(entryDebitBalance AS DECIMAL(18,2)))
                            - SUM(CAST(entryCreditBalance AS DECIMAL(18,2))),0) t
              FROM entries WHERE deleted = 0");
echo "\n  partida doble global: " . money($d['t']) . " (debe ser 0)\n";
$g = one($m, "SELECT accountBalance b FROM subaccounts WHERE id = {$ACC_GASTO}");
echo "  510528 Comisiones operadores bot: " . money($g['b']) . "\n";
