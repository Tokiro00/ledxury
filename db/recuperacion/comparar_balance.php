<?php
/**
 * COMPARACIÓN 24/08/2026 — "el balance que teníamos" contra el sistema actual.
 *
 * Teníamos = balance de apertura del 20/08 (cierre_apertura_20260820.php,
 * verificado A = P + Pat con diferencia 0,00) más los dos ajustes del 22/08
 * (banco a $5.081.154,27 y Germam Maria a cero). Son las últimas cifras
 * confirmadas antes de perder la instancia.
 *
 * Actual = el servidor nuevo: respaldo del 18/06 + los contrapagos/cortes
 * recuperados hoy (que no tocan contabilidad). La capa contable de hoy sigue
 * siendo la de los libros viejos porque el cierre del 20/08 aún no se
 * re-ejecuta aquí.
 *
 * Solo lee. No escribe nada.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');

function one($m, $sql) { $r = $m->query($sql);
    if ($r === false) { echo "SQL ERROR: {$m->error}\n"; return null; }
    return $r->fetch_assoc(); }
function mo($v) { return number_format((float)$v, 2, ',', '.'); }

// Neto contable (débito − crédito) por código PUC, presentado por naturaleza.
function netoPuc($m, $puc, $naturalezaCredito = false) {
    $r = one($m, "
        SELECT COALESCE(SUM(CASE WHEN e.entryDebitAccount = s.id THEN CAST(e.entryDebitBalance AS DECIMAL(18,2)) ELSE 0 END),0)
             - COALESCE(SUM(CASE WHEN e.entryCreditAccount = s.id THEN CAST(e.entryCreditBalance AS DECIMAL(18,2)) ELSE 0 END),0) AS neto
        FROM subaccounts s
        JOIN entries e ON (e.entryDebitAccount = s.id OR e.entryCreditAccount = s.id) AND e.deleted = 0
        WHERE s.deleted = 0 AND s.pucCode = '$puc'");
    $n = $r ? (float)$r['neto'] : 0;
    return $naturalezaCredito ? -$n : $n;
}

// ── Actual: capa operativa ──────────────────────────────────────────────────
function tesoreria($m, $tipo, $id) {
    $tabla = $tipo === 'caja' ? 'cashboxes' : 'bank_accounts';
    $pk    = $tipo === 'caja' ? 'idCashbox' : 'idBankAccount';
    $r = one($m, "
        SELECT t.initialBalance + COALESCE((
            SELECT SUM(CASE
                WHEN cm.movementType IN ('ingreso','apertura') AND cm.sourceType='$tipo' AND cm.sourceId = t.$pk THEN cm.amount
                WHEN cm.movementType IN ('egreso','cierre')    AND cm.sourceType='$tipo' AND cm.sourceId = t.$pk THEN -cm.amount
                WHEN cm.movementType = 'transferencia'         AND cm.sourceType='$tipo' AND cm.sourceId = t.$pk THEN -cm.amount
                WHEN cm.movementType = 'transferencia'         AND cm.destinationType='$tipo' AND cm.destinationId = t.$pk THEN cm.amount
                WHEN cm.movementType = 'ajuste'                AND cm.sourceType='$tipo' AND cm.sourceId = t.$pk THEN cm.amount
                ELSE 0 END)
            FROM cash_movements cm
            WHERE cm.deleted = 0 AND cm.status != 'anulado'
              AND ( (cm.sourceType='$tipo' AND cm.sourceId = t.$pk)
                 OR (cm.destinationType='$tipo' AND cm.destinationId = t.$pk AND cm.movementType='transferencia') )
        ), 0) AS saldo
        FROM $tabla t WHERE t.$pk = $id");
    return $r ? (float)$r['saldo'] : 0;
}

$cart = one($m, "
    SELECT COALESCE(SUM(i.total - COALESCE(p.pagado,0)),0) saldo, COUNT(*) n
    FROM invoices i
    LEFT JOIN (SELECT invoiceId, SUM(payment) pagado FROM payments WHERE deleted=0 GROUP BY invoiceId) p
           ON p.invoiceId = i.idInvoice
    WHERE i.state = 0 AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)");

$inv = one($m, "
    SELECT COALESCE(SUM(iv.stock * COALESCE(NULLIF(p.cost_cop,0), NULLIF(p.cost,0), 0)),0) valor,
           COUNT(*) refs, COALESCE(SUM(iv.stock),0) unidades
    FROM inventory iv JOIN products p ON p.idProduct = iv.idProduct
    WHERE iv.stock > 0 AND (p.deleted IS NULL OR p.deleted = 0)");

$prov = one($m, "SELECT COALESCE(SUM(balance),0) s, COUNT(*) n FROM supplier_invoices
                 WHERE deleted = 0 AND balance <> 0");

// ── Teníamos (apertura 20/08 + ajustes 22/08) ───────────────────────────────
$T = array(
    '110505 Caja'                  => array(115000.00,      tesoreria($m,'caja',1),  'tesorería caja 1'),
    '111005 Banco'                 => array(5081154.27,     tesoreria($m,'banco',1), 'tesorería banco 1'),
    '130505 Cartera clientes'      => array(16565810.00,    (float)$cart['saldo'],   $cart['n'] . ' facturas abiertas'),
    '132505 CxC vinculados'        => array(0.00,           netoPuc($m,'132505'),    'contable'),
    '136525 Anticipos vendedores'  => array(0.00,           netoPuc($m,'136525'),    'contable'),
    '143501 Inventario'            => array(41568451.93,    (float)$inv['valor'],    $inv['refs'] . ' refs, ' . number_format((float)$inv['unidades'],0,',','.') . ' und'),
    '220505 Proveedores (MAM)'     => array(131791567.00,   (float)$prov['s'],       $prov['n'] . ' fact. proveedor con saldo'),
    '223005 CxP vinculadas'        => array(0.00,           netoPuc($m,'223005', true), 'contable'),
    '233525 Comisiones bots'       => array(1445100.00,     netoPuc($m,'233525', true), 'contable (2.488.518 − 1.043.418 Germam)'),
);

echo "BALANCE QUE TENÍAMOS (22/08, tras apertura y ajustes)  vs  SISTEMA ACTUAL (respaldo 18/06 + recuperado)\n";
echo str_repeat('─', 110) . "\n";
printf("%-30s %18s %18s %18s   %s\n", 'Cuenta', 'Teníamos 22/08', 'Actual', 'Diferencia', 'Fuente actual');
echo str_repeat('─', 110) . "\n";
foreach ($T as $nombre => $x) {
    printf("%-30s %18s %18s %18s   %s\n", $nombre, mo($x[0]), mo($x[1]), mo($x[1] - $x[0]), $x[2]);
}
echo str_repeat('─', 110) . "\n";

// Referencia: qué diría el cierre si se corriera HOY con los datos actuales
echo "\nSi el cierre/apertura se re-corriera HOY, calcularía:\n";
printf("  Cartera:    %18s  (original 20/08: 16.565.810,00 -> faltan facturas/pagos del 19/06 al 20/08)\n", mo($cart['saldo']));
printf("  Inventario: %18s  (original 20/08: 41.568.451,93 -> faltan movimientos de stock del 19/06 al 20/08)\n", mo($inv['valor']));

// Estado contable crudo de caja/banco hoy (libros viejos, pre-cierre)
printf("\nContable hoy (libros viejos, el cierre aún no corre aquí): 110505 = %s | 111005 = %s | 130505 = %s\n",
    mo(netoPuc($m,'110505')), mo(netoPuc($m,'111005')), mo(netoPuc($m,'130505')));

// Contrapagos recuperados hoy (solo informativo)
$cp = one($m, "SELECT COUNT(*) lotes, COALESCE(SUM(total_valor),0) v FROM contrapago_batches WHERE fecha_pago >= '2026-06-19'");
printf("\nRecuperado hoy en contrapagos: %d lotes por %s (operativo, sin efecto contable: son anteriores a la apertura).\n",
    $cp['lotes'], mo($cp['v']));
