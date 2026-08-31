<?php
/**
 * RECUPERACIÓN — reconstruye ventas de bots perdidas cuyo NOMBRE aparece en
 * las hojas de Google (Medellín / Barranquilla) pero cuyo VALOR difiere del
 * pago (descuentos, recompras). El total de la factura es el VALOR REAL que
 * cobró Interrapidísimo; el detalle de la hoja se prorratea a ese total.
 *
 * POR ORDEN DE ALEX: SIN PAGOS. Las facturas quedan ABIERTAS (state 0,
 * payment 0) con la guía amarrada y el vínculo al lote; el pago se registra
 * después, cuando él lo decida.
 *
 * Reglas de seguridad:
 *  - una guía = una factura; una fila de hoja no se usa dos veces
 *  - nombre con varias filas candidatas: gana la de valor más cercano;
 *    empate real -> revisión manual (no se adivina)
 *  - diferencia hoja vs pago > 50% -> no se cruza
 *
 *   php reconstruir_48_sin_pago.php            (simulacion)
 *   php reconstruir_48_sin_pago.php --apply
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR (sin pagos) ===\n" : "=== SIMULACION (sin pagos) ===\n";

$UID = '71339095';
$BOTS = array(
    'medellin'     => array('csv' => '/tmp/sheet_medellin.csv',     'vendor' => '1234567',    'nombre' => 'GerLedxury Medellin'),
    'barranquilla' => array('csv' => '/tmp/sheet_barranquilla.csv', 'vendor' => '1048937562', 'nombre' => 'GerLedxury Barranquilla'),
);

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
function parseItems($s) {
    $out = array();
    // formato Medellin estructurado [[SKU,qty,total]]
    if (preg_match_all('/\[([A-Za-z0-9][A-Za-z0-9\-\.]+),\s*(\d+),\s*([0-9.]+)\]/', (string)$s, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $it) $out[] = array(strtoupper($it[1]), (int)$it[2]);
        return $out;
    }
    // formato "40x 6LED-12V-C, 30x ..."
    if (preg_match_all('/(\d+)\s*x\s*([A-Za-z0-9][A-Za-z0-9\-\.]+)/u', (string)$s, $mm, PREG_SET_ORDER))
        foreach ($mm as $it) $out[] = array(strtoupper($it[2]), (int)$it[1]);
    return $out;
}

// Filas de hoja (ambos bots)
$filas = array();
foreach ($BOTS as $bot => $cfg) {
    if (!is_file($cfg['csv'])) { fwrite(STDERR, "falta {$cfg['csv']}\n"); exit(1); }
    $fh = fopen($cfg['csv'], 'r');
    $hdr = fgetcsv($fh); $idx = array_flip($hdr);
    while (($row = fgetcsv($fh)) !== false) {
        $nombre = trim((string)($row[$idx['nombre']] ?? ''));
        if ($nombre === '') continue;
        $filas[] = array(
            'bot' => $bot,
            'nombre' => $nombre, 'nnorm' => norm($nombre),
            'cedula' => preg_replace('/[^0-9]/', '', (string)($row[$idx['documento']] ?? '')),
            'dir' => trim((string)($row[$idx['direccion']] ?? '')),
            'cel' => preg_replace('/[^0-9]/', '', (string)($row[$idx['celular']] ?? '')),
            'total' => (float)preg_replace('/[^0-9.]/', '', (string)($row[$idx['total']] ?? '')),
            'items' => parseItems($row[$idx['productos']] ?? ''),
            'usada' => false,
        );
    }
    fclose($fh);
}

// Guias de bots sin factura (menudeo < 500k, regla de Alex)
$pagos = array();
$r = $m->query("SELECT REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','') g, cp.nombreDestinatario nombre,
                       cp.valorTotal valor, DATE(cp.fechaVenta) fv, b.sheet_name lote, b.fecha_pago fp
                FROM contrapago_payments cp JOIN contrapago_batches b ON b.id=cp.batch_id
                WHERE cp.status='sin_match' AND cp.invoice_id IS NULL AND cp.valorTotal < 500000
                ORDER BY cp.fechaVenta");
while ($x = $r->fetch_assoc()) $pagos[] = $x;

$usadasGuia = array();
$r = $m->query("SELECT REGEXP_REPLACE(tracking_number,'[^0-9]','') g FROM invoices
                WHERE tracking_number IS NOT NULL AND tracking_number <> '' AND (deleted IS NULL OR deleted = 0)");
while ($x = $r->fetch_assoc()) $usadasGuia[$x['g']] = true;

$cliCols = array();
$r = $m->query("SHOW COLUMNS FROM clients");
while ($x = $r->fetch_assoc()) $cliCols[$x['Field']] = $x;

$creadas = 0; $suma = 0; $clientesNuevos = 0; $manuales = array(); $porBot = array();
if ($APPLY) $m->begin_transaction();

foreach ($pagos as $p) {
    $g = $p['g'];
    if (isset($usadasGuia[$g])) continue;
    $valorPago = (float)$p['valor'];

    // candidatas por nombre, no usadas, con diferencia de valor <= 50%
    $cands = array();
    foreach ($filas as $fi => $f) {
        if ($f['usada']) continue;
        if (!mismoNombre($f['nombre'], $p['nombre'])) continue;
        if ($f['total'] > 0 && abs($f['total'] - $valorPago) / $valorPago > 0.5) continue;
        $cands[] = $fi;
    }
    if (!$cands) continue;
    if (count($cands) > 1) {
        // gana la de valor mas cercano; empate -> manual
        usort($cands, function ($a, $b) use ($filas, $valorPago) {
            return abs($filas[$a]['total'] - $valorPago) <=> abs($filas[$b]['total'] - $valorPago);
        });
        $d0 = abs($filas[$cands[0]]['total'] - $valorPago);
        $d1 = abs($filas[$cands[1]]['total'] - $valorPago);
        if ($d0 === $d1) { $manuales[] = "$g {$p['nombre']} " . number_format($valorPago, 0, ',', '.') . " (candidatas empatadas)"; continue; }
    }
    $fi = $cands[0];
    $f = $filas[$fi];
    $filas[$fi]['usada'] = true;
    $usadasGuia[$g] = true;
    $cfg = $BOTS[$f['bot']];

    $cel = $f['cel'];
    if (strlen($cel) === 12 && strpos($cel, '57') === 0) $cel = substr($cel, 2);
    $fecha = ($p['fv'] ?: substr($p['fp'], 0, 10)) . ' 12:00:00';

    // cliente
    $clientId = null;
    if ($f['cedula'] !== '') {
        $q = $m->query("SELECT idClient FROM clients WHERE deleted=0 AND REGEXP_REPLACE(COALESCE(idNum,''),'[^0-9]','') = '{$f['cedula']}' LIMIT 1");
        if ($q && ($x = $q->fetch_assoc())) $clientId = (int)$x['idClient'];
    }
    if (!$clientId) {
        $q = $m->query("SELECT idClient FROM clients WHERE deleted=0 AND LOWER(TRIM(name)) = LOWER(" . esc($m, $f['nombre']) . ") LIMIT 1");
        if ($q && ($x = $q->fetch_assoc())) $clientId = (int)$x['idClient'];
    }
    $cliTag = '';
    if (!$clientId) {
        $clientesNuevos++; $cliTag = ' [cliente nuevo]';
        if ($APPLY) {
            $data = array('name' => $f['nombre'], 'idNum' => $f['cedula'], 'cellphone' => $cel,
                'address' => mb_substr($f['dir'], 0, 250), 'vendor' => $cfg['vendor'], 'created_by' => $UID,
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
                echo "ERROR cliente {$f['nombre']}: {$m->error}\n"; $m->rollback(); exit(1);
            }
            $clientId = $m->insert_id;
        } else $clientId = 0;
    }

    $obs = "[RECUPERADA-SHEET 31/08/2026] Venta del bot {$cfg['nombre']} reconstruida (cruce por nombre; total = valor real cobrado por Interrapidisimo"
         . ($f['total'] > 0 && $f['total'] != $valorPago ? ", la hoja decia " . number_format($f['total'], 0, ',', '.') : '')
         . "). {$f['nombre']} CC {$f['cedula']} TL $cel DR " . mb_substr($f['dir'], 0, 150)
         . ". Guia $g en lote {$p['lote']} ({$p['fp']}). PENDIENTE REGISTRAR PAGO por orden de Alex.";

    if ($APPLY) {
        $ok = $m->query("INSERT INTO budgets (tenant_id, clientId, vendorId, storeId, total, comments, date, state, deleted, created_by, created_at, updated_at)
            VALUES (1, $clientId, '{$cfg['vendor']}', 1, " . (int)$valorPago . ", " . esc($m, $obs) . ", '$fecha', 4, 0, '$UID', NOW(), NOW())");
        if (!$ok) { echo "ERROR budget $g: {$m->error}\n"; $m->rollback(); exit(1); }
        $budgetId = $m->insert_id;
        $items = $f['items'];
        $qtyTot = 0; foreach ($items as $it) $qtyTot += $it[1];
        $iN = count($items); $restante = (int)$valorPago;
        foreach ($items as $k => $it) {
            $sub = ($k === $iN - 1) ? $restante : (int)round($valorPago * $it[1] / max($qtyTot, 1));
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
            VALUES (1, 0, $budgetId, $clientId, '{$cfg['vendor']}', 1, 1,
                0, 0, 10, " . (float)$valorPago . ", 0, 8, 0, " . esc($m, $obs) . ",
                '$fecha', 0, 0, 1, 0, 0, 0,
                0, 0, 0, 'interrapidisimo', '$g', 'interrapidisimo',
                '$UID', NOW(), NOW(), 0)");
        if (!$ok) { echo "ERROR factura $g: {$m->error}\n"; $m->rollback(); exit(1); }
        $invId = $m->insert_id;
        $restante = (int)$valorPago;
        foreach ($items as $k => $it) {
            $sub = ($k === $iN - 1) ? $restante : (int)round($valorPago * $it[1] / max($qtyTot, 1));
            $restante -= $sub;
            $unit = (int)round($sub / max($it[1], 1));
            $m->query("INSERT INTO invoice_details (invoiceId, productId, quantity, unit, base, total, reviewed, tenant_id)
                VALUES ($invId, " . esc($m, $it[0]) . ", {$it[1]}, $unit, $unit, $sub, 0, 1)");
        }
        // vinculo informativo con el lote (NO es pago)
        $m->query("UPDATE contrapago_payments SET company = 'ledxury', invoice_id = $invId
                   WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '$g'");
        $m->query("UPDATE contrapago_invoice_items SET company = 'ledxury', invoice_system_id = $invId
                   WHERE REGEXP_REPLACE(numero_guia,'[^0-9]','') = '$g' AND shipping_guide_id IS NULL");
    }
    $skuTxt = implode('+', array_map(function ($i) { return $i[1] . 'x' . $i[0]; }, $f['items']));
    printf("  [%s] %s %-24s %9s (hoja: %s) %s (%s %s)%s\n", strtoupper(substr($f['bot'], 0, 3)), $g,
        mb_substr($f['nombre'], 0, 24), number_format($valorPago, 0, ',', '.'),
        number_format($f['total'], 0, ',', '.'), $skuTxt ?: 'SIN-DETALLE', $p['lote'], $p['fp'], $cliTag);
    $creadas++; $suma += $valorPago;
    $porBot[$f['bot']] = ($porBot[$f['bot']] ?? 0) + 1;
}
if ($APPLY) $m->commit();

printf("\nreconstruidas SIN PAGO (abiertas): %d por %s | clientes nuevos: %d | por bot: %s\n",
    $creadas, number_format($suma, 0, ',', '.'), $clientesNuevos, json_encode($porBot));
if ($manuales) { echo "para revision manual (empates):\n"; foreach ($manuales as $x) echo "  $x\n"; }
