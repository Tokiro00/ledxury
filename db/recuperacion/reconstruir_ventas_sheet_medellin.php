<?php
/**
 * RECUPERACIÓN — reconstruye las facturas del bot GerLedxury Medellín
 * perdidas con la instancia, desde su hoja de Google (que siguió viva y
 * escribe cada venta con el detalle estructurado):
 *
 *   productos = [[SKU,cantidad,total],...] + nombre, cédula, dirección,
 *   celular, total, fecha y guía.
 *
 * Alcance: filas cuya GUÍA está en los lotes de pago de Interrapidísimo como
 * 'sin_match' y sin factura — es plata ya recaudada cuya venta no existe en
 * el sistema. La factura se recrea con el cliente real, el detalle real, el
 * vendedor GerMam, fecha original, tracking = guía, y queda PAGADA con la
 * fecha del lote (pago método 5, sin movimiento de tesorería: esos recaudos
 * ya viven en el saldo del banco de la apertura, o entrarán con el registro
 * del lote 19).
 *
 * Requiere /tmp/sheet_medellin.csv (export de la hoja).
 *
 *   php reconstruir_ventas_sheet_medellin.php            (simulacion)
 *   php reconstruir_ventas_sheet_medellin.php --apply
 *
 * Idempotente: salta guías que ya tengan factura (tracking) en el sistema.
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

$VENDOR = '1234567';   // GerMam (bot GerLedxury Medellín)
$UID    = '71339095';
$CSV    = '/tmp/sheet_medellin.csv';
if (!is_file($CSV)) { fwrite(STDERR, "falta $CSV\n"); exit(1); }

function esc($m, $v) { return "'" . $m->real_escape_string((string)$v) . "'"; }
function fechaHoja($s) {
    $s = trim((string)$s);
    if ($s === '') return null;
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', $s, $x))          // d/m/Y
        return sprintf('%04d-%02d-%02d 12:00:00', $x[3], $x[2], $x[1]);
    $ts = strtotime($s);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

// Guías sin match y sin factura, con su lote y fecha de pago
$sinMatch = array();
$r = $m->query("SELECT REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','') g, MAX(b.sheet_name) lote, MAX(b.fecha_pago) fp
                FROM contrapago_payments cp JOIN contrapago_batches b ON b.id=cp.batch_id
                WHERE cp.status='sin_match' AND cp.invoice_id IS NULL
                GROUP BY 1");
while ($x = $r->fetch_assoc()) $sinMatch[$x['g']] = $x;

// Guías que ya tienen factura (no tocar)
$conFactura = array();
$r = $m->query("SELECT REGEXP_REPLACE(tracking_number,'[^0-9]','') g FROM invoices
                WHERE tracking_number IS NOT NULL AND tracking_number <> '' AND (deleted IS NULL OR deleted = 0)");
while ($x = $r->fetch_assoc()) $conFactura[$x['g']] = true;

// Esquemas para defaults dinamicos de clients
$cliCols = array();
$r = $m->query("SHOW COLUMNS FROM clients");
while ($x = $r->fetch_assoc()) $cliCols[$x['Field']] = $x;

$fh = fopen($CSV, 'r');
$hdr = fgetcsv($fh);
$idx = array_flip($hdr);
$creadas = 0; $saltadas = 0; $sinSku = 0; $clientesNuevos = 0; $suma = 0; $detallesMalos = array();

if ($APPLY) $m->begin_transaction();
while (($row = fgetcsv($fh)) !== false) {
    $guia = preg_replace('/[^0-9]/', '', (string)$row[$idx['guia']]);
    if (strlen($guia) < 8) continue;
    if (!isset($sinMatch[$guia])) continue;          // solo las recaudadas sin factura
    if (isset($conFactura[$guia])) { $saltadas++; continue; }

    $nombre  = trim((string)$row[$idx['nombre']]);
    $cedula  = preg_replace('/[^0-9]/', '', (string)$row[$idx['documento']]);
    $dir     = trim((string)$row[$idx['direccion']]);
    $celular = preg_replace('/[^0-9]/', '', (string)$row[$idx['celular']]);
    if (strlen($celular) === 12 && strpos($celular, '57') === 0) $celular = substr($celular, 2);
    $total   = (float)preg_replace('/[^0-9.]/', '', (string)$row[$idx['total']]);
    $fecha   = fechaHoja($row[$idx['fecha']]);
    $prodRaw = trim((string)$row[$idx['productos']]);
    if ($nombre === '' || $total <= 0 || !$fecha) continue;

    // detalle [[SKU,qty,total],[...]]
    $items = array();
    if (preg_match_all('/\[([A-Za-z0-9\-\.]+),\s*([0-9]+),\s*([0-9.]+)\]/', $prodRaw, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $it) $items[] = array(strtoupper($it[1]), (int)$it[2], (float)$it[3]);
    }
    if (!$items) { $detallesMalos[] = "$guia: $prodRaw"; }

    // cliente por cedula, luego por nombre; crear si falta
    $clientId = null;
    if ($cedula !== '') {
        $q = $m->query("SELECT idClient FROM clients WHERE deleted=0 AND REGEXP_REPLACE(COALESCE(idNum,''),'[^0-9]','') = '$cedula' LIMIT 1");
        if ($q && ($x = $q->fetch_assoc())) $clientId = (int)$x['idClient'];
    }
    if (!$clientId) {
        $q = $m->query("SELECT idClient FROM clients WHERE deleted=0 AND LOWER(TRIM(name)) = LOWER(" . esc($m, $nombre) . ") LIMIT 1");
        if ($q && ($x = $q->fetch_assoc())) $clientId = (int)$x['idClient'];
    }
    $cliTag = '';
    if (!$clientId) {
        $clientesNuevos++; $cliTag = ' [cliente nuevo]';
        if ($APPLY) {
            $data = array('name' => $nombre, 'idNum' => $cedula, 'cellphone' => $celular,
                'address' => mb_substr($dir, 0, 250), 'vendor' => $VENDOR, 'created_by' => $UID,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted' => 0);
            if (isset($cliCols['tenant_id'])) $data['tenant_id'] = 1;
            foreach ($cliCols as $col => $def) {
                if (isset($data[$col]) || $col === 'idClient') continue;
                if ($def['Null'] === 'NO' && $def['Default'] === null && stripos($def['Extra'], 'auto') === false)
                    $data[$col] = (stripos($def['Type'], 'int') !== false || stripos($def['Type'], 'decimal') !== false) ? 0 : '';
            }
            $cn = array(); $vn = array();
            foreach ($data as $k => $v) { $cn[] = "`$k`"; $vn[] = is_int($v) ? (string)$v : esc($m, $v); }
            if (!$m->query("INSERT INTO clients (" . implode(',', $cn) . ") VALUES (" . implode(',', $vn) . ")")) {
                echo "ERROR cliente $nombre: {$m->error}\n"; $m->rollback(); exit(1);
            }
            $clientId = $m->insert_id;
        } else $clientId = 0;
    }

    $lote = $sinMatch[$guia]['lote']; $fp = $sinMatch[$guia]['fp'];
    $obs = "[RECUPERADA-SHEET 31/08/2026] Venta del bot GerLedxury Medellin reconstruida desde su hoja de Google (instancia perdida 23/08). "
         . "$nombre CC $cedula TL $celular DR " . mb_substr($dir, 0, 160) . ". Guia $guia pagada por Interrapidisimo en $lote ($fp).";

    if ($APPLY) {
        // presupuesto de respaldo (FK) + factura pagada + detalle + pago
        $ok = $m->query("INSERT INTO budgets (tenant_id, clientId, vendorId, storeId, total, comments, date, state, deleted, created_by, created_at, updated_at)
            VALUES (1, $clientId, '$VENDOR', 1, " . (int)$total . ", " . esc($m, $obs) . ", '$fecha', 4, 0, '$UID', NOW(), NOW())");
        if (!$ok) { echo "ERROR budget $guia: {$m->error}\n"; $m->rollback(); exit(1); }
        $budgetId = $m->insert_id;
        foreach ($items as $it) {
            $m->query("INSERT INTO budget_detail (budgetId, productId, quantity, unit, base, total, tenant_id)
                VALUES ($budgetId, " . esc($m, $it[0]) . ", {$it[1]}, " . (int)round($it[2] / max($it[1], 1)) . ", " . (int)round($it[2] / max($it[1], 1)) . ", " . (int)$it[2] . ", 1)");
        }
        $ok = $m->query("INSERT INTO invoices (tenant_id, if_id, budgetId, clientId, vendorId, storeId, delivery_type,
                payment, discount, discount_perc, total, hasIva, iva, e_commerce, comments,
                date, state, settled, printed, legal_collection, blacklisted, list_price,
                check_delivery, close_to_expire, is_expired, transportadora, tracking_number, tracking_carrier,
                created_by, created_at, updated_at, deleted)
            VALUES (1, 0, $budgetId, $clientId, '$VENDOR', 1, 1,
                " . (float)$total . ", 0, 10, " . (float)$total . ", 0, 8, 0, " . esc($m, $obs) . ",
                '$fecha', 2, 0, 1, 0, 0, 0,
                0, 0, 0, 'interrapidisimo', '$guia', 'interrapidisimo',
                '$UID', NOW(), NOW(), 0)");
        if (!$ok) { echo "ERROR factura $guia: {$m->error}\n"; $m->rollback(); exit(1); }
        $invId = $m->insert_id;
        foreach ($items as $it) {
            $unit = (int)round($it[2] / max($it[1], 1));
            $m->query("INSERT INTO invoice_details (invoiceId, productId, quantity, unit, base, total, reviewed, tenant_id)
                VALUES ($invId, " . esc($m, $it[0]) . ", {$it[1]}, $unit, $unit, " . (int)$it[2] . ", 0, 1)");
        }
        $fpago = $fp ? "$fp 12:00:00" : date('Y-m-d H:i:s');
        $m->query("INSERT INTO payments (tenant_id, invoiceId, clientId, vendorId, paymentMethod, payment, comments,
                date, originType, originId, cashMovementId, deleted, created_at, updated_at)
            VALUES (1, $invId, $clientId, '$VENDOR', 5, " . (float)$total . ",
                " . esc($m, "Pago contrapago Interrapidísimo - Guía $guia - Lote $lote [RECUPERADO: sin movimiento de tesorería]") . ",
                '$fpago', 'banco', 1, NULL, 0, NOW(), NOW())");
        $m->query("UPDATE contrapago_payments SET company = 'ledxury', invoice_id = $invId, status = 'conciliado'
                   WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '$guia'");
        $m->query("UPDATE contrapago_invoice_items SET company = 'ledxury', invoice_system_id = $invId
                   WHERE REGEXP_REPLACE(numero_guia,'[^0-9]','') = '$guia' AND shipping_guide_id IS NULL");
    }
    $skuTxt = implode('+', array_map(function ($i) { return $i[1] . 'x' . $i[0]; }, $items));
    printf("  %s %-24s %9s %s (%s pagada %s)%s\n", $guia, mb_substr($nombre, 0, 24),
        number_format($total, 0, ',', '.'), $skuTxt ?: 'SIN-DETALLE', $lote, $fp, $cliTag);
    $creadas++; $suma += $total;
}
fclose($fh);
if ($APPLY) $m->commit();

printf("\nfacturas reconstruidas: %d por %s | clientes nuevos: %d | ya tenian factura: %d\n",
    $creadas, number_format($suma, 0, ',', '.'), $clientesNuevos, $saltadas);
if ($detallesMalos) { echo "detalle no parseable (quedan sin renglones):\n";
    foreach ($detallesMalos as $d) echo "  $d\n"; }

// SKUs del detalle que no existen en products (informativo)
if ($APPLY) {
    $r = $m->query("SELECT DISTINCT d.productId FROM invoice_details d
                    LEFT JOIN products p ON p.idProduct = d.productId
                    JOIN invoices i ON i.idInvoice = d.invoiceId
                    WHERE p.idProduct IS NULL AND i.comments LIKE '[RECUPERADA-SHEET%'");
    $faltan = array(); while ($x = $r->fetch_assoc()) $faltan[] = $x['productId'];
    if ($faltan) echo "SKUs en detalles que NO existen en products (revisar): " . implode(', ', $faltan) . "\n";
}
