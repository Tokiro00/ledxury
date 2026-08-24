<?php
/**
 * RECUPERACIÓN 24/08/2026 — importa al servidor nuevo lo que se salvó en Excel
 * tras la pérdida de la instancia (respaldo restaurado: 18/06):
 *
 *   1. mam_dispatches: 68 despachos de MAM (jul-ago) para el auto-tag de guías.
 *   2. Facturas de corte Interrapidísimo 210579 (1-15 jul) y 211205 (1-15 ago).
 *   3. Lotes de pago contrapago PAGO 11-17 (19/06 -> 14/08, $34.148.100).
 *
 * Replica la lógica de Contrapagos::upload() e importInvoice() (hash de hoja,
 * matchGuides con duplicadas, auto-tag MAM, vínculo de facturas por observación)
 * SIN tocar contabilidad ni tesorería: todos estos hechos son anteriores a la
 * apertura del 20/08, que ya los absorbe en el balance inicial.
 *
 *   php importar_contrapagos_recuperados.php            (simulación)
 *   php importar_contrapagos_recuperados.php --apply
 *
 * Idempotente: hash de guías por lote, numero_factura por corte y numero_guia
 * por despacho. Correrlo dos veces no duplica nada.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
$USER  = '71339095';

$json = json_decode(file_get_contents(__DIR__ . '/datos_contrapagos.json'), true);
if (!$json) { fwrite(STDERR, "no pude leer datos_contrapagos.json\n"); exit(1); }

echo $APPLY ? "=== MODO APLICAR ===\n" : "=== SIMULACION (sin --apply no escribe nada) ===\n";

function q($m, $sql) { $r = $m->query($sql); if ($r === false) { echo "  SQL ERROR: {$m->error}\n  en: " . substr($sql,0,140) . "\n"; } return $r; }
function esc($m, $v) { return $v === null ? 'NULL' : "'" . $m->real_escape_string($v) . "'"; }
function insert($m, $APPLY, $tabla, $data) {
    static $sim = 1000000;
    $cols = array(); $vals = array();
    foreach ($data as $k => $v) {
        $cols[] = "`$k`";
        $vals[] = is_int($v) || is_float($v) ? (string)$v : esc($m, $v);
    }
    if (!$APPLY) return ++$sim;
    $ok = q($m, "INSERT INTO `$tabla` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")");
    return $ok ? $m->insert_id : 0;
}

// ── 1. mam_dispatches ────────────────────────────────────────────────────────
echo "\n[1] Despachos MAM\n";
$nuevosD = 0; $yaD = 0;
foreach ($json['despachos'] as $d) {
    $ex = q($m, "SELECT id FROM mam_dispatches WHERE numero_guia = " . (int)$d['numero_guia']);
    if ($ex && $ex->num_rows) { $yaD++; continue; }
    insert($m, $APPLY, 'mam_dispatches', array(
        'numero_guia' => (int)$d['numero_guia'],
        'factura_mam' => $d['factura_mam'],
        'fecha_despacho' => $d['fecha_despacho'],
        'cliente' => $d['cliente'],
        'destino' => $d['destino'],
        'transportadora' => $d['transportadora'],
        'cajas' => (int)$d['cajas'],
        'peso' => $d['peso'],
        'valor_factura' => $d['valor_factura'],
        'imported_filename' => $d['imported_filename'],
        'imported_by' => $USER,
        'imported_at' => date('Y-m-d H:i:s'),
    ));
    $nuevosD++;
}
echo "  nuevos: $nuevosD | ya estaban: $yaD\n";

// ── 2. Facturas de corte ─────────────────────────────────────────────────────
echo "\n[2] Facturas de corte Interrapidísimo\n";
foreach ($json['cortes'] as $corte) {
    $nf = $corte['numero_factura'];
    $ex = q($m, "SELECT id FROM contrapago_invoices WHERE numero_factura = " . esc($m, $nf));
    if ($ex && $ex->num_rows) { echo "  #$nf ya importada, se salta.\n"; continue; }
    $invoiceId = insert($m, $APPLY, 'contrapago_invoices', array(
        'numero_factura' => $nf,
        'fecha_corte' => $corte['fecha_corte'],
        'nit' => $corte['nit'],
        'razon_social' => $corte['razon_social'],
        'total_guias' => count($corte['items']),
        'valor_transporte' => $corte['valor_transporte'],
        'valor_seguro' => $corte['valor_seguro'],
        'valor_adicionales' => $corte['valor_adicionales'],
        'valor_total' => $corte['valor_total'],
        'status' => 'pendiente',
        'filename' => $corte['filename'],
        'created_by' => $USER,
        'created_at' => date('Y-m-d H:i:s'),
    ));
    $matched = 0; $fleteUpd = 0;
    foreach ($corte['items'] as $it) {
        $itemId = insert($m, $APPLY, 'contrapago_invoice_items', array(
            'invoice_id' => $invoiceId,
            'numero_guia' => $it['numero_guia'],
            'fecha_grabacion' => $it['fecha_grabacion'],
            'ciudad_origen' => $it['ciudad_origen'],
            'ciudad_destino' => $it['ciudad_destino'],
            'peso' => $it['peso'],
            'valor_comercial' => $it['valor_comercial'],
            'valor_adicionales' => $it['valor_adicionales'],
            'valor_transporte' => $it['valor_transporte'],
            'valor_prima' => $it['valor_prima'],
            'valor_total' => $it['valor_total'],
        ));
        // matchItems: cruzar con shipping_guides
        $g = q($m, "SELECT id, invoiceId, valorTotal, valorFlete FROM shipping_guides
                    WHERE numeroPreenvio = " . esc($m, $it['numero_guia']));
        if ($g && ($guide = $g->fetch_assoc())) {
            $matched++;
            if ($APPLY) {
                q($m, "UPDATE contrapago_invoice_items SET shipping_guide_id = {$guide['id']},
                       invoice_system_id = " . ((int)$guide['invoiceId'] ?: 'NULL') . ", company = 'ledxury'
                       WHERE id = $itemId");
                if (abs((float)$guide['valorTotal'] - (float)$it['valor_total']) > 0.01
                    || abs((float)$guide['valorFlete'] - (float)$it['valor_transporte']) > 0.01) {
                    q($m, "UPDATE shipping_guides SET valorFlete = {$it['valor_transporte']},
                           valorSeguro = {$it['valor_prima']}, valorTotal = {$it['valor_total']},
                           updated_at = NOW() WHERE id = {$guide['id']}");
                    $fleteUpd++;
                }
            }
        }
    }
    // autoTagInvoice: guias en mam_dispatches -> mam
    $tagged = 0;
    if ($APPLY) {
        q($m, "UPDATE contrapago_invoice_items cii
               JOIN mam_dispatches md ON CAST(cii.numero_guia AS UNSIGNED) = md.numero_guia
               SET cii.company = 'mam'
               WHERE cii.invoice_id = $invoiceId AND COALESCE(cii.company,'ledxury') <> 'mam'");
        $tagged = $m->affected_rows;
    } else {
        $lista = "'" . implode("','", array_column($corte['items'], 'numero_guia')) . "'";
        $r = q($m, "SELECT COUNT(*) n FROM mam_dispatches WHERE numero_guia IN ($lista)");
        $tagged = $r ? (int)$r->fetch_assoc()['n'] : 0;
    }
    printf("  #%s %s: %d guias, total %s | cruzadas ledxury: %d | tag mam: %d | fletes act.: %d\n",
        $nf, $corte['fecha_corte'], count($corte['items']),
        number_format($corte['valor_total'],0,',','.'), $matched, $tagged, $fleteUpd);
}

// ── 3. Lotes de pago ─────────────────────────────────────────────────────────
echo "\n[3] Lotes de pago contrapago\n";
// parseInvoiceReferences (copia fiel del modelo)
function parseRefs($obs) {
    if (empty($obs)) return array();
    $res = array();
    foreach (preg_split('/[\/;]/', $obs) as $seg) {
        $seg = trim($seg); if ($seg === '') continue;
        $fact = null;
        if (preg_match('/Fra\.?\s*#?\s*(\d{3,})/i', $seg, $x)) $fact = $x[1];
        elseif (preg_match('/Factura\s*#?\s*(\d{3,})/i', $seg, $x)) $fact = $x[1];
        $monto = 0;
        if (preg_match('/\$\s*([\d][\d\.,]*)/', $seg, $x))
            $monto = (float)str_replace(array('.', ','), array('', '.'), $x[1]);
        if ($fact && $monto > 0) $res[] = array('factura' => $fact, 'monto' => $monto);
    }
    return $res;
}
foreach ($json['lotes'] as $lote) {
    $guias = array_map('strval', array_column($lote['rows'], 'numeroGuia'));
    sort($guias, SORT_STRING);
    $hash = md5(implode(',', $guias));
    $ex = q($m, "SELECT id FROM contrapago_batches WHERE import_hash = '$hash'");
    if ($ex && $ex->num_rows) { echo "  {$lote['sheet_name']}: mismas guías que un lote existente, se salta.\n"; continue; }

    $batchId = insert($m, $APPLY, 'contrapago_batches', array(
        'filename' => $lote['filename'],
        'sheet_name' => $lote['sheet_name'],
        'total_guias' => count($lote['rows']),
        'total_valor' => $lote['total_valor'],
        'fecha_pago' => $lote['fecha_pago'],
        'banco' => $lote['banco'],
        'status' => 'importado',
        'created_by' => $USER,
        'import_hash' => $hash,
        'created_at' => date('Y-m-d H:i:s'),
    ));

    $matched = 0; $unmatched = 0; $dups = 0; $tagged = 0;
    foreach ($lote['rows'] as $row) {
        $payId = insert($m, $APPLY, 'contrapago_payments', array(
            'batch_id' => $batchId,
            'numeroGuia' => $row['numeroGuia'],
            'fechaVenta' => $row['fechaVenta'],
            'valorTotal' => $row['valorTotal'],
            'nombreDestinatario' => $row['nombreDestinatario'],
            'conciliacion' => $row['conciliacion'],
            'fechaPago' => $row['fechaPago'],
            'valorPago' => $row['valorPago'],
            'banco' => $row['banco'],
            'observacion' => $row['observacion'],
            'status' => 'pendiente',
            'created_at' => date('Y-m-d H:i:s'),
        ));
        // matchGuides: 1) duplicada en otro lote conciliado/registrado
        $d = q($m, "SELECT cp.id FROM contrapago_payments cp
                    JOIN contrapago_batches b ON b.id = cp.batch_id
                    WHERE cp.numeroGuia = " . esc($m, $row['numeroGuia']) . "
                      AND cp.id <> $payId AND cp.status IN ('conciliado','duplicada')
                      AND b.status IN ('conciliado','registrado')
                    ORDER BY cp.id ASC LIMIT 1");
        if ($d && ($dup = $d->fetch_assoc())) {
            if ($APPLY) q($m, "UPDATE contrapago_payments SET status='duplicada', duplicate_of_id={$dup['id']} WHERE id=$payId");
            $dups++; continue;
        }
        // 2) match con shipping_guides
        $g = q($m, "SELECT sg.id, sg.invoiceId FROM shipping_guides sg
                    WHERE sg.numeroPreenvio = " . esc($m, $row['numeroGuia']));
        if ($g && ($guide = $g->fetch_assoc())) {
            if ($APPLY) {
                q($m, "UPDATE contrapago_payments SET shipping_guide_id={$guide['id']},
                       invoice_id=" . ((int)$guide['invoiceId'] ?: 'NULL') . ",
                       company='ledxury', status='conciliado' WHERE id=$payId");
                if ($guide['invoiceId']) {
                    q($m, "UPDATE invoices SET tracking_number=" . esc($m, $row['numeroGuia']) . ",
                           tracking_carrier='interrapidisimo'
                           WHERE idInvoice={$guide['invoiceId']} AND (tracking_number IS NULL OR tracking_number='')");
                }
            }
            $matched++;
        } else {
            if ($APPLY) q($m, "UPDATE contrapago_payments SET status='sin_match', company='mam' WHERE id=$payId");
            $unmatched++;
        }
    }
    if ($APPLY) {
        q($m, "UPDATE contrapago_batches SET matched=$matched, unmatched=$unmatched, status='conciliado' WHERE id=$batchId");
        // autoTagBatch
        q($m, "UPDATE contrapago_payments cp
               JOIN mam_dispatches md ON CAST(cp.numeroGuia AS UNSIGNED) = md.numero_guia
               SET cp.company = 'mam'
               WHERE cp.batch_id = $batchId AND COALESCE(cp.company,'ledxury') <> 'mam'");
        $tagged = $m->affected_rows;
    } else {
        $lista = "'" . implode("','", array_column($lote['rows'], 'numeroGuia')) . "'";
        $r = q($m, "SELECT COUNT(*) n FROM mam_dispatches WHERE numero_guia IN ($lista)");
        $tagged = $r ? (int)$r->fetch_assoc()['n'] : 0;
    }
    printf("  %-8s pago %s $%12s: %3d guias | ledxury: %d | sin match: %d | dup: %d | tag mam: %d\n",
        $lote['sheet_name'], $lote['fecha_pago'], number_format($lote['total_valor'],0,',','.'),
        count($lote['rows']), $matched, $unmatched, $dups, $tagged);

    // linkBatchToInterInvoices: observaciones -> facturas de corte
    $obsVistas = array();
    foreach ($lote['rows'] as $row) {
        $obs = trim((string)$row['observacion']);
        if ($obs === '' || isset($obsVistas[$obs])) continue;
        $obsVistas[$obs] = 1;
        foreach (parseRefs($obs) as $ref) {
            $inv = q($m, "SELECT id, valor_total, descontada_en_batch_id FROM contrapago_invoices
                          WHERE numero_factura = " . esc($m, $ref['factura']));
            if (!$inv || !$inv->num_rows) {
                printf("     obs: factura #%s ($%s) NO importada aún — queda pendiente\n",
                    $ref['factura'], number_format($ref['monto'],0,',','.'));
                continue;
            }
            $invRow = $inv->fetch_assoc();
            if ($APPLY) {
                q($m, "INSERT INTO contrapago_invoice_payments (invoice_id, batch_id, monto_cobrado, texto_observacion, created_by)
                       VALUES ({$invRow['id']}, $batchId, {$ref['monto']}, " . esc($m, $obs) . ", '$USER')");
                $tc = q($m, "SELECT COALESCE(SUM(monto_cobrado),0) t FROM contrapago_invoice_payments WHERE invoice_id={$invRow['id']}")->fetch_assoc();
                $st = ((float)$tc['t'] >= (float)$invRow['valor_total'] - 0.01) ? 'descontada' : (((float)$tc['t'] > 0) ? 'parcial' : 'pendiente');
                $descEn = $st === 'descontada' ? $batchId : ((int)$invRow['descontada_en_batch_id'] ?: 0);
                q($m, "UPDATE contrapago_invoices SET status='$st', descontada_en_batch_id=" . ($descEn ?: 'NULL') . " WHERE id={$invRow['id']}");
                printf("     obs: factura #%s vinculada por $%s -> %s\n", $ref['factura'], number_format($ref['monto'],0,',','.'), $st);
            } else {
                printf("     obs: factura #%s ($%s) se vincularía\n", $ref['factura'], number_format($ref['monto'],0,',','.'));
            }
        }
    }
}
echo "\nListo. " . ($APPLY ? "Cambios aplicados." : "Nada se escribió (simulación).") . "\n";
