<?php
/**
 * Arregla la medición de gastos en el estado de resultados.
 *
 *   php arreglar_gastos_y_fletes_terceros.php                        (simulación)
 *   php arreglar_gastos_y_fletes_terceros.php --apply
 *   php arreglar_gastos_y_fletes_terceros.php --apply --incluir-historico
 *
 * QUÉ ARREGLA
 *
 *  1. Crea la cuenta para el bot: 513520 "Software y plataformas (bots, SaaS)"
 *     dentro del grupo 5135 Servicios, más su categoría de gasto GAS-SOF.
 *     En el PUC colombiano 513520 es "Procesamiento electrónico de datos", que
 *     es el casillero estándar para software y servicios de datos: es donde va
 *     una suscripción como BuilderBot. Hoy no existía y el único destino
 *     posible era 513505 "Servicios (logística, hosting, etc)", un cajón de
 *     sastre que no permite medir cuánto cuesta el bot.
 *
 *  2. Saca de los fletes de Ledxury el flete de las guías de MAM y MAM-Online.
 *     Interrapidísimo descuenta de nuestra consignación la factura de corte
 *     completa, y adentro vienen guías de las otras compañías. Ese flete no es
 *     gasto de Ledxury: es plata que nos deben. Hoy está todo en 513540 y el
 *     estado de resultados de Ledxury queda inflado.
 *     El reparto sale de la clasificación por compañía que se hace al revisar
 *     la factura (contrapago_invoice_items.company), a prorrata de lo que se
 *     cruzó contra cada lote. No hay cifras hardcodeadas.
 *     Asiento: DR 132505 CxC vinculados / CR 513540 Fletes.
 *
 *  3. Registra el movimiento intercompañía que faltaba: el flete de MAM-Online
 *     de la factura 210579 ($260.675). El de MAM sí estaba, el de MAM-Online no.
 *
 *  4. Reclasifica "parqueadero y gasolina" ($41.133,71) de 513540 Fletes a
 *     519505 Gastos diversos. Se registró como flete porque no había otra
 *     categoría, y ensucia la medición del flete real.
 *
 * CON --incluir-historico ADEMÁS
 *
 *  5. Trae al balance la cuenta por cobrar a MAM de los fletes descontados
 *     antes de julio (lotes 27, 29 y 30) y el pago que MAM ya hizo. Esos
 *     movimientos quedaron dentro del asiento de apertura, así que la
 *     contrapartida es utilidades acumuladas. Deja 132505 en el saldo real.
 *     Va aparte porque toca el patrimonio y esa es decisión de Alex.
 *
 * Idempotente: cada paso comprueba si ya está hecho.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
$HIST  = in_array('--incluir-historico', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===" : "=== SIMULACION (sin --apply no escribe nada) ===";
echo $HIST ? "  [incluyendo histórico]\n\n" : "\n\n";

$USER       = '71339095';
$STORE      = 1;
$ACC_FREIGHT = 55;   // 513540 Fletes
$ACC_DIVERSOS = 49;  // 519505 Gastos diversos
$ACC_IC_RECV = 60;   // 132505 CxC vinculados económicos
$ACC_RETAINED = 45;  // 370501 Utilidades acumuladas
$GRP_SERVICIOS = 50; // accounts_accounts.id del grupo 5135 Servicios
$CAT_OTROS   = 8;    // expense_categories GAS-OTR

function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function money($v) { return '$' . number_format((float)$v, 2, ',', '.'); }

$errores = 0;
function exec_sql($m, $APPLY, $sql, &$errores) {
    if (!$APPLY) { echo "       [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 105)) . "\n"; return true; }
    if ($m->query($sql) === false) { echo "       ERROR: {$m->error}\n"; $errores++; return false; }
    echo "       -> {$m->affected_rows} fila" . ($m->affected_rows == 1 ? '' : 's') . "\n";
    return true;
}
function asiento($m, $APPLY, &$errores, $desc, $date, $dr, $cr, $val, $type, $tid, $user, $store) {
    echo "     asiento: DR {$dr} / CR {$cr}  " . money($val) . "  [{$date}]\n       {$desc}\n";
    $d = $m->real_escape_string($desc);
    $v = number_format((float)$val, 2, '.', '');
    return exec_sql($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryType,
            entryDebitAccount, entryDebitBalance, entryCreditAccount, entryCreditBalance,
            entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
            entryTransactionType, entryTransactionId, entryDate)
        VALUES ('{$user}', '{$d}', 1, {$dr}, '{$v}', {$cr}, '{$v}',
            1, '{$user}', NOW(), 0, {$store}, '{$type}', " . (int)$tid . ", '{$date}')", $errores);
}

if ($APPLY) $m->begin_transaction();

// ═══ 1. Cuenta y categoría para el bot ═════════════════════════════════════
echo "── 1. Cuenta para BuilderBot: 513520 Software y plataformas\n";
$ya = one($m, "SELECT id FROM subaccounts WHERE pucCode = '513520' AND deleted = 0");
if ($ya) {
    echo "     ya existe (subcuenta {$ya['id']}), no se repite\n";
    $subBot = (int)$ya['id'];
} else {
    $ord = one($m, "SELECT COALESCE(MAX(accountOrder),0)+1 n FROM subaccounts WHERE accountID = 5135 AND deleted = 0");
    exec_sql($m, $APPLY, "INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide,
            accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement,
            accountType, created_by, accountCreationDate, created_at, deleted, store, pucCode)
        VALUES (5135, 'Software y plataformas (bots, SaaS)', 513520, '1',
            0, 0, 0, " . (int)$ord['n'] . ", 1, '2', 'expense', '{$USER}', NOW(), NOW(), 0, {$STORE}, '513520')", $errores);
    $subBot = $APPLY ? (int)$m->insert_id : 0;
    echo "     subcuenta creada: id {$subBot}\n";
}

$yaCat = one($m, "SELECT id FROM expense_categories WHERE code = 'GAS-SOF' AND deleted = 0");
if ($yaCat) {
    echo "     categoría GAS-SOF ya existe (id {$yaCat['id']})\n";
} else {
    $sid = $subBot ?: 'NULL';
    exec_sql($m, $APPLY, "INSERT INTO expense_categories (code, name, description,
            accounting_account_id, accounting_subaccount_id, is_active, deleted, created_at, updated_at)
        VALUES ('GAS-SOF', 'Software y bots (BuilderBot, SaaS)',
            'Suscripciones de software y plataformas: BuilderBot, hosting, herramientas SaaS. PUC 513520 procesamiento electrónico de datos.',
            {$GRP_SERVICIOS}, {$sid}, 1, 0, NOW(), NOW())", $errores);
    echo "     categoría GAS-SOF creada\n";
}

// ═══ 2. Flete de MAM / MAM-Online fuera del gasto de Ledxury ═══════════════
echo "\n── 2. Flete de terceros que hoy está en el gasto de Ledxury (513540)\n";
$fletes = rows($m, "
    SELECT e.entryID, e.entryDate, e.entryTransactionId lote, e.entryTransactionType t,
           CAST(e.entryDebitBalance AS DECIMAL(18,2)) monto
    FROM entries e
    WHERE e.deleted = 0 AND e.entryDebitAccount = {$ACC_FREIGHT}
      AND e.entryTransactionType IN ('contrapago_freight', 'contrapago_ajuste2')
    ORDER BY e.entryDate");

$totalRecl = array();
foreach ($fletes as $f) {
    $lote = (int)$f['lote'];
    $yaHecho = one($m, "SELECT COUNT(*) n FROM entries
                        WHERE deleted = 0 AND entryTransactionType = 'contrapago_freight_terceros'
                          AND entryTransactionId = {$lote}");
    if ((int)$yaHecho['n'] > 0) {
        echo "  lote {$lote}: ya reclasificado, no se repite\n";
        continue;
    }

    // Reparto por compañía, a prorrata de lo cruzado contra este lote.
    $porCompania = array();
    $vin = rows($m, "SELECT cip.invoice_id, cip.monto_cobrado, ci.valor_total, ci.numero_factura
                     FROM contrapago_invoice_payments cip
                     JOIN contrapago_invoices ci ON ci.id = cip.invoice_id
                     WHERE cip.batch_id = {$lote}");
    if (!$vin) { echo "  lote {$lote}: sin facturas vinculadas, queda como flete de Ledxury\n"; continue; }

    foreach ($vin as $v) {
        if ((float)$v['valor_total'] <= 0) continue;
        $prop = (float)$v['monto_cobrado'] / (float)$v['valor_total'];
        foreach (rows($m, "SELECT company, COALESCE(SUM(valor_total),0) t
                           FROM contrapago_invoice_items
                           WHERE invoice_id = " . (int)$v['invoice_id'] . "
                             AND company IN ('mam','mam_online')
                           GROUP BY company") as $g) {
            $val = round((float)$g['t'] * $prop, 2);
            if ($val <= 0) continue;
            if (!isset($porCompania[$g['company']])) $porCompania[$g['company']] = 0;
            $porCompania[$g['company']] = round($porCompania[$g['company']] + $val, 2);
        }
    }
    if (!$porCompania) { echo "  lote {$lote}: todo el flete es de Ledxury\n"; continue; }

    $suma = array_sum($porCompania);
    if ($suma > (float)$f['monto'] + 0.01) {
        echo "  lote {$lote}: OJO, el flete de terceros (" . money($suma) . ") supera lo asentado ("
           . money($f['monto']) . "). Se salta para revisar a mano.\n";
        continue;
    }

    echo "  lote {$lote} (asiento {$f['entryID']}, " . money($f['monto']) . " el {$f['entryDate']})\n";
    foreach ($porCompania as $comp => $val) {
        $label = strtoupper(str_replace('_', '-', $comp));
        if (!isset($totalRecl[$comp])) $totalRecl[$comp] = 0;
        $totalRecl[$comp] = round($totalRecl[$comp] + $val, 2);
        asiento($m, $APPLY, $errores,
            "Flete de guías de {$label} descontado de nuestra consignación — lote #{$lote}. "
            . "No es gasto de Ledxury: lo debe {$label}.",
            $f['entryDate'], $ACC_IC_RECV, $ACC_FREIGHT, $val,
            'contrapago_freight_terceros', $lote, $USER, $STORE);
    }
}
echo "\n  total sacado del gasto de fletes:\n";
foreach ($totalRecl as $comp => $val) printf("     %-12s %16s\n", $comp, money($val));
if ($totalRecl) echo "     " . str_pad('TOTAL', 12) . str_pad(money(array_sum($totalRecl)), 16, ' ', STR_PAD_LEFT) . "\n";

// ═══ 3. Movimiento intercompañía que faltaba (flete MAM-Online) ════════════
echo "\n── 3. Movimiento intercompañía del flete de MAM-Online\n";
$falta = rows($m, "
    SELECT it.company, ci.id inv_id, ci.numero_factura, ci.fecha_corte,
           COALESCE(SUM(it.valor_total),0) total
    FROM contrapago_invoice_items it
    JOIN contrapago_invoices ci ON ci.id = it.invoice_id
    WHERE it.company = 'mam_online' AND ci.status = 'descontada'
    GROUP BY ci.id");
foreach ($falta as $f) {
    $ya = one($m, "SELECT id FROM intercompany_movements
                   WHERE deleted_at IS NULL AND concepto = 'flete_mam'
                     AND partner_company = 'mam_online' AND contrapago_invoice_id = " . (int)$f['inv_id']);
    if ($ya) { echo "     factura {$f['numero_factura']}: ya registrado (mov {$ya['id']})\n"; continue; }
    $desc = "Flete de guías de MAM-Online en la factura {$f['numero_factura']} de Interrapidísimo, "
          . "descontado de nuestra consignación. Lo debe MAM-Online.";
    echo "     factura {$f['numero_factura']} ({$f['fecha_corte']}): " . money($f['total']) . "\n";
    exec_sql($m, $APPLY, "INSERT INTO intercompany_movements (tipo, concepto, direccion,
            partner_company, monto, fecha, descripcion, contrapago_invoice_id, status, created_by, created_at, updated_at)
        VALUES ('cobro_pendiente', 'flete_mam', 'mam_debe_ledxury', 'mam_online',
            " . number_format((float)$f['total'], 2, '.', '') . ", '{$f['fecha_corte']}',
            '" . $m->real_escape_string($desc) . "', " . (int)$f['inv_id'] . ", 'activo', '{$USER}', NOW(), NOW())", $errores);
}

// ═══ 4. Parqueadero fuera de la cuenta de fletes ═══════════════════════════
echo "\n── 4. Parqueadero y gasolina: de Fletes a Gastos diversos\n";
$g = one($m, "SELECT r.id, r.code, r.amount, r.entry_id, r.expense_category_id
              FROM expense_records r WHERE r.id = 2 AND r.deleted = 0");
if (!$g) { echo "     no existe el gasto 2, se salta\n"; }
elseif ((int)$g['expense_category_id'] === $CAT_OTROS) { echo "     ya está en Gastos diversos\n"; }
else {
    echo "     {$g['code']} " . money($g['amount']) . ": categoría Fletes -> Otros gastos diversos\n";
    exec_sql($m, $APPLY, "UPDATE expense_records SET expense_category_id = {$CAT_OTROS}, updated_at = NOW()
                          WHERE id = " . (int)$g['id'], $errores);
    if ($g['entry_id']) {
        echo "     asiento de causación {$g['entry_id']}: DR {$ACC_FREIGHT} -> DR {$ACC_DIVERSOS}\n";
        exec_sql($m, $APPLY, "UPDATE entries SET entryDebitAccount = {$ACC_DIVERSOS}, updated_at = NOW()
                              WHERE entryID = " . (int)$g['entry_id'] . " AND entryDebitAccount = {$ACC_FREIGHT}", $errores);
    }
}

// ═══ 5. Histórico: CxC a MAM de antes de julio ═════════════════════════════
if (!$HIST) {
    echo "\n── 5. CxC histórica a MAM: NO se toca (falta --incluir-historico)\n";
    $pre = rows($m, "
        SELECT ci.numero_factura, ci.fecha_corte, ci.descontada_en_batch_id lote,
               COALESCE(SUM(it.valor_total),0) total
        FROM contrapago_invoices ci JOIN contrapago_invoice_items it ON it.invoice_id = ci.id
        WHERE it.company = 'mam' AND ci.status = 'descontada' AND ci.fecha_corte < '2026-07-01'
          AND NOT EXISTS (SELECT 1 FROM entries e WHERE e.deleted = 0
                          AND e.entryTransactionType IN ('contrapago_freight','contrapago_ajuste2')
                          AND e.entryTransactionId = ci.descontada_en_batch_id)
        GROUP BY ci.id ORDER BY ci.fecha_corte");
    $tp = 0;
    foreach ($pre as $p) { $tp += (float)$p['total'];
        printf("     factura %-9s %s lote %-4s %16s\n", $p['numero_factura'], $p['fecha_corte'], $p['lote'], money($p['total'])); }
    $pago = one($m, "SELECT COALESCE(SUM(monto),0) t FROM intercompany_movements
                     WHERE deleted_at IS NULL AND status='activo' AND tipo='pago_recibido' AND partner_company='mam'");
    echo "     flete de MAM descontado antes de julio: " . money($tp) . "\n";
    echo "     menos lo que MAM ya pagó:               " . money($pago['t']) . "\n";
    echo "     CxC histórica neta a llevar al balance:  " . money($tp - (float)$pago['t']) . "\n";
    echo "     (contrapartida: 370501 utilidades acumuladas, porque su efectivo ya está en la apertura)\n";
} else {
    echo "\n── 5. CxC histórica a MAM (antes de julio) al balance\n";
    $yaH = one($m, "SELECT COUNT(*) n FROM entries WHERE deleted = 0
                    AND entryTransactionType = 'cxc_mam_historica'");
    if ((int)$yaH['n'] > 0) { echo "     ya está registrada, no se repite\n"; }
    else {
        $pre = rows($m, "
            SELECT COALESCE(SUM(it.valor_total),0) total
            FROM contrapago_invoices ci JOIN contrapago_invoice_items it ON it.invoice_id = ci.id
            WHERE it.company = 'mam' AND ci.status = 'descontada' AND ci.fecha_corte < '2026-07-01'
              AND NOT EXISTS (SELECT 1 FROM entries e WHERE e.deleted = 0
                              AND e.entryTransactionType IN ('contrapago_freight','contrapago_ajuste2')
                              AND e.entryTransactionId = ci.descontada_en_batch_id)");
        $tp = round((float)$pre[0]['total'], 2);
        $pago = one($m, "SELECT COALESCE(SUM(monto),0) t FROM intercompany_movements
                         WHERE deleted_at IS NULL AND status='activo' AND tipo='pago_recibido' AND partner_company='mam'");
        $pg = round((float)$pago['t'], 2);
        if ($tp > 0) {
            asiento($m, $APPLY, $errores,
                'CxC a MAM por fletes descontados de nuestras consignaciones antes de julio 2026 (lotes 27, 29 y 30). '
                . 'Su efectivo ya está dentro del asiento de apertura del 01/07, así que la contrapartida es utilidades acumuladas.',
                '2026-06-30', $ACC_IC_RECV, $ACC_RETAINED, $tp, 'cxc_mam_historica', 0, $USER, $STORE);
        }
        if ($pg > 0) {
            asiento($m, $APPLY, $errores,
                'Pago que MAM ya hizo a cuenta de esos fletes (transferencia del 06/05/2026). '
                . 'Igual que el cargo, su efectivo ya está dentro del asiento de apertura.',
                '2026-06-30', $ACC_RETAINED, $ACC_IC_RECV, $pg, 'cxc_mam_historica', 0, $USER, $STORE);
        }
    }
}

// ═══ 6. Recalcular saldos ══════════════════════════════════════════════════
echo "\n── 6. Recalculando saldos de subcuentas\n";
exec_sql($m, $APPLY, "
    UPDATE subaccounts s SET
      s.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAccount  = s.id),
      s.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAccount = s.id)
    WHERE s.deleted = 0", $errores);
exec_sql($m, $APPLY, "
    UPDATE subaccounts SET accountBalance = CASE WHEN accountSide = '1'
        THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
    WHERE deleted = 0", $errores);

if ($APPLY) {
    if ($errores) { echo "\n{$errores} error(es): ROLLBACK, no se cambió nada.\n"; $m->rollback(); exit(1); }
    $m->commit();
}

// ═══ Verificación ══════════════════════════════════════════════════════════
echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if (!$APPLY) exit(0);

echo "\n  cuentas afectadas:\n";
foreach (rows($m, "SELECT id, pucCode, accountName, accountBalance FROM subaccounts
                   WHERE id IN ({$ACC_FREIGHT}, {$ACC_DIVERSOS}, {$ACC_IC_RECV}, {$ACC_RETAINED}, 66)
                      OR pucCode = '513520'
                   ORDER BY pucCode") as $r)
    printf("    id %-4s %-9s %-46s %16s\n", $r['id'], $r['pucCode'], substr($r['accountName'], 0, 46), money($r['accountBalance']));

$d = one($m, "SELECT COALESCE(SUM(CAST(entryDebitBalance AS DECIMAL(18,2)))
                            - SUM(CAST(entryCreditBalance AS DECIMAL(18,2))),0) t
              FROM entries WHERE deleted = 0");
echo "\n  partida doble global: " . money($d['t']) . " (debe ser 0)\n";

echo "\n  intercompañías por compañía:\n";
foreach (rows($m, "SELECT partner_company, concepto, tipo, COUNT(*) n, COALESCE(SUM(monto),0) t
                   FROM intercompany_movements WHERE deleted_at IS NULL AND status = 'activo'
                   GROUP BY partner_company, concepto, tipo ORDER BY partner_company, concepto") as $r)
    printf("    %-12s %-16s %-16s %3s  %16s\n", $r['partner_company'], $r['concepto'], $r['tipo'], $r['n'], money($r['t']));
