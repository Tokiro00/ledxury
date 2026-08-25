<?php
/**
 * RECUPERACIÓN — cartera de Barranquilla al 24/07/2026.
 *
 * Recrea las facturas del exporte facturas_pendientes_todas_2026-07-24.xlsx
 * que se perdieron con la instancia (posteriores al respaldo del 18/06),
 * como facturas SIN detalle de productos (el archivo solo trae encabezados;
 * no hay otra fuente). Conserva el número original (idInvoice), la fecha,
 * el cliente (lo crea si no existe, cruzando por cédula y luego por nombre)
 * y el total. Vendedora: Germam Maria Barranquilla (1048937562).
 *
 *   php importar_cartera_20260724.php            (simulación)
 *   php importar_cartera_20260724.php --apply
 *
 * Idempotente: si el idInvoice ya existe, se salta.
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

$VENDEDORA = '1048937562';   // Germam Maria Barranquilla
$UID       = '71339095';
$NOTA      = '[RECUPERADA 25/08/2026] Recreada del exporte de cartera del 24/07 (instancia perdida el 23/08). Sin detalle de productos: el archivo solo traia encabezados.';

$rows = json_decode(file_get_contents('/tmp/cartera_20260724.json'), true);
if (!$rows) { fwrite(STDERR, "no pude leer /tmp/cartera_20260724.json\n"); exit(1); }

function esc($m, $v) { return $v === null ? 'NULL' : "'" . $m->real_escape_string($v) . "'"; }

// ¿La tabla lleva tenant_id?
$hayTenantInv = $m->query("SHOW COLUMNS FROM invoices LIKE 'tenant_id'")->num_rows > 0;
$hayTenantCli = $m->query("SHOW COLUMNS FROM clients LIKE 'tenant_id'")->num_rows > 0;

// Columnas de clients con NOT NULL sin default (para crear clientes sin
// tropezar con STRICT_TRANS_TABLES).
$cliCols = array();
$r = $m->query("SHOW COLUMNS FROM clients");
while ($x = $r->fetch_assoc()) $cliCols[$x['Field']] = $x;

function crearCliente($m, $APPLY, $cliCols, $hayTenantCli, $nombre, $nit, $vendedora, $uid) {
    $data = array(
        'name' => $nombre,
        'idNum' => $nit,
        'vendor' => $vendedora,
        'created_by' => $uid,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'deleted' => 0,
    );
    if ($hayTenantCli) $data['tenant_id'] = 1;
    // Rellenar NOT NULL sin default que no estemos seteando
    foreach ($cliCols as $col => $def) {
        if (isset($data[$col]) || $col === 'idClient') continue;
        if ($def['Null'] === 'NO' && $def['Default'] === null && stripos($def['Extra'], 'auto_increment') === false) {
            $data[$col] = (stripos($def['Type'], 'int') !== false || stripos($def['Type'], 'decimal') !== false) ? 0 : '';
        }
    }
    if (!$APPLY) return -1;
    $cols = array(); $vals = array();
    foreach ($data as $k => $v) { $cols[] = "`$k`"; $vals[] = is_int($v) || is_float($v) ? (string)$v : esc($m, $v); }
    if (!$m->query("INSERT INTO clients (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")")) {
        echo "  ERROR creando cliente $nombre: {$m->error}\n"; return 0;
    }
    return $m->insert_id;
}

// Columnas reales de budgets: la FK invoices.budgetId exige un presupuesto
// por factura, así que se crea uno stub por cada una.
$budCols = array();
$r = $m->query("SHOW COLUMNS FROM budgets");
while ($x = $r->fetch_assoc()) $budCols[$x['Field']] = $x;

function crearBudgetStub($m, $APPLY, $budCols, $clientId, $vendedora, $fecha, $total, $nota, $uid) {
    $data = array('total' => (int)round($total), 'comments' => $nota);
    $opc = array(
        'clientId' => $clientId, 'vendorId' => $vendedora, 'storeId' => 1,
        'date' => $fecha, 'state' => 4, 'deleted' => 0,
        'created_by' => $uid, 'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'), 'tenant_id' => 1,
    );
    foreach ($opc as $k => $v) if (isset($budCols[$k])) $data[$k] = $v;
    foreach ($budCols as $col => $def) {
        if (isset($data[$col]) || $col === 'idBudget') continue;
        if ($def['Null'] === 'NO' && $def['Default'] === null && stripos($def['Extra'], 'auto_increment') === false) {
            $data[$col] = (stripos($def['Type'], 'int') !== false || stripos($def['Type'], 'decimal') !== false) ? 0 : '';
        }
    }
    if (!$APPLY) return -1;
    $cols = array(); $vals = array();
    foreach ($data as $k => $v) { $cols[] = "`$k`"; $vals[] = is_int($v) || is_float($v) ? (string)$v : esc($m, $v); }
    if (!$m->query("INSERT INTO budgets (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")")) {
        echo "  ERROR creando budget stub: {$m->error}\n"; return 0;
    }
    return $m->insert_id;
}

$creadas = 0; $saltadas = 0; $clientesNuevos = 0; $sumaCreadas = 0;
if ($APPLY) $m->begin_transaction();

foreach ($rows as $f) {
    $num = (int)$f['num'];
    $ex = $m->query("SELECT idInvoice FROM invoices WHERE idInvoice = $num");
    if ($ex && $ex->num_rows) { $saltadas++; continue; }

    // Cliente: por cédula, luego por nombre
    $nit = preg_replace('/[^0-9]/', '', (string)$f['nit']);
    $clientId = null;
    if ($nit !== '') {
        $q = $m->query("SELECT idClient FROM clients WHERE deleted = 0 AND REGEXP_REPLACE(COALESCE(idNum,''),'[^0-9]','') = '" . $m->real_escape_string($nit) . "' LIMIT 1");
        if ($q && ($x = $q->fetch_assoc())) $clientId = (int)$x['idClient'];
    }
    if (!$clientId) {
        $q = $m->query("SELECT idClient FROM clients WHERE deleted = 0 AND LOWER(TRIM(name)) = LOWER(" . esc($m, trim($f['cliente'])) . ") LIMIT 1");
        if ($q && ($x = $q->fetch_assoc())) $clientId = (int)$x['idClient'];
    }
    $cliNuevo = '';
    if (!$clientId) {
        $clientId = crearCliente($m, $APPLY, $cliCols, $hayTenantCli, trim($f['cliente']), $nit, $VENDEDORA, $UID);
        if ($APPLY && !$clientId) { $m->rollback(); exit(1); }
        $clientesNuevos++;
        $cliNuevo = ' [cliente NUEVO]';
    }

    $fecha = $f['fecha'] ?: '2026-07-24 00:00:00';
    $budgetId = crearBudgetStub($m, $APPLY, $budCols, $clientId, $VENDEDORA, $fecha, $f['total'], $NOTA, $UID);
    if ($APPLY && !$budgetId) { $m->rollback(); exit(1); }
    if (!$APPLY) $budgetId = 0;
    $tCol = $hayTenantInv ? 'tenant_id, ' : '';
    $tVal = $hayTenantInv ? '1, ' : '';
    $sql = "INSERT INTO invoices
        (idInvoice, {$tCol}if_id, budgetId, clientId, vendorId, storeId, delivery_type,
         payment, discount, discount_perc, total, hasIva, iva, e_commerce, comments,
         date, state, settled, printed, legal_collection, blacklisted, list_price,
         check_delivery, close_to_expire, is_expired, transportadora,
         created_by, created_at, updated_at, deleted)
        VALUES ($num, {$tVal}0, $budgetId, $clientId, '$VENDEDORA', 1, 1,
         " . (float)$f['pagado'] . ", 0, 10, " . (float)$f['total'] . ", 0, 8, 0, " . esc($m, $NOTA) . ",
         " . esc($m, $fecha) . ", 0, 0, 1, 0, 0, 0,
         0, 0, 0, 'interrapidisimo',
         '$UID', NOW(), NOW(), 0)";
    if ($APPLY) {
        if (!$m->query($sql)) { echo "  ERROR #$num: {$m->error}\n"; $m->rollback(); exit(1); }
    }
    printf("  #%06d %s %-30s %12s%s\n", $num, substr($fecha, 0, 10),
        mb_substr($f['cliente'], 0, 30), number_format($f['total'], 0, ',', '.'), $cliNuevo);
    $creadas++;
    $sumaCreadas += (float)$f['total'];
}

if ($APPLY) $m->commit();

printf("\nfacturas creadas: %d por %s | ya existian: %d | clientes nuevos: %d\n",
    $creadas, number_format($sumaCreadas, 0, ',', '.'), $saltadas, $clientesNuevos);

if ($APPLY) {
    $x = $m->query("
        SELECT COALESCE(SUM(i.total - COALESCE(p.pagado,0)),0) saldo, COUNT(*) n
        FROM invoices i
        LEFT JOIN (SELECT invoiceId, SUM(payment) pagado FROM payments WHERE deleted=0 GROUP BY invoiceId) p
               ON p.invoiceId = i.idInvoice
        WHERE i.state = 0 AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)")->fetch_assoc();
    printf("cartera operativa ahora: %s en %d facturas abiertas (al 20/08 era 16.565.810)\n",
        number_format($x['saldo'], 2, ',', '.'), $x['n']);
}
