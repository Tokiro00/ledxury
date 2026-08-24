<?php
/**
 * SALDOS DE VENDEDORES A CERO — 24/08/2026.
 *
 * Alex confirma que la semana pasada se pagaron TODAS las deudas con los
 * vendedores/operadores de bot (los pagos reales salieron del banco real en
 * la instancia perdida; el saldo bancario que se cargue en la apertura ya
 * los refleja). Aquí solo se refleja en libros: cada auxiliar de 233525
 * (Comisiones bots por pagar) con saldo se lleva a cero contra 370501
 * (utilidades acumuladas), con fecha de hoy 24/08/2026.
 *
 * Con los siguientes reportes de Interrapidísimo las comisiones se vuelven a
 * causar desde cero.
 *
 *   php vendedores_a_cero_20260824.php            (simulación)
 *   php vendedores_a_cero_20260824.php --apply
 *
 * Idempotente: aborta si ya hay asientos tipo 'ajuste_vendedores_20260824'.
 * Recalcula los campos denormalizados del aux (accountDebit/accountCredit),
 * que son lo que lee la pantalla de Liquidaciones.
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

$FECHA = '2026-08-24';
$TIPO  = 'ajuste_vendedores_20260824';
$USER  = '71339095';

function rows($m, $sql) { $r = $m->query($sql); if (!$r) { echo "SQL ERR: {$m->error}\n"; exit(1); }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o; }
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function run($m, $APPLY, $sql) {
    if (!$APPLY) return true;
    if (!$m->query($sql)) { echo "SQL ERR: {$m->error}\n"; $m->rollback(); exit(1); }
    return true;
}
function mo($v) { return '$' . number_format((float)$v, 2, ',', '.'); }

// Cuentas por código (no por id, por si difieren en este servidor)
$comis = one($m, "SELECT id FROM subaccounts WHERE pucCode = '233525' AND deleted = 0");
$patri = one($m, "SELECT id FROM subaccounts WHERE pucCode = '370501' AND deleted = 0");
if (!$comis || !$patri) { echo "ABORTA: no encuentro 233525 o 370501.\n"; exit(1); }
$ACC_COMIS = (int)$comis['id'];
$ACC_PATRI = (int)$patri['id'];
echo "233525 id={$ACC_COMIS} · 370501 id={$ACC_PATRI}\n\n";

$ya = one($m, "SELECT COUNT(*) n FROM entries WHERE deleted = 0 AND entryTransactionType = '$TIPO'");
if ((int)$ya['n'] > 0) { echo "Ya existen {$ya['n']} asientos '$TIPO'. No se repite.\n"; exit(0); }

// Saldo REAL por auxiliar desde los asientos (crédito − débito)
$auxs = rows($m, "
    SELECT a.id, a.accountName, a.accountAccount,
           COALESCE(SUM(CASE WHEN e.entryCreditAuxaccount = a.id THEN e.entryCreditBalance ELSE 0 END),0) -
           COALESCE(SUM(CASE WHEN e.entryDebitAuxaccount  = a.id THEN e.entryDebitBalance  ELSE 0 END),0) AS saldo
    FROM auxiliary_subaccounts a
    LEFT JOIN entries e ON (e.entryCreditAuxaccount = a.id OR e.entryDebitAuxaccount = a.id) AND e.deleted = 0
    WHERE a.accountType = 'bot_commission' AND a.deleted = 0
    GROUP BY a.id, a.accountName, a.accountAccount
    HAVING ABS(saldo) >= 0.01
    ORDER BY a.accountName");

if (!$auxs) { echo "No hay auxiliares de comisión con saldo. Nada que hacer.\n"; exit(0); }

if ($APPLY) $m->begin_transaction();
$total = 0; $ids = array();
foreach ($auxs as $a) {
    $s = round((float)$a['saldo'], 2);
    $ids[] = (int)$a['id'];
    $desc = "Saldo a cero {$a['accountName']}: comisiones pagadas la semana pasada por fuera del sistema (instancia perdida); el saldo bancario de la apertura ya refleja esos pagos. Al 24/08/2026 no se debe nada; las nuevas comisiones se causan con los proximos reportes de Interrapidisimo.";
    $d = $m->real_escape_string($desc);
    if ($s > 0) {
        // pasivo con saldo → se cancela: DR 233525/aux, CR 370501
        printf("  %-30s saldo %14s  ->  DR 233525/aux %d / CR 370501\n", $a['accountName'], mo($s), $a['id']);
        run($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryType,
            entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
            entryCreditAccount, entryCreditBalance,
            entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
            entryTransactionType, entryTransactionId, entryDate)
            VALUES ('$USER', '$d', 1, $ACC_COMIS, {$a['id']}, " . number_format($s, 2, '.', '') . ",
                    $ACC_PATRI, " . number_format($s, 2, '.', '') . ",
                    1, '$USER', NOW(), 0, 1, '$TIPO', 0, '$FECHA')");
    } else {
        // saldo negativo (pagado de más) → al revés: DR 370501, CR 233525/aux
        $v = abs($s);
        printf("  %-30s saldo %14s  ->  DR 370501 / CR 233525/aux %d\n", $a['accountName'], mo($s), $a['id']);
        run($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryType,
            entryDebitAccount, entryDebitBalance,
            entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
            entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
            entryTransactionType, entryTransactionId, entryDate)
            VALUES ('$USER', '$d', 1, $ACC_PATRI, " . number_format($v, 2, '.', '') . ",
                    $ACC_COMIS, {$a['id']}, " . number_format($v, 2, '.', '') . ",
                    1, '$USER', NOW(), 0, 1, '$TIPO', 0, '$FECHA')");
    }
    $total += $s;
}
printf("\nTotal llevado a cero: %s (efecto en patrimonio: %s)\n", mo($total), mo($total));

// Recalcular denormalizados: la pantalla de Liquidaciones lee accountCredit/accountDebit del aux.
$lista = implode(',', $ids);
run($m, $APPLY, "UPDATE auxiliary_subaccounts a SET
    a.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAuxaccount  = a.id AND e.deleted = 0),
    a.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAuxaccount = a.id AND e.deleted = 0)
    WHERE a.id IN ($lista)");
run($m, $APPLY, "UPDATE auxiliary_subaccounts SET accountBalance = accountCredit - accountDebit WHERE id IN ($lista)");
// y la subcuenta 233525 + 370501
run($m, $APPLY, "UPDATE subaccounts s SET
    s.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAccount  = s.id AND e.deleted = 0),
    s.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAccount = s.id AND e.deleted = 0)
    WHERE s.id IN ($ACC_COMIS, $ACC_PATRI)");
run($m, $APPLY, "UPDATE subaccounts SET
    accountBalance = CASE WHEN accountSide = '1' THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
    WHERE id IN ($ACC_COMIS, $ACC_PATRI)");

if ($APPLY) {
    $m->commit();
    echo "\n=== VERIFICACION ===\n";
    foreach (rows($m, "SELECT accountName, accountCredit - accountDebit s FROM auxiliary_subaccounts WHERE id IN ($lista)") as $r)
        printf("  %-30s saldo: %s (debe ser 0)\n", $r['accountName'], mo($r['s']));
    $v = one($m, "SELECT SUM(entryDebitBalance) - SUM(entryCreditBalance) d FROM entries WHERE deleted = 0");
    echo "  partida doble global: " . mo($v['d']) . " (debe ser 0)\n";
} else {
    echo "\n=== FIN SIMULACION — nada se escribió ===\n";
}
