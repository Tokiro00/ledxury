<?php
/**
 * RECUPERACIÓN — anula las facturas abiertas cuya guía terminó DEVUELTA
 * (desenlace resuelto con el historial de Interrapidísimo, Cron::resolveArchived).
 * El cliente nunca recibió: no es cartera cobrable.
 *
 *  - state = 3 (Anulada) — sale de cartera pero queda el registro
 *  - sus asientos (invoice + cost_of_sales) quedan deleted = 1 (convención del sistema)
 *  - NO toca inventario: la mercancía devuelta ya está contenida en el conteo
 *    físico del 27/07 que se cargará como inventario inicial
 *
 *   php anular_devueltas_20260825.php            (simulación)
 *   php anular_devueltas_20260825.php --apply
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

$r = $m->query("
    SELECT i.idInvoice, DATE(i.date) f, c.name cliente, i.total, sg.numeroPreenvio guia
    FROM invoices i
    JOIN clients c ON c.idClient = i.clientId
    JOIN shipping_guides sg ON sg.invoiceId = i.idInvoice AND sg.outcome = 'devuelto'
    WHERE i.state IN (0,1) AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)
    GROUP BY i.idInvoice
    ORDER BY i.date");
$facturas = array();
while ($x = $r->fetch_assoc()) $facturas[] = $x;
echo "facturas abiertas con guía devuelta: " . count($facturas) . "\n\n";

$nota = " [ANULADA 25/08/2026: contraentrega DEVUELTA por Interrapidisimo - desenlace resuelto en la recuperacion; la mercancia vuelve via conteo fisico]";
$total = 0; $asientos = 0;
if ($APPLY) $m->begin_transaction();
foreach ($facturas as $f) {
    printf("  ANULA #%06d %s %-28s %10s guía %s\n", $f['idInvoice'], $f['f'],
        mb_substr($f['cliente'], 0, 28), number_format($f['total'], 0, ',', '.'), $f['guia']);
    if ($APPLY) {
        $ok = $m->query("UPDATE invoices SET state = 3,
            comments = CONCAT(COALESCE(comments,''), '" . $m->real_escape_string($nota) . "'),
            updated_at = NOW()
            WHERE idInvoice = {$f['idInvoice']}");
        if (!$ok) { echo "ERROR: {$m->error}\n"; $m->rollback(); exit(1); }
        $m->query("UPDATE entries SET deleted = 1
            WHERE deleted = 0 AND entryTransactionType IN ('invoice','cost_of_sales')
              AND entryTransactionId = {$f['idInvoice']}");
        $asientos += $m->affected_rows;
    }
    $total += (float)$f['total'];
}
if ($APPLY) $m->commit();

printf("\nanuladas: %d por %s | asientos anulados: %d\n", count($facturas), number_format($total, 0, ',', '.'), $asientos);
$x = $m->query("
    SELECT COALESCE(SUM(i.total - COALESCE(p.pagado,0)),0) saldo, COUNT(*) n
    FROM invoices i
    LEFT JOIN (SELECT invoiceId, SUM(payment) pagado FROM payments WHERE deleted=0 GROUP BY invoiceId) p
           ON p.invoiceId = i.idInvoice
    WHERE i.state IN (0,1) AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)")->fetch_assoc();
printf("cartera abierta %s: %s en %d facturas\n", $APPLY ? 'ahora' : 'quedaría en',
    number_format($x['saldo'], 2, ',', '.'), $x['n']);
