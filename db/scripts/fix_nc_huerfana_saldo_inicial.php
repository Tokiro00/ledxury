<?php
/**
 * Remate del saldo inicial MAM-Online: anula el asiento huérfano de la NC de
 * junio y compensa, para que contabilidad = facturas vivas.
 *
 * El asiento 11171 (supplier_return, DR 2205+auxMAM / CR 1435 por $510.852,97)
 * apunta al id del acta de devolución (1), no al de la factura NC, así que la
 * anulación por id de factura no lo alcanzó. Con la NC absorbida por el saldo
 * inicial, ese asiento dejaba el auxiliar MAM $510.852,97 por debajo de las
 * facturas. Anularlo sube 1435 por encima del físico, así que se compensa
 * DR 3705 / CR 1435 por el mismo valor (la misma bolsa del residuo E2).
 *
 * Ejecutar: php fix_nc_huerfana_saldo_inicial.php --apply   (sin flag: simula)
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

function one($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n"); exit(1); } return $r->fetch_assoc(); }
function run($m, $APPLY, $sql) {
    if (!$APPLY) { echo "  [sim] " . preg_replace('/\s+/', ' ', substr(trim($sql), 0, 150)) . "\n"; return; }
    if (!$m->query($sql)) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); $m->rollback(); exit(1); }
    echo "  [ok] filas: {$m->affected_rows}\n";
}

$chk = one($m, "SELECT deleted FROM entries WHERE entryID = 11171");
if (!$chk) { echo "ABORTA: no existe el asiento 11171.\n"; exit(1); }
if ((int)$chk['deleted'] === 1) { echo "ABORTA: el asiento 11171 ya está anulado (fix ya aplicado).\n"; exit(0); }

if ($APPLY) $m->begin_transaction();

run($m, $APPLY, "UPDATE entries SET deleted = 1 WHERE entryID = 11171 AND deleted = 0");
run($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryDate, entryStoreId, entryType,
    entryTransactionType, entryTransactionId, entryDebitAccount, entryDebitBalance,
    entryCreditAccount, entryCreditBalance, entryStatus, created_by, entryCreateDate, deleted)
  SELECT '71339095', 'Saldo inicial MAM-Online 01/08/2026 - compensacion por anulacion del asiento de la NC de junio (absorbida en el saldo inicial)',
    '2026-08-01', 1, 1, 'supplier_bill',
    (SELECT idSupplierInvoice FROM supplier_invoices WHERE invoiceNumber = 'SALDO-INICIAL-MAMONLINE-20260801'),
    45, 510852.97, 41, 510852.97, 1, '71339095', NOW(), 0");
run($m, $APPLY, "UPDATE subaccounts s SET
    s.accountDebit  = (SELECT COALESCE(SUM(e.entryDebitBalance),0)  FROM entries e WHERE e.entryDebitAccount  = s.id AND e.deleted = 0),
    s.accountCredit = (SELECT COALESCE(SUM(e.entryCreditBalance),0) FROM entries e WHERE e.entryCreditAccount = s.id AND e.deleted = 0)
    WHERE s.id IN (41,42,45)");
run($m, $APPLY, "UPDATE subaccounts SET accountBalance = CASE WHEN accountSide = '1' THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END WHERE id IN (41,42,45)");
run($m, $APPLY, "UPDATE auxiliary_subaccounts a SET a.accountBalance =
    (SELECT COALESCE(SUM(CASE WHEN e.entryCreditAuxaccount = a.id THEN e.entryCreditBalance ELSE 0 END),0) -
     COALESCE(SUM(CASE WHEN e.entryDebitAuxaccount  = a.id THEN e.entryDebitBalance  ELSE 0 END),0)
     FROM entries e WHERE e.deleted = 0) WHERE a.id = 5790");

if ($APPLY) {
    $m->commit();
    echo "=== VERIFICACION ===\n";
    $v = one($m, "SELECT (SELECT accountBalance FROM auxiliary_subaccounts WHERE id=5790) aux_mam,
        (SELECT accountBalance FROM subaccounts WHERE id=41) inv_1435,
        (SELECT SUM(balance) FROM supplier_invoices WHERE providerId='12' AND deleted=0) cxp_facturas,
        (SELECT SUM(entryDebitBalance)-SUM(entryCreditBalance) FROM entries WHERE deleted=0) partida_doble");
    echo "aux MAM:        " . number_format($v['aux_mam'], 2, ',', '.') . "  (esperado 131.791.567,00)\n";
    echo "1435 inventario:" . number_format($v['inv_1435'], 2, ',', '.') . "  (esperado 39.318.756,22)\n";
    echo "CxP facturas:   " . number_format($v['cxp_facturas'], 2, ',', '.') . "  (esperado 131.791.567,00)\n";
    echo "partida doble:  " . number_format($v['partida_doble'], 2, ',', '.') . "  (esperado 0)\n";
} else {
    echo "=== FIN SIMULACION ===\n";
}
