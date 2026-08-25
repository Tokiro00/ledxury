<?php
/**
 * RECUPERACIÓN — la cartera de Barranquilla es contraentrega: si la guía de la
 * factura aparece en el histórico de pagos de Interrapidísimo (las 17 hojas ya
 * importadas), la factura YA ESTÁ PAGA. Este script:
 *
 *  1. Toma todas las facturas abiertas (state 0/1) desde abril.
 *  2. Resuelve su guía: la enlazada (tracking_number) o, si no tiene, cruce
 *     codicioso contra guías de pago sin usar (mismo valor, ±5 días,
 *     prefiriendo nombre del destinatario y fecha más cercana).
 *  3. Si la guía está en el histórico de pagos → registra el pago (método 5,
 *     fecha real del lote) y marca la factura Pagada (state 2).
 *     SIN movimiento de tesorería ni asiento: es del periodo perdido y la
 *     apertura del 20/08 ya lo absorbe en el saldo del banco.
 *  4. Si la guía fue DEVOLUCIÓN, no la paga: la reporta para anular.
 *
 *   php marcar_pagadas_interrapidisimo.php            (simulación)
 *   php marcar_pagadas_interrapidisimo.php --apply
 *
 * Idempotente: solo toca facturas abiertas sin pagos previos de este tipo.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n" : "=== SIMULACION (sin --apply no escribe nada) ===\n";

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

// Histórico COMPLETO de pagos por guía (fecha del lote y nombre del lote)
$pagosPorGuia = array();
$r = $m->query("
    SELECT REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','') g,
           MAX(b.fecha_pago) fp, MAX(b.sheet_name) lote,
           MAX(cp.valorTotal) valor, MAX(DATE(cp.fechaVenta)) fventa,
           MAX(cp.nombreDestinatario) nombre
    FROM contrapago_payments cp
    JOIN contrapago_batches b ON b.id = cp.batch_id
    WHERE cp.status <> 'duplicada'
    GROUP BY REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','')");
while ($x = $r->fetch_assoc()) if ($x['g'] !== '') $pagosPorGuia[$x['g']] = $x;

// Devoluciones detectadas por el API
$devoluciones = array();
$r = $m->query("SELECT numero_guia, fecha_ultimo_estado FROM guide_recovery WHERE estado_actual LIKE '%evol%'");
while ($x = $r->fetch_assoc()) $devoluciones[$x['numero_guia']] = $x['fecha_ultimo_estado'];

// Guías de pago ya usadas por alguna factura (tracking) — no reusarlas
$usadas = array();
$r = $m->query("SELECT REGEXP_REPLACE(tracking_number,'[^0-9]','') g FROM invoices
                WHERE tracking_number IS NOT NULL AND tracking_number <> '' AND (deleted IS NULL OR deleted = 0)");
while ($x = $r->fetch_assoc()) if ($x['g'] !== '') $usadas[$x['g']] = true;

// Facturas abiertas
$facturas = array();
$r = $m->query("
    SELECT i.idInvoice, i.date, i.total, i.payment, i.clientId, i.vendorId,
           REGEXP_REPLACE(COALESCE(i.tracking_number,''),'[^0-9]','') guia,
           c.name AS cliente
    FROM invoices i
    LEFT JOIN clients c ON c.idClient = i.clientId
    WHERE i.state IN (0,1) AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)
      AND i.date >= '2026-04-01'
    ORDER BY i.date");
while ($x = $r->fetch_assoc()) $facturas[] = $x;
echo "facturas abiertas desde abril: " . count($facturas) . "\n\n";

$hayTenantPay = $m->query("SHOW COLUMNS FROM payments LIKE 'tenant_id'")->num_rows > 0;

$pagadas = 0; $sumaPagada = 0; $devueltasN = 0; $sinCruce = 0;
if ($APPLY) $m->begin_transaction();

foreach ($facturas as $f) {
    $guia = $f['guia'];

    // Sin guía: cruce codicioso contra guías de pago libres (mismo valor, ±5 días)
    if ($guia === '' || !isset($pagosPorGuia[$guia])) {
        $fFecha = strtotime(substr($f['date'], 0, 10));
        $mejor = null; $mejorDist = 99; $mejorNombre = false;
        foreach ($pagosPorGuia as $g => $p) {
            if (isset($usadas[$g])) continue;
            if ((float)$p['valor'] != (float)$f['total']) continue;
            if (!$p['fventa']) continue;
            $dist = abs(strtotime($p['fventa']) - $fFecha) / 86400;
            if ($dist > 5) continue;
            $nom = mismoNombre($p['nombre'], $f['cliente']);
            // preferir nombre coincidente; a igual condición, la fecha más cercana
            if ($mejor === null || ($nom && !$mejorNombre) || ($nom === $mejorNombre && $dist < $mejorDist)) {
                $mejor = $g; $mejorDist = $dist; $mejorNombre = $nom;
            }
        }
        if ($mejor === null) { $sinCruce++; continue; }
        $guia = $mejor;
    }

    $p = $pagosPorGuia[$guia];
    $usadas[$guia] = true;

    // ¿Devolución? No se paga: se reporta.
    if (isset($devoluciones[$guia])) {
        $devueltasN++;
        printf("  DEVUELTA #%06d %s %-24s %10s guía %s devuelta el %s -> revisar/anular\n",
            $f['idInvoice'], substr($f['date'], 0, 10), mb_substr($f['cliente'], 0, 24),
            number_format($f['total'], 0, ',', '.'), $guia, substr((string)$devoluciones[$guia], 0, 10));
        continue;
    }

    $fechaPago = $p['fp'] ? $p['fp'] . ' 12:00:00' : date('Y-m-d H:i:s');
    printf("  PAGADA   #%06d %s %-24s %10s guía %s pagada el %s (%s)\n",
        $f['idInvoice'], substr($f['date'], 0, 10), mb_substr($f['cliente'], 0, 24),
        number_format($f['total'], 0, ',', '.'), $guia, $p['fp'], $p['lote']);

    if ($APPLY) {
        $coment = $m->real_escape_string("Pago contrapago Interrapidísimo - Guía $guia - Lote {$p['lote']} [RECUPERADO 25/08/2026: pago del periodo perdido; sin movimiento de tesorería, absorbido por la apertura del 20/08]");
        $tCol = $hayTenantPay ? 'tenant_id, ' : '';
        $tVal = $hayTenantPay ? '1, ' : '';
        $ok = $m->query("INSERT INTO payments ({$tCol}invoiceId, clientId, vendorId, paymentMethod, payment, comments,
                date, originType, originId, cashMovementId, deleted, created_at, updated_at)
            VALUES ({$tVal}{$f['idInvoice']}, {$f['clientId']}, '" . $m->real_escape_string($f['vendorId']) . "', 5,
                " . (float)$f['total'] . ", '$coment', '$fechaPago', 'banco', 1, NULL, 0, NOW(), NOW())");
        if (!$ok) { echo "  ERROR pago #{$f['idInvoice']}: {$m->error}\n"; $m->rollback(); exit(1); }
        $ok = $m->query("UPDATE invoices SET payment = " . (float)$f['total'] . ", state = 2,
                tracking_number = COALESCE(NULLIF(tracking_number,''), '$guia'), tracking_carrier = 'interrapidisimo',
                updated_at = '$fechaPago'
            WHERE idInvoice = {$f['idInvoice']}");
        if (!$ok) { echo "  ERROR factura #{$f['idInvoice']}: {$m->error}\n"; $m->rollback(); exit(1); }
        // la guía queda de nuestra empresa y apuntando a la factura
        $m->query("UPDATE contrapago_payments SET company = 'ledxury', invoice_id = {$f['idInvoice']}
                   WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '$guia'");
        $m->query("UPDATE contrapago_invoice_items SET company = 'ledxury', invoice_system_id = {$f['idInvoice']}
                   WHERE REGEXP_REPLACE(numero_guia,'[^0-9]','') = '$guia' AND shipping_guide_id IS NULL");
    }
    $pagadas++;
    $sumaPagada += (float)$f['total'];
}

if ($APPLY) $m->commit();

printf("\nmarcadas PAGADAS: %d por %s | con guía DEVUELTA (revisar): %d | sin cruce (cartera real): %d\n",
    $pagadas, number_format($sumaPagada, 0, ',', '.'), $devueltasN, $sinCruce);

$x = $m->query("
    SELECT COALESCE(SUM(i.total - COALESCE(p.pagado,0)),0) saldo, COUNT(*) n
    FROM invoices i
    LEFT JOIN (SELECT invoiceId, SUM(payment) pagado FROM payments WHERE deleted=0 GROUP BY invoiceId) p
           ON p.invoiceId = i.idInvoice
    WHERE i.state IN (0,1) AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)")->fetch_assoc();
printf("cartera abierta %s: %s en %d facturas (al 20/08 el real era 16.565.810)\n",
    $APPLY ? 'ahora' : 'quedaría en (tras aplicar)',
    number_format($x['saldo'], 2, ',', '.'), $x['n']);
