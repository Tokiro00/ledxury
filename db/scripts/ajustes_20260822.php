<?php
/**
 * Dos ajustes contra el balance inicial (370501 utilidades acumuladas).
 *
 *   php ajustes_20260822.php            (simulación)
 *   php ajustes_20260822.php --apply
 *
 * 1. GERMAM MARIA BARRANQUILLA queda en CERO por las dos puntas: Alex confirma
 *    que no le debe nada. Hoy su cuenta tiene:
 *      · comisión de bot pendiente  $1.043.418  (pasivo, aux 5724 de 233525)
 *      · anticipo pendiente           $843.418  (activo, aux 5729 de 136525,
 *                                                el ANT0012 del 21/08)
 *    Se llevan las dos a cero contra utilidades acumuladas, y el anticipo se
 *    marca como saldado en el módulo para que Liquidaciones no lo siga
 *    mostrando ni lo vuelva a restar.
 *    Efecto neto en el patrimonio: +$200.000, que es justo el neto que se le
 *    iba a girar y que ya no se gira.
 *
 * 2. BANCO al saldo real de hoy: $5.081.154,27. Se hace con un movimiento de
 *    tipo 'ajuste' en tesorería (delta firmado en amount, la convención de
 *    siempre) MÁS su asiento contra utilidades acumuladas, para que las dos
 *    partes queden iguales. Los ajustes no asientan solos por diseño, así que
 *    el asiento va explícito aquí.
 *
 * Idempotente: aborta si ya existen asientos de tipo 'ajuste_20260822'.
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

$FECHA   = '2026-08-22';
$TIPO    = 'ajuste_20260822';
$USER    = '71339095';
$STORE   = 1;
$ACC_BANCO   = 39;   // 111005
$ACC_ANTIC   = 63;   // 136525
$ACC_COMIS   = 65;   // 233525
$ACC_PATRI   = 45;   // 370501
$VENDEDORA   = '1048937562';
$OBJ_BANCO   = 5081154.27;
$ID_BANCO    = 1;

$errores = 0;
function rows($m, $sql) { $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o; }
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function mo($v) { return '$' . number_format((float)$v, 2, ',', '.'); }
function ex($m, $APPLY, $sql, &$errores) {
    if (!$APPLY) { echo "       [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 100)) . "\n"; return true; }
    if ($m->query($sql) === false) { echo "       ERROR: {$m->error}\n"; $errores++; return false; }
    return true;
}
function asiento($m, $APPLY, &$errores, $desc, $dr, $drAux, $cr, $crAux, $val, $tipo, $fecha, $user, $store) {
    $val = round((float)$val, 2);
    if ($val <= 0) return;
    echo "     DR {$dr}" . ($drAux ? "/{$drAux}" : '') . " / CR {$cr}" . ($crAux ? "/{$crAux}" : '')
       . "  " . mo($val) . "\n       {$desc}\n";
    $d = $m->real_escape_string($desc);
    $v = number_format($val, 2, '.', '');
    $a = $drAux ? (int)$drAux : 'NULL';
    $b = $crAux ? (int)$crAux : 'NULL';
    ex($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryType,
            entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
            entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
            entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
            entryTransactionType, entryTransactionId, entryDate)
        VALUES ('{$user}', '{$d}', 1, {$dr}, {$a}, '{$v}', {$cr}, {$b}, '{$v}',
            1, '{$user}', NOW(), 0, {$store}, '{$tipo}', 0, '{$fecha}')", $errores);
}

$ya = one($m, "SELECT COUNT(*) n FROM entries WHERE deleted = 0 AND entryTransactionType = '{$TIPO}'");
if ((int)$ya['n'] > 0) { echo "Ya existen {$ya['n']} asientos de tipo '{$TIPO}'. No se repite.\n"; exit(0); }

if ($APPLY) $m->begin_transaction();

// ── 1. Germam Maria Barranquilla a cero ────────────────────────────────────
echo "── 1. GERMAM MARIA BARRANQUILLA a cero\n";
$auxCom = one($m, "SELECT id, (accountCredit - accountDebit) saldo FROM auxiliary_subaccounts
                   WHERE accountAccount = '{$VENDEDORA}' AND accountType = 'bot_commission' AND deleted = 0");
$auxAnt = one($m, "SELECT id, (accountDebit - accountCredit) saldo FROM auxiliary_subaccounts
                   WHERE accountAccount = '{$VENDEDORA}' AND accountType = 'employee_advance' AND deleted = 0");
$com = $auxCom ? round((float)$auxCom['saldo'], 2) : 0;
$ant = $auxAnt ? round((float)$auxAnt['saldo'], 2) : 0;
printf("     comisión pendiente %16s   anticipo pendiente %16s   neto %16s\n", mo($com), mo($ant), mo($com - $ant));

if ($com > 0.005) {
    asiento($m, $APPLY, $errores,
        'Ajuste al ' . $FECHA . ': se lleva a cero la comisión de bot pendiente de Germam Maria Barranquilla — Alex confirma que no se le debe nada. Contra utilidades acumuladas.',
        $ACC_COMIS, (int)$auxCom['id'], $ACC_PATRI, null, $com, $TIPO, $FECHA, $USER, $STORE);
} else { echo "     la comisión ya está en cero\n"; }

if ($ant > 0.005) {
    asiento($m, $APPLY, $errores,
        'Ajuste al ' . $FECHA . ': se lleva a cero el anticipo pendiente de Germam Maria Barranquilla (ANT0012), que deja de ser cobrable. Contra utilidades acumuladas.',
        $ACC_PATRI, null, $ACC_ANTIC, (int)$auxAnt['id'], $ant, $TIPO, $FECHA, $USER, $STORE);
    echo "     marcando sus anticipos como saldados en el módulo\n";
    ex($m, $APPLY, "UPDATE employee_advances
        SET outstanding_balance = 0, status = 'pagado',
            observations = CONCAT(COALESCE(observations,''), ' | Saldado en el ajuste del {$FECHA}: no se le debe nada y el anticipo deja de ser cobrable.'),
            updated_at = NOW()
        WHERE employee_id = '{$VENDEDORA}' AND status = 'desembolsado'
          AND (deleted IS NULL OR deleted = 0) AND outstanding_balance > 0", $errores);
} else { echo "     no tiene anticipos pendientes\n"; }

printf("     efecto en el patrimonio: %s\n", mo($com - $ant));

// ── 2. Banco al saldo real ─────────────────────────────────────────────────
echo "\n── 2. BANCO al saldo real de hoy\n";
$NETO_SQL = "(SELECT COALESCE(SUM(CAST(e.entryDebitBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAccount = s.id)
           - (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAccount = s.id)";
$sb = one($m, "SELECT ({$NETO_SQL}) v FROM subaccounts s WHERE s.id = {$ACC_BANCO}");
$actual = round((float)$sb['v'], 2);
$delta  = round($OBJ_BANCO - $actual, 2);
printf("     contable actual %16s  ->  objetivo %16s   ajuste %16s\n", mo($actual), mo($OBJ_BANCO), mo($delta));

if (abs($delta) > 0.005) {
    $desc = 'Ajuste al ' . $FECHA . ': el saldo del banco queda en '
          . number_format($OBJ_BANCO, 2, ',', '.') . ', que es el saldo real del extracto de hoy.';
    if ($delta > 0) asiento($m, $APPLY, $errores, $desc . ' Contra utilidades acumuladas.',
        $ACC_BANCO, null, $ACC_PATRI, null, $delta, $TIPO, $FECHA, $USER, $STORE);
    else            asiento($m, $APPLY, $errores, $desc . ' Contra utilidades acumuladas.',
        $ACC_PATRI, null, $ACC_BANCO, null, -$delta, $TIPO, $FECHA, $USER, $STORE);

    echo "     movimiento de ajuste en tesorería " . mo($delta) . "\n";
    ex($m, $APPLY, "INSERT INTO cash_movements
        (sourceType, sourceId, movementType, amount, concept, category, documentNumber,
         movementDate, status, created_at, updated_at)
        VALUES ('banco', {$ID_BANCO}, 'ajuste', " . number_format($delta, 2, '.', '') . ",
         '" . $m->real_escape_string($desc . ' El lado contable va en el asiento de ajuste.') . "',
         'ajuste', 'AJUSTE-{$FECHA}', '{$FECHA} 23:00:00', 'activo', NOW(), NOW())", $errores);
} else { echo "     el banco ya está en el saldo objetivo\n"; }

// ── 3. Recalcular saldos ───────────────────────────────────────────────────
echo "\n── 3. Recalculando saldos\n";
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
    // El campo currentBalance ya es un espejo de los movimientos; se refresca.
    "UPDATE bank_accounts b SET b.currentBalance = (
        SELECT b.initialBalance + COALESCE(SUM(CASE
            WHEN c.movementType IN ('ingreso','apertura') AND c.sourceType='banco' AND c.sourceId=b.idBankAccount THEN c.amount
            WHEN c.movementType IN ('egreso','cierre')    AND c.sourceType='banco' AND c.sourceId=b.idBankAccount THEN -c.amount
            WHEN c.movementType='transferencia'           AND c.sourceType='banco' AND c.sourceId=b.idBankAccount THEN -c.amount
            WHEN c.movementType='transferencia' AND c.destinationType='banco' AND c.destinationId=b.idBankAccount THEN c.amount
            WHEN c.movementType='ajuste'                  AND c.sourceType='banco' AND c.sourceId=b.idBankAccount THEN c.amount
            ELSE 0 END),0)
        FROM cash_movements c WHERE c.deleted=0 AND c.status<>'anulado'
          AND ((c.sourceType='banco' AND c.sourceId=b.idBankAccount)
            OR (c.destinationType='banco' AND c.destinationId=b.idBankAccount AND c.movementType='transferencia'))
     ), b.updated_at = NOW() WHERE b.deleted = 0",
) as $sql) ex($m, $APPLY, $sql, $errores);

if ($APPLY) {
    if ($errores) { echo "\n{$errores} error(es): ROLLBACK, no se cambió nada.\n"; $m->rollback(); exit(1); }
    $m->commit();
}

echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if (!$APPLY) exit(0);

echo "\n  comisión de bot por persona:\n";
foreach (rows($m, "SELECT a.accountAccount uid, u.name, (a.accountCredit - a.accountDebit) com,
                          COALESCE((SELECT SUM(ea.outstanding_balance) FROM employee_advances ea
                                    WHERE ea.employee_id = a.accountAccount AND ea.status='desembolsado'
                                      AND (ea.deleted IS NULL OR ea.deleted=0)),0) ant
                   FROM auxiliary_subaccounts a LEFT JOIN users u ON u.idUser = a.accountAccount
                   WHERE a.accountType = 'bot_commission' AND a.deleted = 0
                   ORDER BY (a.accountCredit - a.accountDebit) DESC") as $r)
    printf("    %-30s comisión %14s  anticipos %13s  neto %14s\n", substr((string)$r['name'], 0, 30),
        mo($r['com']), mo($r['ant']), mo((float)$r['com'] - (float)$r['ant']));

echo "\n  cuentas afectadas:\n";
foreach (rows($m, "SELECT id, pucCode, accountName, accountBalance FROM subaccounts
                   WHERE id IN ({$ACC_BANCO}, {$ACC_ANTIC}, {$ACC_COMIS}, {$ACC_PATRI}) ORDER BY pucCode") as $r)
    printf("    %-9s %-42s %18s\n", $r['pucCode'], substr($r['accountName'], 0, 42), mo($r['accountBalance']));

$b = one($m, "SELECT currentBalance v FROM bank_accounts WHERE idBankAccount = {$ID_BANCO}");
$sb = one($m, "SELECT ({$NETO_SQL}) v FROM subaccounts s WHERE s.id = {$ACC_BANCO}");
echo "\n  banco tesorería " . mo($b['v']) . "   contable " . mo($sb['v'])
   . "   brecha " . mo((float)$sb['v'] - (float)$b['v']) . "\n";
$d = one($m, "SELECT COALESCE(SUM(CAST(entryDebitBalance AS DECIMAL(18,2)))
                            - SUM(CAST(entryCreditBalance AS DECIMAL(18,2))),0) t
              FROM entries WHERE deleted = 0");
echo "  partida doble global: " . mo($d['t']) . " (debe ser 0)\n";
