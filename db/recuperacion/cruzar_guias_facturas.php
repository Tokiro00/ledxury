<?php
/**
 * RECUPERACIÓN — cruza las guías huérfanas con las facturas recuperadas
 * por FECHA + VALOR (idea de Alex: el valor declarado del corte es el valor
 * de la venta, y el despacho sale el mismo día o muy cerca).
 *
 * Solo enlaza cruces ÚNICOS (una guía candidata para una factura y viceversa)
 * para no casar mal: los ambiguos se listan para revisión manual.
 *
 * Al enlazar: invoices.tracking_number = guía, y la guía queda marcada
 * company='ledxury' en contrapagos/cortes (es una venta nuestra).
 *
 *   php cruzar_guias_facturas.php            (simulación)
 *   php cruzar_guias_facturas.php --apply
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

$VENTANA_DIAS = 3;

// Facturas sin guía (las recuperadas y cualquier otra abierta sin tracking)
$facturas = array();
$r = $m->query("
    SELECT i.idInvoice, i.date, i.total, c.name AS cliente
    FROM invoices i
    LEFT JOIN clients c ON c.idClient = i.clientId
    WHERE (i.tracking_number IS NULL OR i.tracking_number = '')
      AND (i.deleted IS NULL OR i.deleted = 0)
      AND i.date >= '2026-06-19'
    ORDER BY i.date");
while ($x = $r->fetch_assoc()) $facturas[] = $x;

// Guías huérfanas con lo que sabemos (valor cobrado del contrapago o valor
// declarado del corte, y su fecha)
$guias = array();
$r = $m->query("
    SELECT cp.numeroGuia AS guia, DATE(cp.fechaVenta) AS fecha,
           cp.valorTotal AS valor, cp.nombreDestinatario AS nombre, 'pago' AS fuente
    FROM contrapago_payments cp
    WHERE cp.shipping_guide_id IS NULL AND cp.status <> 'duplicada'");
while ($x = $r->fetch_assoc()) { $x['guia'] = preg_replace('/[^0-9]/', '', $x['guia']); $guias[] = $x; }
$r = $m->query("
    SELECT cii.numero_guia AS guia, DATE(cii.fecha_grabacion) AS fecha,
           cii.valor_comercial AS valor, cii.ciudad_destino AS nombre, 'corte' AS fuente
    FROM contrapago_invoice_items cii
    WHERE cii.shipping_guide_id IS NULL");
while ($x = $r->fetch_assoc()) { $x['guia'] = preg_replace('/[^0-9]/', '', $x['guia']); $guias[] = $x; }

// dedupe guías (una puede venir de pago y de corte; preferir la de pago por el nombre)
$porGuia = array();
foreach ($guias as $g) {
    if (!isset($porGuia[$g['guia']]) || $g['fuente'] === 'pago') $porGuia[$g['guia']] = $g;
}
$guias = array_values($porGuia);

printf("facturas sin guía (desde 19/06): %d | guías huérfanas: %d | ventana: ±%d días\n\n",
    count($facturas), count($guias), $VENTANA_DIAS);

// Candidatos por factura: mismo valor y fecha dentro de la ventana
$candPorFactura = array(); $candPorGuia = array();
foreach ($facturas as $fi => $f) {
    $fFecha = strtotime(substr($f['date'], 0, 10));
    foreach ($guias as $gi => $g) {
        if ((float)$g['valor'] != (float)$f['total']) continue;
        if (!$g['fecha']) continue;
        $dias = abs(strtotime($g['fecha']) - $fFecha) / 86400;
        if ($dias > $VENTANA_DIAS) continue;
        $candPorFactura[$fi][] = $gi;
        $candPorGuia[$gi][] = $fi;
    }
}

// Normaliza nombres para comparar (minúsculas, sin tildes, espacios simples)
function norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = strtr($s, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'));
    return preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', '', $s));
}
// ¿El nombre del destinatario y el del cliente son la misma persona?
// Igualdad normalizada, o todos los tokens (>2 letras) del más corto
// presentes en el más largo.
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

$enlazados = 0; $ambiguos = 0; $usadas = array();
foreach ($candPorFactura as $fi => $gis) {
    $gis = array_values(array_filter($gis, function ($gi) use ($usadas) { return !isset($usadas[$gi]); }));
    if (!$gis) { $ambiguos++; continue; }
    // 1) Por NOMBRE del destinatario (los pagos de contrapago lo traen)
    $porNombre = array_values(array_filter($gis, function ($gi) use ($guias, $facturas, $fi) {
        return mismoNombre($guias[$gi]['nombre'], $facturas[$fi]['cliente']);
    }));
    if (count($porNombre) >= 1) {
        $g = $guias[$porNombre[0]];
        $f = $facturas[$fi];
        $usadas[$porNombre[0]] = true;
        $facturas[$fi]["_enlazada"] = true;
        printf("  ENLAZA-N #%06d %s %-26s %10s <- guía %s (%s, destinatario: %s)\n",
            $f['idInvoice'], substr($f['date'], 0, 10), mb_substr($f['cliente'], 0, 26),
            number_format($f['total'], 0, ',', '.'), $g['guia'], $g['fecha'], mb_substr($g['nombre'], 0, 26));
        if ($APPLY) {
            $m->query("UPDATE invoices SET tracking_number = '{$g['guia']}', tracking_carrier = 'interrapidisimo'
                       WHERE idInvoice = {$f['idInvoice']}");
            $m->query("UPDATE contrapago_payments SET company = 'ledxury', invoice_id = {$f['idInvoice']}
                       WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '{$g['guia']}' AND shipping_guide_id IS NULL");
            $m->query("UPDATE contrapago_invoice_items SET company = 'ledxury', invoice_system_id = {$f['idInvoice']}
                       WHERE REGEXP_REPLACE(numero_guia,'[^0-9]','') = '{$g['guia']}' AND shipping_guide_id IS NULL");
        }
        $enlazados++;
        continue;
    }
    // 2) Único en ambos sentidos (valor + fecha)
    $unicos = array_values(array_filter($gis, function ($gi) use ($candPorGuia) {
        return count($candPorGuia[$gi]) === 1;
    }));
    if (count($unicos) !== 1) {
        $ambiguos++;
        $f = $facturas[$fi];
        printf("  AMBIGUA  #%06d %s %-26s %10s -> %d guías candidatas\n",
            $f['idInvoice'], substr($f['date'], 0, 10), mb_substr($f['cliente'], 0, 26),
            number_format($f['total'], 0, ',', '.'), count($gis));
        continue;
    }
    $g = $guias[$unicos[0]];
    $f = $facturas[$fi];
    $usadas[$unicos[0]] = true;
    $facturas[$fi]["_enlazada"] = true;
    printf("  ENLAZA   #%06d %s %-26s %10s <- guía %s (%s %s)\n",
        $f['idInvoice'], substr($f['date'], 0, 10), mb_substr($f['cliente'], 0, 26),
        number_format($f['total'], 0, ',', '.'), $g['guia'], $g['fuente'], $g['fecha']);
    if ($APPLY) {
        $m->query("UPDATE invoices SET tracking_number = '{$g['guia']}', tracking_carrier = 'interrapidisimo'
                   WHERE idInvoice = {$f['idInvoice']}");
        $m->query("UPDATE contrapago_payments SET company = 'ledxury', invoice_id = {$f['idInvoice']}
                   WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '{$g['guia']}' AND shipping_guide_id IS NULL");
        $m->query("UPDATE contrapago_invoice_items SET company = 'ledxury', invoice_system_id = {$f['idInvoice']}
                   WHERE REGEXP_REPLACE(numero_guia,'[^0-9]','') = '{$g['guia']}' AND shipping_guide_id IS NULL");
    }
    $enlazados++;
}

// ── Segunda pasada: solo por NOMBRE (valor puede diferir por el flete),
// ventana amplia de ±15 días, y únicamente si hay UNA guía con ese nombre.
$enlazados2 = 0;
$facturasEnlazadas = array();
$r = $m->query("SELECT idInvoice FROM invoices WHERE tracking_number IS NOT NULL AND tracking_number <> ''");
while ($x = $r->fetch_assoc()) $facturasEnlazadas[(int)$x['idInvoice']] = true;

echo "\n── Segunda pasada (solo nombre, ±15 días) ──\n";
foreach ($facturas as $fi => $f) {
    // saltar las ya enlazadas en esta corrida
    if (isset($f['_enlazada'])) continue;
    // ¿ya tiene tracking (enlazada en pasada 1 con --apply, o antes)?
    if (isset($facturasEnlazadas[(int)$f['idInvoice']])) continue;

    $fFecha = strtotime(substr($f['date'], 0, 10));
    $cands = array();
    foreach ($guias as $gi => $g) {
        if (isset($usadas[$gi])) continue;
        if ($g['fuente'] !== 'pago') continue;             // solo pagos traen nombre de persona
        if (!$g['fecha']) continue;
        if (abs(strtotime($g['fecha']) - $fFecha) / 86400 > 15) continue;
        if ((float)$g['valor'] != (float)$f['total']) continue; // el valor debe cuadrar: nombre solo no basta
        if (!mismoNombre($g['nombre'], $f['cliente'])) continue;
        $cands[] = $gi;
    }
    if (count($cands) !== 1) continue;
    $g = $guias[$cands[0]];
    $usadas[$cands[0]] = true;
    $facturas[$fi]['_enlazada'] = true;
    printf("  ENLAZA-2 #%06d %s %-26s fact %10s / guía %10s <- %s (%s, %s)\n",
        $f['idInvoice'], substr($f['date'], 0, 10), mb_substr($f['cliente'], 0, 26),
        number_format($f['total'], 0, ',', '.'), number_format($g['valor'], 0, ',', '.'),
        $g['guia'], $g['fecha'], mb_substr($g['nombre'], 0, 24));
    if ($APPLY) {
        $m->query("UPDATE invoices SET tracking_number = '{$g['guia']}', tracking_carrier = 'interrapidisimo'
                   WHERE idInvoice = {$f['idInvoice']}");
        $m->query("UPDATE contrapago_payments SET company = 'ledxury', invoice_id = {$f['idInvoice']}
                   WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '{$g['guia']}' AND shipping_guide_id IS NULL");
        $m->query("UPDATE contrapago_invoice_items SET company = 'ledxury', invoice_system_id = {$f['idInvoice']}
                   WHERE REGEXP_REPLACE(numero_guia,'[^0-9]','') = '{$g['guia']}' AND shipping_guide_id IS NULL");
    }
    $enlazados2++;
}

printf("\nenlazadas 1ra pasada: %d | 2da pasada (nombre): %d | ambiguas: %d\n",
    $enlazados, $enlazados2, $ambiguos);
echo $APPLY ? "Cambios aplicados.\n" : "Nada se escribió (simulación).\n";
