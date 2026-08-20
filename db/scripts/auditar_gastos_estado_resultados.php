<?php
/**
 * Radiografía de los gastos que alimentan el estado de resultados.
 *
 *   php auditar_gastos_estado_resultados.php
 *
 * Solo lee. Muestra:
 *   1. Plan de cuentas de gasto/costo (clases 5 y 6) con saldo y movimiento.
 *   2. expense_categories y a qué subcuenta apunta cada una.
 *   3. En qué cuenta cae cada tipo de asiento (para ver mezclas).
 *   4. Fletes de Interrapidísimo: qué se lleva Ledxury como gasto y qué debería
 *      ser cuenta por cobrar a MAM / MAM-Online.
 *   5. Clasificación por compañía de las facturas de la transportadora.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');

function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return array(); }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function mo($v) { return number_format((float)$v, 2, ',', '.'); }
function titulo($t) { echo "\n" . str_repeat('=', 100) . "\n{$t}\n" . str_repeat('=', 100) . "\n"; }

titulo('1. CUENTAS DE GASTO Y COSTO (clases 5 y 6) — con movimiento del año');
foreach (rows($m, "
    SELECT s.id, s.pucCode, s.accountName, s.accountSide, s.accountBalance,
           (SELECT COUNT(*) FROM entries e WHERE e.deleted=0
              AND (e.entryDebitAccount = s.id OR e.entryCreditAccount = s.id)) n_mov,
           (SELECT COALESCE(SUM(CAST(e.entryDebitBalance AS DECIMAL(18,2))),0) FROM entries e
              WHERE e.deleted=0 AND e.entryDebitAccount = s.id) deb,
           (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e
              WHERE e.deleted=0 AND e.entryCreditAccount = s.id) cre
    FROM subaccounts s
    WHERE s.deleted = 0 AND (s.pucCode LIKE '5%' OR s.pucCode LIKE '6%')
    ORDER BY s.pucCode") as $x) {
    $marca = ((int)$x['n_mov'] === 0) ? '   (sin movimiento)' : '';
    printf("  id %-4s %-9s %-48s deb %16s  cre %16s  saldo %16s%s\n",
        $x['id'], $x['pucCode'], substr($x['accountName'], 0, 48),
        mo($x['deb']), mo($x['cre']), mo($x['accountBalance']), $marca);
}

titulo('2. CATEGORIAS DE GASTO -> SUBCUENTA, con lo registrado en cada una');
foreach (rows($m, "
    SELECT ec.id, ec.code, ec.name, ec.is_active, ec.accounting_subaccount_id sid,
           s.pucCode, s.accountName,
           (SELECT COUNT(*) FROM expense_records r WHERE r.expense_category_id = ec.id AND r.deleted = 0) n,
           (SELECT COALESCE(SUM(r.amount),0) FROM expense_records r WHERE r.expense_category_id = ec.id AND r.deleted = 0) t
    FROM expense_categories ec
    LEFT JOIN subaccounts s ON s.id = ec.accounting_subaccount_id
    WHERE ec.deleted = 0 ORDER BY ec.id") as $x) {
    $act = $x['is_active'] ? '' : '  (inactiva)';
    printf("  cat %-3s %-9s %-30s -> %-9s %-42s  %2s gasto(s) %16s%s\n",
        $x['id'], $x['code'], substr($x['name'], 0, 30), $x['pucCode'],
        substr((string)$x['accountName'], 0, 42), $x['n'], mo($x['t']), $act);
}

titulo('3. QUE CUENTA USA CADA TIPO DE ASIENTO (para detectar mezclas)');
foreach (rows($m, "
    SELECT e.entryTransactionType t,
           sd.pucCode dr_puc, sd.accountName dr_name,
           sc.pucCode cr_puc, sc.accountName cr_name,
           COUNT(*) n, COALESCE(SUM(CAST(e.entryDebitBalance AS DECIMAL(18,2))),0) total
    FROM entries e
    LEFT JOIN subaccounts sd ON sd.id = e.entryDebitAccount
    LEFT JOIN subaccounts sc ON sc.id = e.entryCreditAccount
    WHERE e.deleted = 0
      AND (sd.pucCode LIKE '5%' OR sd.pucCode LIKE '6%' OR sc.pucCode LIKE '5%' OR sc.pucCode LIKE '6%')
    GROUP BY e.entryTransactionType, e.entryDebitAccount, e.entryCreditAccount
    ORDER BY e.entryTransactionType, total DESC") as $x) {
    printf("  %-26s DR %-9s %-32s CR %-9s %-32s %3s  %16s\n",
        substr((string)$x['t'], 0, 26), $x['dr_puc'], substr((string)$x['dr_name'], 0, 32),
        $x['cr_puc'], substr((string)$x['cr_name'], 0, 32), $x['n'], mo($x['total']));
}

titulo('4. FLETES DE INTERRAPIDISIMO — asientos por tipo y cuenta');
foreach (rows($m, "
    SELECT e.entryTransactionType t, sd.pucCode dr_puc, sd.accountName dr_name,
           COUNT(*) n, COALESCE(SUM(CAST(e.entryDebitBalance AS DECIMAL(18,2))),0) total,
           MIN(e.entryDate) desde, MAX(e.entryDate) hasta
    FROM entries e JOIN subaccounts sd ON sd.id = e.entryDebitAccount
    WHERE e.deleted = 0 AND (sd.pucCode LIKE '5135%' OR e.entryTransactionType LIKE '%freight%'
       OR e.entryTransactionType LIKE '%flete%')
    GROUP BY e.entryTransactionType, e.entryDebitAccount
    ORDER BY total DESC") as $x)
    printf("  %-26s DR %-9s %-40s %3s  %16s   %s a %s\n",
        substr((string)$x['t'], 0, 26), $x['dr_puc'], substr((string)$x['dr_name'], 0, 40),
        $x['n'], mo($x['total']), $x['desde'], $x['hasta']);

titulo('5. FACTURAS DE LA TRANSPORTADORA — clasificacion por compania');
$t5 = rows($m, "SHOW TABLES LIKE 'contrapago_invoice_items'");
if (!$t5) { echo "  (no existe contrapago_invoice_items)\n"; }
else {
    foreach (rows($m, "
        SELECT COALESCE(NULLIF(company,''),'(vacio)') company, COUNT(*) n,
               COALESCE(SUM(valorFlete),0) total
        FROM contrapago_invoice_items
        GROUP BY company ORDER BY total DESC") as $x)
        printf("  %-14s %5s item(s)  %16s\n", $x['company'], $x['n'], mo($x['total']));

    echo "\n  Por factura de la transportadora y compania:\n";
    foreach (rows($m, "
        SELECT ci.id, ci.numeroFactura, ci.status,
               COALESCE(NULLIF(it.company,''),'(vacio)') company,
               COUNT(*) n, COALESCE(SUM(it.valorFlete),0) total
        FROM contrapago_invoices ci
        JOIN contrapago_invoice_items it ON it.invoice_id = ci.id
        GROUP BY ci.id, it.company
        ORDER BY ci.id, total DESC") as $x)
        printf("    #%-4s %-16s %-12s %-14s %5s  %16s\n",
            $x['id'], $x['numeroFactura'], $x['status'], $x['company'], $x['n'], mo($x['total']));
}

titulo('6. MOVIMIENTOS INTERCOMPANIAS (lo que MAM / MAM-Online nos deben)');
$t6 = rows($m, "SHOW TABLES LIKE 'intercompany_movements'");
if (!$t6) { echo "  (no existe intercompany_movements)\n"; }
else {
    foreach (rows($m, "
        SELECT COALESCE(NULLIF(partner_company,''),'(vacio)') partner, type, status,
               COUNT(*) n, COALESCE(SUM(amount),0) total
        FROM intercompany_movements WHERE deleted = 0
        GROUP BY partner_company, type, status
        ORDER BY partner, type") as $x)
        printf("  %-14s %-18s %-14s %4s  %16s\n",
            $x['partner'], $x['type'], $x['status'], $x['n'], mo($x['total']));
}

titulo('7. GASTOS REGISTRADOS, uno por uno');
foreach (rows($m, "
    SELECT r.id, r.code, r.expense_date, r.amount, r.status, r.description,
           ec.name categoria, s.pucCode, r.entry_id, r.payment_entry_id
    FROM expense_records r
    LEFT JOIN expense_categories ec ON ec.id = r.expense_category_id
    LEFT JOIN subaccounts s ON s.id = ec.accounting_subaccount_id
    WHERE r.deleted = 0 ORDER BY r.expense_date, r.id") as $x) {
    $sinAsiento = (!$x['entry_id'] || !$x['payment_entry_id']) ? '  <<< SIN ASIENTO COMPLETO' : '';
    printf("  %-3s %-9s %-11s %14s %-10s %-9s %-22s %s%s\n",
        $x['id'], $x['code'], $x['expense_date'], mo($x['amount']), $x['status'],
        $x['pucCode'], substr((string)$x['categoria'], 0, 22), substr((string)$x['description'], 0, 40), $sinAsiento);
}
