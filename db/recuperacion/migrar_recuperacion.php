<?php
/**
 * Migra las 3 facturas vivas de supplier_invoices al módulo nuevo de compras
 * (provider_invoices), sin tocar la contabilidad en montos: solo re-etiqueta
 * los asientos para que el módulo nuevo los reconozca como suyos.
 *
 * Ejecutar en el server de ledxury:  php migrar_a_provider_invoices.php [--apply]
 *
 * Qué migra:
 *   - SALDO-INICIAL-MAMONLINE-20260801 ($129.308.187) → status 'open'
 *     (sus 3 asientos supplier_bill → provider_invoice)
 *   - REM-MAM-00046 y REM-MAM-00047 (agosto) → status 'en_transito' + items
 *     (su asiento supplier_bill → provider_invoice_transit)
 *   - mam_remision_sync apunta a los ids nuevos
 *   - las filas viejas de supplier_invoices quedan deleted=1 con nota
 *     (el módulo viejo deja de mostrarlas; el nuevo es la fuente)
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

function one($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); exit(1); } return $r->fetch_assoc(); }
function all($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); exit(1); } $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o; }
function run($m, $APPLY, $sql) {
    if (!$APPLY) { echo "  [sim] " . preg_replace('/\s+/', ' ', substr(trim($sql), 0, 150)) . "\n"; return 0; }
    if (!$m->query($sql)) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); $m->rollback(); exit(1); }
    echo "  [ok] filas: {$m->affected_rows}\n";
    return $m->insert_id;
}

// Guardas
$t = one($m, "SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'provider_invoices'");
if ((int)$t['n'] === 0) { echo "ABORTA: falta la tabla provider_invoices (migración 069).\n"; exit(1); }
$ya = one($m, "SELECT COUNT(*) n FROM provider_invoices WHERE inv_code = 'SALDO-INICIAL-MAMONLINE-20260801'");
if ((int)$ya['n'] > 0) { echo "ABORTA: la migración ya corrió.\n"; exit(0); }

$bills = all($m, "SELECT * FROM supplier_invoices WHERE providerId = '12' AND deleted = 0
                  AND (invoiceNumber LIKE 'SALDO-INICIAL%' OR invoiceNumber LIKE 'REM-MAM-%') ORDER BY idSupplierInvoice");
if (count($bills) < 1) { echo "ABORTA: no hay facturas vivas que migrar.
"; exit(1); }

if ($APPLY) $m->begin_transaction();

foreach ($bills as $b) {
    $esSaldo = strpos($b['invoiceNumber'], 'SALDO-INICIAL') === 0;
    $status  = $esSaldo ? 'open' : 'en_transito';
    $remId   = $esSaldo ? null : (int) ltrim(substr($b['invoiceNumber'], 8), '0');
    $origen  = $esSaldo ? null : ('remision:' . $remId);
    echo "── {$b['invoiceNumber']} → provider_invoices ($status)\n";

    run($m, $APPLY, sprintf(
        "INSERT INTO provider_invoices (inv_code, provider_id, issue_date, due_date, currency, exchange_rate,
            subtotal, tax, withholding, total, paid, status, origin_ref, notes, created_by, created_at, updated_at, deleted)
         VALUES ('%s', 12, '%s', %s, 'COP', 1, %.2f, 0, 0, %.2f, %.2f, '%s', %s, '%s', 'migracion', NOW(), NOW(), 0)",
        $m->real_escape_string($b['invoiceNumber']), $b['invoiceDate'],
        $b['dueDate'] ? "'{$b['dueDate']}'" : 'NULL',
        (float)$b['subtotal'], (float)$b['total'], (float)$b['paidAmount'], $status,
        $origen ? "'" . $m->real_escape_string($origen) . "'" : 'NULL',
        $m->real_escape_string((string)$b['notes'])));
    $newId = $APPLY ? $m->insert_id : 0;

    // items
    $dets = all($m, "SELECT * FROM supplier_invoice_details WHERE supplierInvoiceId = " . (int)$b['idSupplierInvoice']);
    foreach ($dets as $d) {
        run($m, $APPLY, sprintf(
            "INSERT INTO provider_invoice_items (provider_invoice_id, product_id, description, quantity, unit_cost, total, created_at)
             VALUES (%d, '%s', '%s', %.3f, %.4f, %.2f, NOW())",
            $newId, $m->real_escape_string($d['productId']), $m->real_escape_string((string)$d['description']),
            (float)$d['quantity'], (float)$d['unitPrice'], (float)$d['total']));
    }
    echo "   items migrados: " . count($dets) . "\n";

    // re-etiquetar asientos (mismos montos y cuentas; solo cambia la referencia)
    $tipoNuevo = $esSaldo ? 'provider_invoice' : 'provider_invoice_transit';
    run($m, $APPLY, sprintf(
        "UPDATE entries SET entryTransactionType = '%s', entryTransactionId = %d
         WHERE entryTransactionType = 'supplier_bill' AND entryTransactionId = %d AND deleted = 0",
        $tipoNuevo, $newId, (int)$b['idSupplierInvoice']));

    // apuntar el puente al id nuevo
    if ($remId) {
        run($m, $APPLY, sprintf(
            "UPDATE mam_remision_sync SET supplier_invoice_id = %d WHERE remision_id = %d", $newId, $remId));
    }

    // retirar la factura vieja del módulo viejo
    run($m, $APPLY, sprintf(
        "UPDATE supplier_invoices SET deleted = 1, deleted_at = NOW(),
            notes = CONCAT(COALESCE(notes,''), '\n[MIGRADA a provider_invoices #%d el 20/08/2026]')
         WHERE idSupplierInvoice = %d", $newId, (int)$b['idSupplierInvoice']));
}

if ($APPLY) {
    $m->commit();
    echo "\n=== VERIFICACION ===\n";
    foreach (all($m, "SELECT id, inv_code, status, total, paid FROM provider_invoices ORDER BY id") as $r) {
        echo "  #{$r['id']} {$r['inv_code']} [{$r['status']}] total " . number_format($r['total'], 0, ',', '.') . "\n";
    }
    $v = one($m, "SELECT COUNT(*) n, COALESCE(SUM(entryDebitBalance),0) s FROM entries WHERE deleted=0 AND entryTransactionType LIKE 'provider_invoice%'");
    echo "  asientos re-etiquetados: {$v['n']} por " . number_format($v['s'], 0, ',', '.') . "\n";
    $v = one($m, "SELECT SUM(entryDebitBalance)-SUM(entryCreditBalance) d FROM entries WHERE deleted=0");
    echo "  partida doble global: " . number_format($v['d'], 2, ',', '.') . " (debe ser 0)\n";
    $v = one($m, "SELECT COALESCE(SUM(total-paid),0) s FROM provider_invoices WHERE deleted=0 AND status IN ('open','paid_partial','en_transito')");
    echo "  CxP total en el módulo nuevo: " . number_format($v['s'], 0, ',', '.') . " (debe ser 131.791.567)\n";
} else {
    echo "\n=== FIN SIMULACION ===\n";
}
