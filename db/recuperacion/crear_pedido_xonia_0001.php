<?php
/**
 * Sube la venta del bot Xonia como presupuesto, serie XONIA-#### en las
 * observaciones (el numero de presupuesto es consecutivo compartido de la
 * tabla; forzar un rango aparte danaria la secuencia general).
 *
 * Pedido tomado de la conversacion del bot (28/08/2026 20:43, confirmado):
 *   Sandra Caicedo, CC 66712464, tel 3155930030
 *   Cra 65 A # 13 B-39, Barrio Bosques del Limonar, Cali, Valle del Cauca
 *   1x AX-CATT-SEMIALBA $55.000 + 1x AX-DRACULA-VESP $85.000 = $140.000
 *   Contraentrega, envio incluido.
 *
 *   php crear_pedido_xonia_0001.php            (simulacion)
 *   php crear_pedido_xonia_0001.php --apply
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

$VENDOR = '1017267341';   // Sebastian Herrera (Axonia)
$FECHA  = '2026-08-28 20:43:00';
$SERIE  = 'XONIA-0001';
$OBS    = $SERIE . ' | Pedido por bot Xonia (WhatsApp). Contraentrega, envio incluido. '
        . 'Sandra Caicedo CC 66712464 TL 3155930030 DR Cra 65 A # 13 B-39, Barrio Bosques del Limonar, Cali, Valle del Cauca (casa, zona urbana). '
        . 'Nota: cliente pregunto por consignacion; el manejo acordado es contraentrega.';
$ITEMS  = array(
    array('AX-CATT-SEMIALBA', 1, 55000),
    array('AX-DRACULA-VESP',  1, 85000),
);
$TOTAL = 140000;

// Idempotencia por la serie en comments
$ya = $m->query("SELECT idBudget FROM budgets WHERE comments LIKE '" . $m->real_escape_string($SERIE) . "%' AND deleted = 0")->fetch_assoc();
if ($ya) { echo "Ya existe el presupuesto #{$ya['idBudget']} con la serie $SERIE. No se repite.\n"; exit(0); }

// Los productos deben existir
foreach ($ITEMS as $it) {
    $ex = $m->query("SELECT idProduct FROM products WHERE idProduct = '" . $m->real_escape_string($it[0]) . "' AND deleted = 0");
    if (!$ex || !$ex->num_rows) { echo "ABORTA: no existe el producto {$it[0]}\n"; exit(1); }
}

// Cliente por cedula, si no existe se crea
$cli = $m->query("SELECT idClient, name FROM clients WHERE deleted = 0 AND REGEXP_REPLACE(COALESCE(idNum,''),'[^0-9]','') = '66712464' LIMIT 1")->fetch_assoc();
if ($cli) { $clientId = (int)$cli['idClient']; echo "cliente ya existe: #{$clientId} {$cli['name']}\n"; }
else {
    echo "cliente Sandra Caicedo -> crear\n";
    if ($APPLY) {
        $cols = array(); $r = $m->query("SHOW COLUMNS FROM clients");
        while ($x = $r->fetch_assoc()) $cols[$x['Field']] = $x;
        $data = array(
            'name' => 'Sandra Caicedo', 'idNum' => '66712464',
            'cellphone' => '3155930030',
            'address' => 'Cra 65 A # 13 B -39, Barrio Bosques del Limonar',
            'city' => 'Cali', 'state' => 'Valle del Cauca',
            'vendor' => $VENDOR, 'created_by' => '71339095',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted' => 0,
        );
        if (isset($cols['tenant_id'])) $data['tenant_id'] = 1;
        foreach ($cols as $col => $def) {
            if (isset($data[$col]) || $col === 'idClient') continue;
            if ($def['Null'] === 'NO' && $def['Default'] === null && stripos($def['Extra'], 'auto') === false)
                $data[$col] = (stripos($def['Type'], 'int') !== false || stripos($def['Type'], 'decimal') !== false) ? 0 : '';
        }
        $cn = array(); $vn = array();
        foreach ($data as $k => $v) { $cn[] = "`$k`"; $vn[] = is_int($v) ? (string)$v : "'" . $m->real_escape_string($v) . "'"; }
        if (!$m->query("INSERT INTO clients (" . implode(',', $cn) . ") VALUES (" . implode(',', $vn) . ")")) {
            echo "ERROR cliente: {$m->error}\n"; exit(1);
        }
        $clientId = $m->insert_id;
    } else $clientId = 0;
}

// Presupuesto
if ($APPLY) $m->begin_transaction();
$hayTenantB = $m->query("SHOW COLUMNS FROM budgets LIKE 'tenant_id'")->num_rows > 0;
$tCol = $hayTenantB ? 'tenant_id, ' : '';
$tVal = $hayTenantB ? '1, ' : '';
if ($APPLY) {
    $ok = $m->query("INSERT INTO budgets ({$tCol}clientId, vendorId, storeId, total, comments, date, state, deleted, created_by, created_at, updated_at)
        VALUES ({$tVal}$clientId, '$VENDOR', 1, $TOTAL, '" . $m->real_escape_string($OBS) . "', '$FECHA', 0, 0, '71339095', NOW(), NOW())");
    if (!$ok) { echo "ERROR presupuesto: {$m->error}\n"; $m->rollback(); exit(1); }
    $budgetId = $m->insert_id;
    foreach ($ITEMS as $it) {
        list($code, $qty, $unit) = $it;
        $ok = $m->query("INSERT INTO budget_detail (budgetId, productId, quantity, unit, base, total, tenant_id)
            VALUES ($budgetId, '" . $m->real_escape_string($code) . "', $qty, $unit, $unit, " . ($qty * $unit) . ", 1)");
        if (!$ok) { echo "ERROR detalle $code: {$m->error}\n"; $m->rollback(); exit(1); }
    }
    $m->commit();
    echo "creado presupuesto #$budgetId ($SERIE) por " . number_format($TOTAL, 0, ',', '.') . " con " . count($ITEMS) . " productos\n";
} else {
    echo "se crearia presupuesto $SERIE por " . number_format($TOTAL, 0, ',', '.') . ":\n";
    foreach ($ITEMS as $it) printf("  %dx %-20s $%s\n", $it[1], $it[0], number_format($it[2], 0, ',', '.'));
}
