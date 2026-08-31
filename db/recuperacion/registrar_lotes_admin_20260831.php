<?php
/**
 * RECUPERACIÓN — marca como 'registrado' los lotes PAGO 11-19 SIN los efectos
 * del registro normal (sin cash movement, sin asiento, sin causación de
 * comisiones), por orden de Alex: ese dinero y sus efectos quedarán
 * absorbidos por el nuevo balance inicial que se va a cargar.
 * cash_movement_id queda NULL a propósito: es la marca de que el lote se
 * cerró administrativamente y no contra el banco.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH','x'); define('ENVIRONMENT','production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n" : "=== SIMULACION ===\n";
$r = $m->query("SELECT id, sheet_name, fecha_pago, total_valor, status FROM contrapago_batches
                WHERE status = 'conciliado' ORDER BY id");
$ids = array(); $tot = 0;
while ($x = $r->fetch_assoc()) {
    printf("  lote #%s %-8s pago %s  %14s  %s -> registrado (admin, sin banco ni comision)\n",
        $x['id'], $x['sheet_name'], $x['fecha_pago'], number_format($x['total_valor'],0,',','.'), $x['status']);
    $ids[] = (int)$x['id']; $tot += $x['total_valor'];
}
if (!$ids) { echo "no hay lotes conciliados\n"; exit(0); }
if ($APPLY) {
    $m->query("UPDATE contrapago_batches SET status = 'registrado' WHERE id IN (" . implode(',', $ids) . ")");
    echo "\nactualizados: {$m->affected_rows} lotes\n";
}
printf("total: %d lotes por %s\n", count($ids), number_format($tot,0,',','.'));
