<?php
/**
 * RECUPERACIÓN — reconstruye ventas del bot GerLedxury Barranquilla desde su
 * hoja de Google. A diferencia de la de Medellín, esta hoja NO escribe la
 * guía y sus fechas no son confiables, así que el cruce va por NOMBRE
 * normalizado + VALOR contra las guías de los lotes de pago que siguen sin
 * factura. La fecha de la factura se toma de la fechaVenta de la guía en el
 * lote (la de Interrapidísimo, confiable).
 *
 * Detalle de productos: "40x 6LED-12V-C" o lista "10x A, 10x B, ..." con
 * total global (se prorratea por cantidad).
 *
 * Requiere /tmp/sheet_barranquilla.csv.
 *
 *   php reconstruir_ventas_sheet_barranquilla.php            (simulacion)
 *   php reconstruir_ventas_sheet_barranquilla.php --apply
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

$VENDOR = '1048937562';   // Germam Maria Barranquilla
$UID    = '71339095';
$CSV    = '/tmp/sheet_barranquilla.csv';
if (!is_file($CSV)) { fwrite(STDERR, "falta $CSV\n"); exit(1); }

function esc($m, $v) { return "'" . $m->real_escape_string((string)$v) . "'"; }
function norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = strtr($s, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'));
    return preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', '', $s));
}
function mismoNombre($a, $b) {
    $a = norm($a); $b = norm($b);
    if ($a === '' || $b === '') return false;
    if ($a === $b) return true;
    $ta = array_filter(explode(' ', $a), function ($t) { return strlen($t) > 2; });
    $tb = array_filter(explode(' ', $b), function ($t) { return strlen($t) > 2; });
    if (count($ta) < 2 || count($tb) < 2) return false;
    $corto = count($ta) <= count($tb) ? $ta : $tb;
    $largo = count($ta) <= count($tb) ? $tb : $ta;
    foreach ($corto as $t) if (!in_array($t, $largo, true)) return false;
    return true;
}
// "40x 6LED-12V-C" | "10x 6LED-24V-I, 10x 6LED-24V-C" -> [[SKU,qty],...]
function parseItems($s) {
    $out = array();
    if (preg_match_all('/(\d+)\s*x\s*([A-Za-z0-9][A-Za-z0-9\-\.]+)/u', (string)$s, $mm, PREG_SET_ORDER))
        foreach ($mm as $it) $out[] = array(strtoupper($it[2]), (int)$it[1]);
    return $out;
}

// Guías sin factura en lotes (cualquier company: la etiqueta 'mam' por defecto
// esconde ventas nuestras), con nombre/valor/fechas
$pagos = array();
$r = $m->query("SELECT REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','') g, cp.nombreDestinatario nombre,
                       cp.valorTotal valor, DATE(cp.fechaVenta) fv, b.sheet_name lote, b.fecha_pago fp
                FROM contrapago_payments cp JOIN contrapago_batches b ON b.id=cp.batch_id
                WHERE cp.status='sin_match' AND cp.invoice_id IS NULL");
while ($x = $r->fetch_assoc()) $pagos[] = $x;
echo "guias de lotes sin factura: " . count($pagos) . "\n";

// Guías ya con factura (no tocar)
$usadas = array();
$r = $m->query("SELECT REGEXP_REPLACE(tracking_number,'[^0-9]','') g FROM invoices
                WHERE tracking_number IS NOT NULL AND tracking_number <> '' AND (deleted IS NULL OR deleted = 0)");
while ($x = $r->fetch_assoc()) $usadas[$x['g']] = true;

$cliCols = array();
$r = $m->query("SHOW COLUMNS FROM clients");
while ($x = $r->fetch_assoc()) $cliCols[$x['Field']] = $x;

$fh = fopen($CSV, 'r');
$hdr = fgetcsv($fh);
$idx = array_flip($hdr);
$creadas = 0; $clientesNuevos = 0; $suma = 0; $ambiguas = 0; $vistos = array();

if ($APPLY) $m->begin_transaction();
while (($row = fgetcsv($fh)) !== false) {
    $nombre  = trim((string)($row[$idx['nombre']] ?? ''));
    $total   = (float)preg_replace('/[^0-9.]/', '', (string)($row[$idx['total']] ?? ''));
    if ($nombre === '' || $total <= 0) continue;

    // candidatos en pagos por nombre + valor
    $cands = array();
    foreach ($pagos as $pi => $p) {
        if (isset($usadas[$p['g']])) continue;
        if ((float)$p['valor'] != $total) continue;
        if (!mismoNombre($p['nombre'], $nombre)) continue;
        $cands[] = $pi;
    }
    if (count($cands) === 0) continue;
    if (count($cands) > 1) { $ambiguas++; continue; }   // dos pagos iguales al mismo nombre: manual
    $p = $pagos[$cands[0]];
    $g = $p['g'];
    // clave anti doble uso de la misma fila de hoja
    $clave = norm($nombre) . '|' . $total . '|' . $g;
    if (isset($vistos[$clave])) continue;
    $vistos[$clave] = true;
    $usadas[$g] = true;

    $cedula  = preg_replace('/[^0-9]/', '', (string)($row[$idx['documento']] ?? ''));
    $dir     = trim((string)($row[$idx['direccion']] ?? ''));
    $celular = preg_replace('/[^0-9]/', '', (string)($row[$idx['celular']] ?? ''));
    if (strlen($celular) === 12 && strpos($celular, '57') === 0) $celular = substr($celular, 2);
    $items   = parseItems($row[$idx['productos']] ?? '');
    $fecha   = ($p['fv'] ?: substr($p['fp'], 0, 10)) . ' 12:00:00';

    // cliente
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

    $obs = "[RECUPERADA-SHEET 31/08/2026] Venta del bot GerLedxury Barranquilla reconstruida desde su hoja de Google (instancia perdida 23/08); "
         . "cruce por nombre+valor contra el pago. $nombre CC $cedula TL $celular DR " . mb_substr($dir, 0, 160)
         . ". Guia $g pagada por Interrapidisimo en {$p['lote']} ({$p['fp']}).";

    if ($APPLY) {
        $ok = $m->query("INSERT INTO budgets (tenant_id, clientId, vendorId, storeId, total, comments, date, state, deleted, created_by, created_at, updated_at)
            VALUES (1, $clientId, '$VENDOR', 1, " . (int)$total . ", " . esc($m, $obs) . ", '$fecha', 4, 0, '$UID', NOW(), NOW())");
        if (!$ok) { echo "ERROR budget $g: {$m->error}\n"; $m->rollback(); exit(1); }
        $budgetId = $m->insert_id;
        $qtyTot = 0; foreach ($items as $it) $qtyTot += $it[1];
        $restante = (int)$total; $iN = count($items);
        foreach ($items as $k => $it) {
            $sub = ($k === $iN - 1) ? $restante : (int)round($total * $it[1] / max($qtyTot, 1));
            $restante -= $sub;
            $unit = (int)round($sub / max($it[1], 1));
            $m->query("INSERT INTO budget_detail (budgetId, productId, quantity, unit, base, total, tenant_id)
                VALUES ($budgetId, " . esc($m, $it[0]) . ", {$it[1]}, $unit, $unit, $sub, 1)");
        }
        $ok = $m->query("INSERT INTO invoices (tenant_id, if_id, budgetId, clientId, vendorId, storeId, delivery_type,
                payment, discount, discount_perc, total, hasIva, iva, e_commerce, comments,
                date, state, settled, printed, legal_collection, blacklisted, list_price,
                check_delivery, close_to_expire, is_expired, transportadora, tracking_number, tracking_carrier,
                created_by, created_at, updated_at, deleted)
            VALUES (1, 0, $budgetId, $clientId, '$VENDOR', 1, 1,
                " . (float)$total . ", 0, 10, " . (float)$total . ", 0, 8, 0, " . esc($m, $obs) . ",
                '$fecha', 2, 0, 1, 0, 0, 0,
                0, 0, 0, 'interrapidisimo', '$g', 'interrapidisimo',
                '$UID', NOW(), NOW(), 0)");
        if (!$ok) { echo "ERROR factura $g: {$m->error}\n"; $m->rollback(); exit(1); }
        $invId = $m->insert_id;
        $restante = (int)$total;
        foreach ($items as $k => $it) {
            $sub = ($k === $iN - 1) ? $restante : (int)round($total * $it[1] / max($qtyTot, 1));
            $restante -= $sub;
            $unit = (int)round($sub / max($it[1], 1));
            $m->query("INSERT INTO invoice_details (invoiceId, productId, quantity, unit, base, total, reviewed, tenant_id)
                VALUES ($invId, " . esc($m, $it[0]) . ", {$it[1]}, $unit, $unit, $sub, 0, 1)");
        }
        $fpago = $p['fp'] ? $p['fp'] . ' 12:00:00' : date('Y-m-d H:i:s');
        $m->query("INSERT INTO payments (tenant_id, invoiceId, clientId, vendorId, paymentMethod, payment, comments,
                date, originType, originId, cashMovementId, deleted, created_at, updated_at)
            VALUES (1, $invId, $clientId, '$VENDOR', 5, " . (float)$total . ",
                " . esc($m, "Pago contrapago Interrapidísimo - Guía $g - Lote {$p['lote']} [RECUPERADO: sin movimiento de tesorería]") . ",
                '$fpago', 'banco', 1, NULL, 0, NOW(), NOW())");
        $m->query("UPDATE contrapago_payments SET company = 'ledxury', invoice_id = $invId, status = 'conciliado'
                   WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '$g'");
        $m->query("UPDATE contrapago_invoice_items SET company = 'ledxury', invoice_system_id = $invId
                   WHERE REGEXP_REPLACE(numero_guia,'[^0-9]','') = '$g' AND shipping_guide_id IS NULL");
    }
    $skuTxt = implode('+', array_map(function ($i) { return $i[1] . 'x' . $i[0]; }, $items));
    printf("  %s %-26s %9s %s (%s pagada %s)%s\n", $g, mb_substr($nombre, 0, 26),
        number_format($total, 0, ',', '.'), $skuTxt ?: 'SIN-DETALLE', $p['lote'], $p['fp'], $cliTag);
    $creadas++; $suma += $total;
}
fclose($fh);
if ($APPLY) $m->commit();
printf("\nreconstruidas: %d por %s | clientes nuevos: %d | ambiguas (manual): %d\n",
    $creadas, number_format($suma, 0, ',', '.'), $clientesNuevos, $ambiguas);
