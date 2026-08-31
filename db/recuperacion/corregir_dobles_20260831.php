<?php
/**
 * Corrige las 3 guias asignadas a dos facturas: el nombreDestinatario del
 * pago de Interrapidisimo decide cual factura se queda la guia; a la otra se
 * le revierte el pago (queda abierta, sin tracking).
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH','x'); define('ENVIRONMENT','production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n" : "=== SIMULACION ===\n";

function norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = strtr($s, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'));
    return preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', '', $s));
}
function afinidad($a, $b) {
    $ta = array_filter(explode(' ', norm($a)), function ($t) { return strlen($t) > 2; });
    $tb = array_filter(explode(' ', norm($b)), function ($t) { return strlen($t) > 2; });
    $n = 0; foreach ($ta as $t) if (in_array($t, $tb, true)) $n++;
    return $n;
}

$r = $m->query("SELECT REGEXP_REPLACE(tracking_number,'[^0-9]','') g, GROUP_CONCAT(idInvoice) ids
                FROM invoices WHERE tracking_number IS NOT NULL AND tracking_number <> ''
                  AND (deleted IS NULL OR deleted = 0)
                GROUP BY 1 HAVING COUNT(*) > 1");
$casos = array();
while ($x = $r->fetch_assoc()) $casos[] = $x;
if (!$casos) { echo "sin dobles\n"; exit(0); }

if ($APPLY) $m->begin_transaction();
foreach ($casos as $caso) {
    $g = $caso['g'];
    $dest = $m->query("SELECT nombreDestinatario FROM contrapago_payments
                       WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '$g'
                         AND nombreDestinatario IS NOT NULL AND nombreDestinatario <> '' LIMIT 1")->fetch_assoc();
    $destN = $dest ? $dest['nombreDestinatario'] : '';
    $ids = explode(',', $caso['ids']);
    $mejor = null; $mejorAf = -1;
    $facts = array();
    foreach ($ids as $id) {
        $f = $m->query("SELECT i.idInvoice, c.name cliente FROM invoices i JOIN clients c ON c.idClient=i.clientId WHERE i.idInvoice = " . (int)$id)->fetch_assoc();
        $f['af'] = afinidad($f['cliente'], $destN);
        $facts[] = $f;
        if ($f['af'] > $mejorAf) { $mejorAf = $f['af']; $mejor = (int)$f['idInvoice']; }
    }
    printf("guia %s (destinatario del pago: %s)\n", $g, $destN);
    foreach ($facts as $f) printf("   #%s %-30s afinidad %d %s\n", $f['idInvoice'], $f['cliente'], $f['af'],
        (int)$f['idInvoice'] === $mejor ? '<- SE QUEDA LA GUIA' : '-> REVERTIR');
    if ($mejorAf < 1) { echo "   OJO: ninguna coincide por nombre; se conserva la de menor id y se revierte la otra\n"; $mejor = min(array_map('intval', $ids)); }
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id === $mejor) continue;
        if ($APPLY) {
            $m->query("UPDATE payments SET deleted = 1, deleted_at = NOW(),
                       comments = CONCAT(COALESCE(comments,''), ' [REVERTIDO: guia $g pertenece a otra factura]')
                       WHERE invoiceId = $id AND deleted = 0 AND comments LIKE '%$g%'");
            $m->query("UPDATE invoices SET payment = 0, state = 0, tracking_number = NULL, tracking_carrier = NULL, updated_at = NOW()
                       WHERE idInvoice = $id");
            $m->query("UPDATE contrapago_payments SET invoice_id = NULL
                       WHERE REGEXP_REPLACE(numeroGuia,'[^0-9]','') = '$g' AND invoice_id = $id");
        }
        echo "   revertida #$id (pago anulado, vuelve a cartera abierta, sin guia)\n";
    }
}
if ($APPLY) { $m->commit(); echo "\naplicado\n"; }
