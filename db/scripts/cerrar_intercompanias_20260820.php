<?php
/**
 * Lleva a cero el tablero de intercompañías al 20/08/2026.
 *
 *   php cerrar_intercompanias_20260820.php            (simulación)
 *   php cerrar_intercompanias_20260820.php --apply
 *
 * POR QUÉ
 * `intercompany_movements` es un libro paralelo que alimenta
 * /sisvent/admin/contrapagos/entreCompanias. No se cruza con la contabilidad:
 * son dos registros que hay que mantener a mano. En el cierre del 20/08 la
 * cuenta contable 132505 (CxC a MAM y MAM-Online) se lleva a cero, así que el
 * tablero tiene que quedar igual o los dos se contradicen.
 *
 * QUÉ HACE
 * Marca como 'anulado' los movimientos activos, con una nota que dice por qué.
 * NO los borra: quedan como historia consultable, solo dejan de sumar al saldo.
 *
 * OJO — el tablero mezcla dos cosas de signo contrario, y por eso su "saldo" no
 * es comparable con 132505 sin desarmarlo:
 *   · concepto 'flete_mam': flete que Ledxury pagó por guías de MAM o
 *     MAM-Online. Eso sí es una cuenta por COBRAR (va en 132505).
 *   · concepto 'contrapago_mam': plata que Interrapidísimo consignó en la
 *     cuenta de Ledxury y que es de MAM-Online. Eso es lo contrario, una cuenta
 *     por PAGAR, y vive en 223005 — aunque en la tabla esté marcada como
 *     'mam_debe_ledxury', que es un error de clasificación de origen.
 * Se anulan los dos grupos porque el arranque es desde cero, pero queda dicho.
 *
 * Idempotente: si ya no hay movimientos activos, no hace nada.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n\n" : "=== SIMULACION (sin --apply no escribe nada) ===\n\n";

$USER = '71339095';
$errores = 0;
function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function mo($v) { return number_format((float)$v, 2, ',', '.'); }

echo "Estado actual del tablero:\n";
$total = 0;
foreach (rows($m, "SELECT partner_company, concepto, tipo, COUNT(*) n, COALESCE(SUM(monto),0) t
                   FROM intercompany_movements
                   WHERE deleted_at IS NULL AND status = 'activo'
                   GROUP BY partner_company, concepto, tipo
                   ORDER BY partner_company, concepto") as $x) {
    printf("  %-12s %-16s %-16s %4s mov  %16s\n", $x['partner_company'], $x['concepto'],
        $x['tipo'], $x['n'], mo($x['t']));
    $total += ($x['tipo'] === 'pago_recibido' ? -1 : 1) * (float)$x['t'];
}
echo "  saldo neto del tablero: " . mo($total) . "\n";

$act = rows($m, "SELECT COUNT(*) n FROM intercompany_movements
                 WHERE deleted_at IS NULL AND status = 'activo'");
$n = (int)$act[0]['n'];
if ($n === 0) { echo "\nNo hay movimientos activos. Nada por hacer.\n"; exit(0); }

echo "\nSe anulan {$n} movimiento(s). Quedan consultables como historia.\n";
$nota = 'Anulado en el cierre del 20/08/2026: la contabilidad arranca de cero y '
      . 'la cuenta 132505 (CxC a MAM y MAM-Online) quedo en cero, asi que el tablero '
      . 'de intercompanias se iguala. Los movimientos quedan como historia, no suman al saldo.';

if (!$APPLY) {
    echo "  [sim] UPDATE intercompany_movements SET status = 'anulado' ... ({$n} filas)\n";
    echo "\n=== FIN SIMULACION — nada se escribió ===\n";
    exit(0);
}

$m->begin_transaction();
$sql = "UPDATE intercompany_movements
        SET status = 'anulado',
            descripcion = CONCAT(COALESCE(descripcion,''), ' | ', '" . $m->real_escape_string($nota) . "'),
            updated_at = NOW()
        WHERE deleted_at IS NULL AND status = 'activo'";
if ($m->query($sql) === false) { echo "  ERROR: {$m->error}\n"; $m->rollback(); exit(1); }
echo "  -> {$m->affected_rows} fila(s) anuladas\n";
$m->commit();

echo "\n=== APLICADO — VERIFICACION ===\n";
foreach (rows($m, "SELECT status, COUNT(*) n, COALESCE(SUM(monto),0) t
                   FROM intercompany_movements WHERE deleted_at IS NULL
                   GROUP BY status") as $x)
    printf("  %-10s %4s mov  %16s\n", $x['status'], $x['n'], mo($x['t']));
$q = rows($m, "SELECT COUNT(*) n FROM intercompany_movements
               WHERE deleted_at IS NULL AND status = 'activo'");
echo "  movimientos activos: {$q[0]['n']} (debe ser 0)\n";
