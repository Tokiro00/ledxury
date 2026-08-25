<?php
/**
 * Remate del saldo inicial en el server nuevo: el asiento de la devolución
 * NC-MAM (entry 11171, tipo supplier_return, ligado a mam_returns id 1 y no a
 * la factura NC id 4) sobrevivió al filtro del script. La NC está absorbida
 * por el SALDO-INICIAL, así que su asiento también se anula, y se compensa
 * 1435 para que quede en el físico actual (el E2 se calculó con él vivo).
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH','x'); define('ENVIRONMENT','production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n" : "=== SIMULACION ===\n";

function one($m,$sql){ $r=$m->query($sql); if(!$r){echo "SQL ERR: {$m->error}\n"; exit(1);} return $r->fetch_assoc(); }
function run($m,$APPLY,$sql){ if(!$APPLY){ echo "  [sim]\n"; return; }
  if(!$m->query($sql)){ echo "SQL ERR: {$m->error}\n"; $m->rollback(); exit(1); } echo "  [ok] filas: {$m->affected_rows}\n"; }

$e = one($m, "SELECT entryID, deleted, entryDebitBalance FROM entries WHERE entryID = 11171");
if (!$e) { echo "no existe 11171\n"; exit(1); }
if ((int)$e['deleted'] === 1) { echo "11171 ya está anulado. Nada que hacer.\n"; exit(0); }
$V = round((float)$e['entryDebitBalance'], 2);
echo "anulando entry 11171 (DR 42/aux5790, CR 41) por $V\n";

if ($APPLY) $m->begin_transaction();
run($m, $APPLY, "UPDATE entries SET deleted = 1 WHERE entryID = 11171");
// Compensación 1435: el E2 del saldo inicial se calculó con 11171 vivo; al
// anularlo, 1435 sube ese valor. Se baja de nuevo contra 3705 (conciliación).
$d = $m->real_escape_string('Saldo inicial MAM-Online 01/08/2026 - remate: anulacion del asiento de la devolucion NC-MAM (absorbida por el saldo inicial); compensacion para dejar 1435 en el fisico');
run($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryType,
    entryDebitAccount, entryDebitBalance, entryCreditAccount, entryCreditBalance,
    entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
    entryTransactionType, entryTransactionId, entryDate)
    VALUES ('71339095', '$d', 1, 45, '$V', 41, '$V', 1, '71339095', NOW(), 0, 1,
    'supplier_bill', 5, '2026-08-01')");
// resincronizar denormalizados
run($m, $APPLY, "UPDATE subaccounts s SET
    s.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAccount  = s.id AND e.deleted = 0),
    s.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAccount = s.id AND e.deleted = 0)
    WHERE s.id IN (41, 42, 45)");
run($m, $APPLY, "UPDATE subaccounts SET
    accountBalance = CASE WHEN accountSide = '1' THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END
    WHERE id IN (41, 42, 45)");
run($m, $APPLY, "UPDATE auxiliary_subaccounts a SET
    a.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAuxaccount  = a.id AND e.deleted = 0),
    a.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAuxaccount = a.id AND e.deleted = 0)
    WHERE a.id = 5790");
run($m, $APPLY, "UPDATE auxiliary_subaccounts SET accountBalance = accountCredit - accountDebit WHERE id = 5790");

if ($APPLY) {
    $m->commit();
    echo "=== VERIFICACION ===\n";
    $x = one($m, "SELECT accountBalance FROM auxiliary_subaccounts WHERE id = 5790");
    echo "aux MAM (debe ser 129.308.187): " . number_format($x['accountBalance'],2,',','.') . "\n";
    $x = one($m, "SELECT accountBalance FROM subaccounts WHERE id = 41");
    echo "1435 (debe ser 0): " . number_format($x['accountBalance'],2,',','.') . "\n";
    $x = one($m, "SELECT SUM(entryDebitBalance)-SUM(entryCreditBalance) d FROM entries WHERE deleted=0");
    echo "partida doble (debe ser 0): " . number_format($x['d'],2,',','.') . "\n";
}
