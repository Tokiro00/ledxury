<?php
/**
 * Cierra la brecha entre el banco de tesorería y el banco contable (111005).
 *
 * Ejecutar en el server de ledxury:
 *   php cerrar_brecha_banco_contable.php            (simulación)
 *   php cerrar_brecha_banco_contable.php --apply
 *
 * DE DÓNDE SALE LA BRECHA (medida con conciliar_banco_tesoreria_vs_contable.php):
 *   tesorería  $23.731.761,18
 *   contable   $26.967.643,18
 *   brecha     $ 3.235.882,00
 *
 * Se descompone exacto en tres cosas:
 *
 *   1. Gasto de publicidad Facebook del 30/06, $2.660.000 (expense_records id 3).
 *      Salió del banco en tesorería pero nunca se causó ni se pagó en
 *      contabilidad: no tiene entry_id ni payment_entry_id. Así que además de
 *      inflar el banco, falta en el estado de resultados de junio.
 *
 *   2. Gasto de parqueadero y gasolina del 09/07, $41.133,71 (id 2). Mismo caso.
 *
 *   3. PAGO 11 contado dos veces, $575.882. Interrapidísimo consignó el 19/06,
 *      o sea antes del corte del asiento de apertura del 01/07, así que esa
 *      plata ya venía dentro del saldo de apertura. El asiento del 11/07
 *      (12165) volvió a debitar el banco. En tesorería ya se corrigió con el
 *      ajuste AJUSTE-DUP-PAGO11; falta el lado contable.
 *      El crédito a cartera de ese asiento SÍ es correcto (las facturas se
 *      cobraron de verdad), así que solo se reversa la parte del banco, contra
 *      utilidades acumuladas — la misma cuenta que usó la apertura.
 *
 *   4. Y el asiento de apertura (12132) quedó corto en $41.133,71: se calculó
 *      contando el parqueadero del 09/07 como si fuera de junio, y sin el
 *      gasto de Facebook. Se ajusta a $7.534.014,94.
 *
 * Con eso el banco contable queda en $23.731.761,18, igual a tesorería.
 *
 * Idempotente: aborta si ya existen los asientos que crea.
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

$USER      = '71339095';   // Alex
$STORE     = 1;
$ACC_BANK  = 39;           // 111005 Bancolombia
$ACC_PAY   = 42;           // 220505 Proveedores nacionales
$AUX_PAY   = 6010;         // SIN PROVEEDOR
$ACC_RET   = 45;           // 370501 Utilidades acumuladas
$ID_OPEN   = 12132;        // asiento de apertura del banco, 01/07
$OPEN_OLD  = 7492881.23;
$OPEN_NEW  = 7534014.94;

function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function money($v) { return '$' . number_format((float)$v, 2, ',', '.'); }

$errores = 0;
function exec_sql($m, $APPLY, $sql, &$errores) {
    if (!$APPLY) { echo "     [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 100)) . "\n"; return true; }
    if ($m->query($sql) === false) { echo "     ERROR: {$m->error}\n"; $errores++; return false; }
    return true;
}

// Crea un asiento de una línea (un débito y un crédito).
function asiento($m, $APPLY, &$errores, $desc, $date, $dr, $drAux, $cr, $crAux, $val, $type, $tid, $user, $store) {
    echo "     asiento: DR {$dr}" . ($drAux ? "/{$drAux}" : '') . " / CR {$cr}" . ($crAux ? "/{$crAux}" : '')
       . "  " . money($val) . "  [{$date}]  — {$desc}\n";
    $d = $m->real_escape_string($desc);
    $drA = $drAux ? (int)$drAux : 'NULL';
    $crA = $crAux ? (int)$crAux : 'NULL';
    $v = number_format((float)$val, 2, '.', '');
    $sql = "INSERT INTO entries (userID, entryDescription, entryType,
                entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
                entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
                entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
                entryTransactionType, entryTransactionId, entryDate)
            VALUES ('{$user}', '{$d}', 1,
                {$dr}, {$drA}, '{$v}',
                {$cr}, {$crA}, '{$v}',
                1, '{$user}', NOW(), 0, {$store},
                '{$type}', " . (int)$tid . ", '{$date}')";
    return exec_sql($m, $APPLY, $sql, $errores);
}

// ── Foto inicial ────────────────────────────────────────────────────────────
$b = one($m, "SELECT currentBalance FROM bank_accounts WHERE idBankAccount = 1");
$s = one($m, "SELECT accountBalance FROM subaccounts WHERE id = {$ACC_BANK}");
echo "antes:  tesorería " . money($b['currentBalance']) . "   contable " . money($s['accountBalance'])
   . "   brecha " . money((float)$s['accountBalance'] - (float)$b['currentBalance']) . "\n\n";

// ── Idempotencia ────────────────────────────────────────────────────────────
$ya = one($m, "SELECT COUNT(*) n FROM entries
               WHERE deleted = 0 AND entryTransactionType IN ('expense_accrual','expense_payment')
                 AND entryTransactionId IN (2,3)");
$yaCp = one($m, "SELECT COUNT(*) n FROM entries
                 WHERE deleted = 0 AND entryTransactionType = 'contrapago_dup_reverso'");
if ((int)$ya['n'] > 0 || (int)$yaCp['n'] > 0) {
    echo "Ya se corrió antes (hay {$ya['n']} asiento(s) de gasto y {$yaCp['n']} reverso(s)). No se repite.\n";
    exit(0);
}

if ($APPLY) $m->begin_transaction();

// ── 1 y 2. Causar y pagar los dos gastos que quedaron sin asiento ──────────
$gastos = rows($m, "SELECT e.id, e.code, e.description, e.amount, e.expense_date, e.store_id,
                           ec.accounting_subaccount_id sub, s.pucCode, s.accountName
                    FROM expense_records e
                    JOIN expense_categories ec ON ec.id = e.expense_category_id
                    JOIN subaccounts s ON s.id = ec.accounting_subaccount_id
                    WHERE e.id IN (2,3) AND e.deleted = 0
                    ORDER BY e.expense_date");
foreach ($gastos as $g) {
    $st = (int)$g['store_id'] ?: $STORE;
    echo "── gasto {$g['code']} {$g['expense_date']} " . money($g['amount'])
       . " — {$g['description']}\n     cuenta: {$g['sub']} {$g['pucCode']} {$g['accountName']}\n";

    asiento($m, $APPLY, $errores, "Causación gasto: {$g['description']}", $g['expense_date'],
        (int)$g['sub'], null, $ACC_PAY, $AUX_PAY, $g['amount'], 'expense_accrual', $g['id'], $USER, $st);
    $idAcc = $APPLY ? $m->insert_id : 0;

    asiento($m, $APPLY, $errores, "Pago gasto: {$g['description']}", $g['expense_date'],
        $ACC_PAY, $AUX_PAY, $ACC_BANK, null, $g['amount'], 'expense_payment', $g['id'], $USER, $st);
    $idPay = $APPLY ? $m->insert_id : 0;

    echo "     enlazando expense_records {$g['id']}: entry_id={$idAcc}, payment_entry_id={$idPay}\n";
    if ($APPLY) {
        exec_sql($m, $APPLY, "UPDATE expense_records SET entry_id = {$idAcc}, payment_entry_id = {$idPay},
                              updated_at = NOW() WHERE id = " . (int)$g['id'], $errores);
    }
}

// ── 3. Reverso del banco en el duplicado de PAGO 11 ────────────────────────
echo "── duplicado de PAGO 11 (lote 33)\n";
asiento($m, $APPLY, $errores,
    'Reverso del banco en PAGO 11: Interrapidísimo consignó el 19/06, antes del corte, así que esa plata ya venía en el saldo de apertura del 01/07. El cobro a cartera sí es correcto y se conserva.',
    '2026-07-11', $ACC_RET, null, $ACC_BANK, null, 575882.00, 'contrapago_dup_reverso', 33, $USER, $STORE);

// ── 4. Ajuste del asiento de apertura ──────────────────────────────────────
echo "── asiento de apertura {$ID_OPEN}: " . money($OPEN_OLD) . " → " . money($OPEN_NEW)
   . "  (+" . money($OPEN_NEW - $OPEN_OLD) . ")\n";
$chk = one($m, "SELECT CAST(entryDebitBalance AS DECIMAL(18,2)) v FROM entries WHERE entryID = {$ID_OPEN}");
if (!$chk || abs((float)$chk['v'] - $OPEN_OLD) > 0.01) {
    echo "     ABORTA: el asiento {$ID_OPEN} no vale " . money($OPEN_OLD)
       . " (vale " . money($chk ? $chk['v'] : 0) . "). Revisar a mano.\n";
    if ($APPLY) $m->rollback();
    exit(1);
}
$nv = number_format($OPEN_NEW, 2, '.', '');
exec_sql($m, $APPLY,
    "UPDATE entries SET entryDebitBalance = '{$nv}', entryCreditBalance = '{$nv}',
        entryDescription = CONCAT(entryDescription, ' [ajustado 20/08/2026: +41.133,71 — el cálculo original contó el parqueadero del 09/07 como si fuera de junio y no incluyó el gasto de Facebook del 30/06]'),
        updated_at = NOW()
     WHERE entryID = {$ID_OPEN}", $errores);

// ── 5. Alinear las fechas de los asientos de contrapago con tesorería ──────
// corregir_fechas_contrapagos_julio.php movió los movimientos de PAGO 12 a 15 a
// la fecha real de la consignación, pero los asientos siguieron con la fecha de
// digitación (11/07 y 29/07). No cambia ningún saldo, pero el libro diario y el
// libro del banco mostraban la misma plata en días distintos.
echo "── alineando fechas de asientos de contrapago con el movimiento de tesorería\n";
$desfase = rows($m, "
    SELECT e.entryID, e.entryDate, e.entryTransactionId lote, DATE(c.movementDate) fecha_real,
           e.entryTransactionType t
    FROM entries e
    JOIN cash_movements c ON c.referenceType = 'contrapago'
                         AND c.referenceId = e.entryTransactionId
                         AND c.movementType = 'ingreso'
                         AND c.deleted = 0 AND c.status <> 'anulado'
    WHERE e.deleted = 0 AND e.entryTransactionType LIKE 'contrapago%'
      AND e.entryDate <> DATE(c.movementDate)
    ORDER BY e.entryTransactionId, e.entryID");
if (!$desfase) {
    echo "     ninguno desfasado\n";
} else {
    foreach ($desfase as $x)
        echo "     asiento {$x['entryID']} (lote {$x['lote']}, {$x['t']}): {$x['entryDate']} → {$x['fecha_real']}\n";
    exec_sql($m, $APPLY, "
        UPDATE entries e
        JOIN cash_movements c ON c.referenceType = 'contrapago'
                             AND c.referenceId = e.entryTransactionId
                             AND c.movementType = 'ingreso'
                             AND c.deleted = 0 AND c.status <> 'anulado'
        SET e.entryDate = DATE(c.movementDate), e.updated_at = NOW()
        WHERE e.deleted = 0 AND e.entryTransactionType LIKE 'contrapago%'
          AND e.entryDate <> DATE(c.movementDate)", $errores);
}

// ── 6. Recalcular saldos de subcuentas y auxiliares ────────────────────────
echo "── recalculando saldos\n";
exec_sql($m, $APPLY, "
    UPDATE subaccounts s SET
      s.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAccount  = s.id),
      s.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAccount = s.id)
    WHERE s.deleted = 0", $errores);
exec_sql($m, $APPLY, "
    UPDATE subaccounts SET accountBalance = CASE WHEN accountSide = '1'
        THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
    WHERE deleted = 0", $errores);
exec_sql($m, $APPLY, "
    UPDATE auxiliary_subaccounts a SET
      a.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAuxaccount  = a.id),
      a.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAuxaccount = a.id)
    WHERE a.deleted = 0", $errores);
exec_sql($m, $APPLY, "
    UPDATE auxiliary_subaccounts SET accountBalance = CASE WHEN accountSide = '1'
        THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
    WHERE deleted = 0", $errores);

if ($APPLY) {
    if ($errores) { echo "\n{$errores} error(es): ROLLBACK, no se cambió nada.\n"; $m->rollback(); exit(1); }
    $m->commit();
}

// ── Verificación ────────────────────────────────────────────────────────────
echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if (!$APPLY) exit(0);

$b = one($m, "SELECT currentBalance FROM bank_accounts WHERE idBankAccount = 1");
$s = one($m, "SELECT accountBalance FROM subaccounts WHERE id = {$ACC_BANK}");
$brecha = (float)$s['accountBalance'] - (float)$b['currentBalance'];
echo "  banco tesorería " . money($b['currentBalance']) . "\n";
echo "  banco contable  " . money($s['accountBalance']) . "\n";
echo "  brecha          " . money($brecha) . (abs($brecha) < 0.01 ? "   <- cuadrado\n" : "   <- REVISAR\n");
$d = one($m, "SELECT COALESCE(SUM(CAST(entryDebitBalance AS DECIMAL(18,2)))
                            - SUM(CAST(entryCreditBalance AS DECIMAL(18,2))),0) t
              FROM entries WHERE deleted = 0");
echo "  partida doble global: " . money($d['t']) . " (debe ser 0)\n";
foreach (rows($m, "SELECT id, pucCode, accountName, accountBalance FROM subaccounts
                   WHERE id IN (39,42,45,55,56) ORDER BY pucCode") as $r)
    printf("  sub %-4s %-8s %-46s %s\n", $r['id'], $r['pucCode'], $r['accountName'], money($r['accountBalance']));
foreach (rows($m, "SELECT id, entry_id, payment_entry_id, code, amount FROM expense_records WHERE id IN (2,3,4)") as $r)
    printf("  gasto %-3s %-9s causación %-7s pago %-7s %s\n", $r['id'], $r['code'],
        $r['entry_id'], $r['payment_entry_id'], money($r['amount']));
