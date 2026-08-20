<?php
/**
 * Balance completo a hoy y propuesta de cierre/apertura.
 *
 *   php balance_propuesto_apertura.php [saldo_banco] [saldo_caja]
 *
 * SOLO LEE. No escribe nada, nunca. Sirve para revisar cuenta por cuenta antes
 * de decidir el asiento de cierre.
 *
 * Muestra:
 *   1. Balance por clase (activo, pasivo, patrimonio) y comprobación de que
 *      activo = pasivo + patrimonio.
 *   2. Estado de resultados del año: lo que se cerraría contra patrimonio.
 *   3. Detalle de cartera por cliente.
 *   4. Detalle de cuentas por pagar por proveedor (auxiliares).
 *   5. Detalle de comisiones de bot por pagar.
 *   6. Inventario: valor contable contra valor físico de productos.
 *   7. Los ajustes de tesorería que harían falta para llegar a las cifras
 *      que se pasen por parámetro.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');

$OBJ_BANCO = isset($argv[1]) ? (float)$argv[1] : null;
$OBJ_CAJA  = isset($argv[2]) ? (float)$argv[2] : null;

function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return array(); }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function mo($v) { return number_format((float)$v, 2, ',', '.'); }
function t($x) { echo "\n" . str_repeat('=', 104) . "\n{$x}\n" . str_repeat('=', 104) . "\n"; }

// Saldo de cada subcuenta calculado desde los asientos, no desde la columna.
$SALDO = "(SELECT COALESCE(SUM(CAST(e.entryDebitBalance AS DECIMAL(18,2))),0)
           FROM entries e WHERE e.deleted=0 AND e.entryDebitAccount = s.id)
        - (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0)
           FROM entries e WHERE e.deleted=0 AND e.entryCreditAccount = s.id)";

t('1. BALANCE A HOY (saldos calculados desde los asientos)');
$clases = array(
    '1' => 'ACTIVO', '2' => 'PASIVO', '3' => 'PATRIMONIO',
    '4' => 'INGRESOS', '5' => 'GASTOS', '6' => 'COSTOS',
);
$totClase = array();
foreach ($clases as $pref => $nombre) {
    $cuentas = rows($m, "SELECT s.id, s.pucCode, s.accountName, s.accountSide, ({$SALDO}) neto
                         FROM subaccounts s
                         WHERE s.deleted = 0 AND s.pucCode LIKE '{$pref}%'
                         ORDER BY s.pucCode");
    $sub = 0; $lineas = array();
    foreach ($cuentas as $x) {
        // Presentación: el activo y el gasto son naturaleza débito; el pasivo,
        // el patrimonio y el ingreso, naturaleza crédito.
        $val = in_array($pref, array('1','5','6')) ? (float)$x['neto'] : -(float)$x['neto'];
        if (abs($val) < 0.005) continue;
        $sub += $val;
        $lineas[] = sprintf("  id %-4s %-9s %-50s %18s", $x['id'], $x['pucCode'],
            substr($x['accountName'], 0, 50), mo($val));
    }
    if (!$lineas) continue;
    echo "\n{$nombre}\n" . implode("\n", $lineas) . "\n";
    printf("  %-66s %18s\n", "TOTAL {$nombre}", mo($sub));
    $totClase[$pref] = $sub;
}

$activo     = isset($totClase['1']) ? $totClase['1'] : 0;
$pasivo     = isset($totClase['2']) ? $totClase['2'] : 0;
$patrimonio = isset($totClase['3']) ? $totClase['3'] : 0;
$ingresos   = isset($totClase['4']) ? $totClase['4'] : 0;
$gastos     = isset($totClase['5']) ? $totClase['5'] : 0;
$costos     = isset($totClase['6']) ? $totClase['6'] : 0;
$resultado  = $ingresos - $gastos - $costos;

t('2. RESULTADO DEL EJERCICIO — esto es lo que se cerraría contra patrimonio');
printf("  Ingresos                          %18s\n", mo($ingresos));
printf("  menos Costos                      %18s\n", mo($costos));
printf("  menos Gastos                      %18s\n", mo($gastos));
printf("  %-33s %18s\n", 'RESULTADO', mo($resultado));
echo "\n  Comprobación de la ecuación contable:\n";
printf("    Activo                          %18s\n", mo($activo));
printf("    Pasivo                          %18s\n", mo($pasivo));
printf("    Patrimonio (sin resultado)      %18s\n", mo($patrimonio));
printf("    Resultado del ejercicio         %18s\n", mo($resultado));
$desc = $activo - ($pasivo + $patrimonio + $resultado);
printf("    %-31s %18s   %s\n", 'Activo - (Pasivo+Patrim+Result)', mo($desc),
    abs($desc) < 0.01 ? 'cuadra' : '<<< REVISAR');

t('3. CARTERA POR CLIENTE (130505) — lo que nos deben');
$cart = rows($m, "
    SELECT i.clientId, cl.name cliente,
           COUNT(*) n,
           COALESCE(SUM(i.total),0) facturado,
           COALESCE(SUM(p.pagado),0) pagado,
           COALESCE(SUM(i.total),0) - COALESCE(SUM(p.pagado),0) saldo
    FROM invoices i
    LEFT JOIN clients cl ON cl.idClient = i.clientId
    LEFT JOIN (SELECT invoiceId, SUM(payment) pagado FROM payments
               WHERE deleted = 0 GROUP BY invoiceId) p ON p.invoiceId = i.idInvoice
    WHERE i.state = 0 AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)
    GROUP BY i.clientId
    HAVING saldo > 0.01
    ORDER BY saldo DESC");
$tc = 0;
foreach ($cart as $x) { $tc += (float)$x['saldo'];
    printf("  %-40s %3s fact  facturado %14s  pagado %13s  saldo %14s\n",
        substr((string)$x['cliente'], 0, 40), $x['n'], mo($x['facturado']), mo($x['pagado']), mo($x['saldo'])); }
printf("  %-52s %-21s saldo %14s\n", 'TOTAL cartera operativa', '', mo($tc));
$cont = one($m, "SELECT ({$SALDO}) neto FROM subaccounts s WHERE s.pucCode = '130505' AND s.deleted = 0");
echo "  Cartera contable (130505): " . mo($cont ? $cont['neto'] : 0) . "\n";
if ($cont) printf("  Diferencia operativa vs contable: %s\n", mo($tc - (float)$cont['neto']));

t('4. CUENTAS POR PAGAR POR PROVEEDOR (auxiliares de 220505 y 223005)');
foreach (rows($m, "SELECT a.id, a.accountID, a.accountName, a.accountType,
                          a.accountDebit deb, a.accountCredit cre,
                          (a.accountCredit - a.accountDebit) saldo
                   FROM auxiliary_subaccounts a
                   WHERE a.deleted = 0 AND a.accountType IN ('provider','intercompany')
                   ORDER BY saldo DESC") as $x)
    printf("  aux %-6s %-8s %-40s debito %14s credito %14s saldo %14s\n", $x['id'], $x['accountID'],
        substr((string)$x['accountName'], 0, 40), mo($x['deb']), mo($x['cre']), mo($x['saldo']));
echo "\n  Facturas de proveedor abiertas (supplier_invoices):\n";
foreach (rows($m, "SELECT p.name proveedor, si.status, COUNT(*) n,
                          COALESCE(SUM(si.total),0) total,
                          COALESCE(SUM(si.paidAmount),0) pagado,
                          COALESCE(SUM(si.balance),0) saldo
                   FROM supplier_invoices si
                   LEFT JOIN providers p ON p.idProvider = si.providerId
                   WHERE (si.deleted IS NULL OR si.deleted = 0)
                   GROUP BY si.providerId, si.status ORDER BY saldo DESC") as $x)
    printf("    %-28s %-12s %4s fact  total %14s pagado %14s saldo %14s\n",
        substr((string)$x['proveedor'], 0, 28), $x['status'], $x['n'],
        mo($x['total']), mo($x['pagado']), mo($x['saldo']));

t('5. COMISIONES DE BOT POR PAGAR (auxiliares de 233525)');
$tb = 0;
foreach (rows($m, "SELECT a.accountAccount uid, u.name, a.accountCredit gen, a.accountDebit pag,
                          (a.accountCredit - a.accountDebit) saldo
                   FROM auxiliary_subaccounts a LEFT JOIN users u ON u.idUser = a.accountAccount
                   WHERE a.accountType = 'bot_commission' AND a.deleted = 0
                   ORDER BY saldo DESC") as $x) {
    $tb += (float)$x['saldo'];
    printf("  %-12s %-32s generada %14s pagada %14s pendiente %14s\n", $x['uid'],
        substr((string)$x['name'], 0, 32), mo($x['gen']), mo($x['pag']), mo($x['saldo']));
}
printf("  %-46s %-30s TOTAL %14s\n", '', '', mo($tb));

t('6. INVENTARIO — contable contra fisico');
$inv = one($m, "SELECT ({$SALDO}) neto FROM subaccounts s WHERE s.pucCode = '143501' AND s.deleted = 0");
echo "  Inventario contable (143501): " . mo($inv ? $inv['neto'] : 0) . "\n";
// El stock vive en `inventory` (idStore, idProduct, stock); el costo real es
// cost_cop y products.cost es legado casi vacío.
echo "\n  Por bodega:\n";
$tf = 0;
foreach (rows($m, "SELECT iv.idStore, st.name bodega, COUNT(*) refs,
                          COALESCE(SUM(iv.stock),0) unidades,
                          COALESCE(SUM(iv.stock * COALESCE(NULLIF(p.cost_cop,0), NULLIF(p.cost,0), 0)),0) valor
                   FROM inventory iv
                   JOIN products p ON p.idProduct = iv.idProduct
                   LEFT JOIN stores st ON st.idStore = iv.idStore
                   WHERE iv.stock > 0 AND (p.deleted IS NULL OR p.deleted = 0)
                   GROUP BY iv.idStore ORDER BY valor DESC") as $x) {
    $tf += (float)$x['valor'];
    printf("    bodega %-3s %-24s %5s refs  %10s unidades  valor %16s\n", $x['idStore'],
        substr((string)$x['bodega'], 0, 24), $x['refs'],
        number_format((float)$x['unidades'], 0, ',', '.'), mo($x['valor']));
}
printf("    %-40s valor total %16s\n", 'TOTAL fisico', mo($tf));
echo "  Diferencia contable - fisico: " . mo((float)($inv ? $inv['neto'] : 0) - $tf) . "\n";
$sinCosto = one($m, "SELECT COUNT(*) n, COALESCE(SUM(iv.stock),0) u
                     FROM inventory iv JOIN products p ON p.idProduct = iv.idProduct
                     WHERE iv.stock > 0 AND (p.deleted IS NULL OR p.deleted = 0)
                       AND COALESCE(NULLIF(p.cost_cop,0), NULLIF(p.cost,0), 0) = 0");
if ($sinCosto && (int)$sinCosto['n'] > 0)
    echo "  OJO: {$sinCosto['n']} referencias con stock y SIN costo ("
       . number_format((float)$sinCosto['u'], 0, ',', '.') . " unidades) — valen 0 en el cálculo\n";

t('7. AJUSTES DE TESORERIA PARA LLEGAR A LAS CIFRAS PEDIDAS');
$b = one($m, "SELECT currentBalance cb FROM bank_accounts WHERE idBankAccount = 1");
$sb = one($m, "SELECT ({$SALDO}) neto FROM subaccounts s WHERE s.pucCode = '111005' AND s.deleted = 0");
$k = one($m, "SELECT currentBalance cb FROM cashboxes WHERE idCashbox = 1");
$sk = one($m, "SELECT ({$SALDO}) neto FROM subaccounts s WHERE s.pucCode = '110505' AND s.deleted = 0");
printf("  Banco    tesoreria %16s   contable %16s\n", mo($b['cb']), mo($sb['neto']));
printf("  Caja     tesoreria %16s   contable %16s\n", mo($k['cb']), mo($sk['neto']));
if ($OBJ_BANCO !== null) {
    printf("\n  Banco objetivo %20s   ajuste %16s\n", mo($OBJ_BANCO), mo($OBJ_BANCO - (float)$sb['neto']));
}
if ($OBJ_CAJA !== null) {
    printf("  Caja  objetivo %20s   ajuste %16s\n", mo($OBJ_CAJA), mo($OBJ_CAJA - (float)$sk['neto']));
}
if ($OBJ_BANCO !== null && $OBJ_CAJA !== null) {
    $aj = ($OBJ_BANCO - (float)$sb['neto']) + ($OBJ_CAJA - (float)$sk['neto']);
    printf("\n  Ajuste total contra patrimonio: %s\n", mo($aj));
    printf("  Patrimonio despues del cierre y del ajuste: %s\n", mo($patrimonio + $resultado + $aj));
}

t('8. PERIODOS CONTABLES CERRADOS (bloquean asientos)');
$tp = rows($m, "SHOW TABLES LIKE 'accounting_periods'");
if (!$tp) echo "  (no existe la tabla accounting_periods)\n";
else foreach (rows($m, "SELECT * FROM accounting_periods ORDER BY 1 DESC LIMIT 12") as $x) {
    $l = array(); foreach ($x as $kk => $vv) if ($vv !== null && $vv !== '') $l[] = "{$kk}={$vv}";
    echo "  " . implode('  ', $l) . "\n";
}
