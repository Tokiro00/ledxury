<?php
/**
 * Compara el banco de tesorería (cash_movements + bank_accounts) contra el
 * banco contable (subcuenta 111005) mes por mes, para ubicar dónde se separan.
 *
 *   php conciliar_banco_tesoreria_vs_contable.php [idBankAccount]
 *
 * Solo lee. No escribe nada.
 *
 * Un movimiento de tesorería sin asiento (o al revés) mueve solo un lado. Los
 * ajustes de saldo son legítimamente de un solo lado: sirven para igualar el
 * extracto y no tienen contrapartida contable. Todo lo demás debería estar en
 * los dos.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');

$bankId = isset($argv[1]) ? (int)$argv[1] : 1;

// Fecha ancla opcional: el día en que tesorería y contabilidad quedaron iguales
// (el asiento de apertura las igualó el 09/07/2026 en $1.347.222,23). Pasándola,
// el script compara solo los deltas posteriores, que es la divergencia real.
$desde = isset($argv[2]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[2]) ? $argv[2] : null;
$fTes = $desde ? " AND DATE(c.movementDate) >= '{$desde}'" : '';
$fCon = $desde ? " AND e.entryDate >= '{$desde}'" : '';

function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n   {$sql}\n"; return array(); }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function money($v) { return number_format((float)$v, 2, ',', '.'); }

$b = rows($m, "SELECT bankName, accountNumber, subaccountId, initialBalance, currentBalance
               FROM bank_accounts WHERE idBankAccount = {$bankId}");
if (!$b) { echo "No existe la cuenta bancaria {$bankId}\n"; exit(1); }

// La subcuenta contable sale de la cuenta misma; si no la tiene, de accounting_settings.
$subId = (int)$b[0]['subaccountId'];
if (!$subId) {
    $x = rows($m, "SELECT subaccount_id FROM accounting_settings WHERE setting_key = 'account_bank'");
    $subId = $x ? (int)$x[0]['subaccount_id'] : 0;
}
if (!$subId) { echo "La cuenta no tiene subcuenta contable asociada\n"; exit(1); }
$s = rows($m, "SELECT pucCode, accountName, accountBalance FROM subaccounts WHERE id = {$subId}");
if (!$s) { echo "No existe la subcuenta {$subId}\n"; exit(1); }

echo "Cuenta bancaria {$bankId}: {$b[0]['bankName']} {$b[0]['accountNumber']}\n";
echo "  saldo inicial            " . str_pad(money($b[0]['initialBalance']), 18, ' ', STR_PAD_LEFT) . "\n";
echo "  tesorería (currentBalance)" . str_pad(money($b[0]['currentBalance']), 17, ' ', STR_PAD_LEFT) . "\n";
echo "Subcuenta contable {$subId} ({$s[0]['pucCode']} {$s[0]['accountName']})\n";
echo "  contable (débito - crédito)" . str_pad(money($s[0]['accountBalance']), 16, ' ', STR_PAD_LEFT) . "\n";
$brecha = (float)$s[0]['accountBalance'] - (float)$b[0]['currentBalance'];
echo "  BRECHA (contable - tesorería) " . str_pad(money($brecha), 13, ' ', STR_PAD_LEFT) . "\n\n";

// ── Tesorería por mes, con la fórmula del libro ─────────────────────────────
$tesSql = "
SELECT DATE_FORMAT(c.movementDate, '%Y-%m') mes,
       SUM(CASE
         WHEN c.movementType IN ('ingreso','apertura') AND c.sourceType='banco' AND c.sourceId={$bankId} THEN c.amount
         WHEN c.movementType IN ('egreso','cierre')    AND c.sourceType='banco' AND c.sourceId={$bankId} THEN -c.amount
         WHEN c.movementType='transferencia'           AND c.sourceType='banco' AND c.sourceId={$bankId} THEN -c.amount
         WHEN c.movementType='transferencia' AND c.destinationType='banco' AND c.destinationId={$bankId} THEN c.amount
         WHEN c.movementType='ajuste'                  AND c.sourceType='banco' AND c.sourceId={$bankId} THEN c.amount
         ELSE 0 END) neto,
       SUM(CASE WHEN c.movementType='ajuste' AND c.sourceType='banco' AND c.sourceId={$bankId} THEN c.amount ELSE 0 END) ajustes
FROM cash_movements c
WHERE c.deleted=0 AND c.status<>'anulado'{$fTes}
  AND ((c.sourceType='banco' AND c.sourceId={$bankId})
    OR (c.destinationType='banco' AND c.destinationId={$bankId} AND c.movementType='transferencia'))
GROUP BY mes ORDER BY mes";
$tes = array(); $ajus = array();
foreach (rows($m, $tesSql) as $r) { $tes[$r['mes']] = (float)$r['neto']; $ajus[$r['mes']] = (float)$r['ajustes']; }

// ── Contabilidad por mes ────────────────────────────────────────────────────
$conSql = "
SELECT DATE_FORMAT(e.entryDate, '%Y-%m') mes,
       SUM(CASE WHEN e.entryDebitAccount  = {$subId} THEN CAST(e.entryDebitBalance  AS DECIMAL(18,2)) ELSE 0 END)
     - SUM(CASE WHEN e.entryCreditAccount = {$subId} THEN CAST(e.entryCreditBalance AS DECIMAL(18,2)) ELSE 0 END) neto
FROM entries e
WHERE e.deleted = 0 AND (e.entryDebitAccount = {$subId} OR e.entryCreditAccount = {$subId}){$fCon}
GROUP BY mes ORDER BY mes";
$con = array();
foreach (rows($m, $conSql) as $r) $con[$r['mes']] = (float)$r['neto'];

$meses = array_unique(array_merge(array_keys($tes), array_keys($con)));
sort($meses);

printf("%-9s %18s %18s %18s %18s\n", 'mes', 'tesorería', 'contable', 'diferencia', 'de eso: ajustes');
echo str_repeat('─', 85) . "\n";
$acumT = 0; $acumC = 0;
foreach ($meses as $mes) {
    $t = isset($tes[$mes]) ? $tes[$mes] : 0;
    $k = isset($con[$mes]) ? $con[$mes] : 0;
    $a = isset($ajus[$mes]) ? $ajus[$mes] : 0;
    $acumT += $t; $acumC += $k;
    $marca = abs($k - $t) > 0.01 ? '  <<<' : '';
    printf("%-9s %18s %18s %18s %18s%s\n", $mes, money($t), money($k), money($k - $t), money($a), $marca);
}
echo str_repeat('─', 85) . "\n";
printf("%-9s %18s %18s %18s\n", 'TOTAL', money($acumT), money($acumC), money($acumC - $acumT));
if ($desde) {
    echo "\nDeltas desde {$desde}. Si en esa fecha las dos partes estaban iguales, la\n";
    echo "columna 'diferencia' del TOTAL es toda la divergencia que hay que explicar.\n";
} else {
    echo "\n(la tesorería arranca del saldo inicial " . money($b[0]['initialBalance']) . ", la contabilidad de cero)\n";
}

// ── Detalle de los meses que no cuadran ─────────────────────────────────────
echo "\n=== DETALLE de los meses descuadrados ===\n";
foreach ($meses as $mes) {
    $t = isset($tes[$mes]) ? $tes[$mes] : 0;
    $k = isset($con[$mes]) ? $con[$mes] : 0;
    if (abs($k - $t) <= 0.01) continue;
    echo "\n── {$mes}  (contable − tesorería = " . money($k - $t) . ")\n";

    echo "   tesorería:\n";
    foreach (rows($m, "
        SELECT c.idMovement, DATE(c.movementDate) f, c.movementType, c.amount,
               c.category, c.documentNumber, LEFT(c.concept, 62) concepto
        FROM cash_movements c
        WHERE c.deleted=0 AND c.status<>'anulado'{$fTes}
          AND DATE_FORMAT(c.movementDate,'%Y-%m') = '{$mes}'
          AND ((c.sourceType='banco' AND c.sourceId={$bankId})
            OR (c.destinationType='banco' AND c.destinationId={$bankId} AND c.movementType='transferencia'))
        ORDER BY c.movementDate, c.idMovement") as $r)
        printf("     %-5s %s %-14s %16s  %-14s %s\n", $r['idMovement'], $r['f'], $r['movementType'],
            money($r['amount']), $r['category'], $r['concepto']);

    echo "   contabilidad:\n";
    foreach (rows($m, "
        SELECT e.entryID, e.entryDate f, e.entryTransactionType tipo, e.entryTransactionId tid,
               CASE WHEN e.entryDebitAccount = {$subId} THEN 'DR' ELSE 'CR' END lado,
               CASE WHEN e.entryDebitAccount = {$subId} THEN CAST(e.entryDebitBalance AS DECIMAL(18,2))
                    ELSE CAST(e.entryCreditBalance AS DECIMAL(18,2)) END val,
               LEFT(e.entryDescription, 62) descripcion
        FROM entries e
        WHERE e.deleted = 0 AND (e.entryDebitAccount = {$subId} OR e.entryCreditAccount = {$subId}){$fCon}
          AND DATE_FORMAT(e.entryDate,'%Y-%m') = '{$mes}'
        ORDER BY e.entryDate, e.entryID") as $r)
        printf("     %-6s %s %-2s %16s  %-22s %s\n", $r['entryID'], $r['f'], $r['lado'],
            money($r['val']), $r['tipo'] . ($r['tid'] ? "#{$r['tid']}" : ''), $r['descripcion']);
}
