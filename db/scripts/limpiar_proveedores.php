<?php
/**
 * Limpieza del panel de proveedores: deja solo los que se usan.
 *
 * Ejecutar en el server de ledxury:  php limpiar_proveedores.php [--apply]
 * Sin --apply es SIMULACIÓN.
 *
 * Auditoría del 20/08/2026 (referencias reales en 9 tablas):
 *   id 12 MAM         → EN USO: 1.597 product_providers, 16 órdenes, 1 regla
 *                       de compra, 1 devolución, 3 facturas. SE QUEDA.
 *   id 1  SIN PROVEEDOR → ESTRUCTURAL: es el proveedor por defecto de 1.606
 *                       productos y 4 gastos. SE QUEDA (borrarlo dejaría esos
 *                       productos apuntando a un id inexistente).
 *   ids 2,3,7,11 (CRISTINA, DAGAZ, PLAZA, GPS) → solo tienen productos
 *                       asignados (2+3+4+13 = 22). Sus productos se reasignan
 *                       a SIN PROVEEDOR y se archivan.
 *   ids 4,5,6,8,9,10 (ZIPAQUIRA, DUMAR, ACT, LUJOS RAMIREZ, EXTIPLAST,
 *                       ORAFOL) → CERO referencias. Se archivan directo.
 *
 * Es soft-delete (deleted=1): no se pierde nada y el historial queda. La
 * reasignación de productos se registra en providers_cleanup_log para poder
 * revertirla producto por producto.
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

$KEEP        = array(1, 12);                    // estructural + MAM
$REASIGNAR   = array(2, 3, 7, 11);              // tienen productos
$ARCHIVAR_YA = array(4, 5, 6, 8, 9, 10);        // sin referencias
$DEFAULT_PROV = 1;

function one($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); exit(1); } return $r->fetch_assoc(); }
function all($m, $sql) { $r = $m->query($sql); if (!$r) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); exit(1); } $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o; }
function run($m, $APPLY, $sql) {
    if (!$APPLY) { echo "  [sim] " . preg_replace('/\s+/', ' ', substr(trim($sql), 0, 150)) . "\n"; return; }
    if (!$m->query($sql)) { fwrite(STDERR, "SQL ERR: {$m->error}\n$sql\n"); $m->rollback(); exit(1); }
    echo "  [ok] filas: {$m->affected_rows}\n";
}

// ── Verificación en vivo: nadie a archivar puede tener uso "duro" ────────────
$candidatos = implode(',', array_merge($REASIGNAR, $ARCHIVAR_YA));
$uso = all($m, "SELECT p.idProvider, p.name,
    (SELECT COUNT(*) FROM provider_invoices x WHERE x.provider_id=p.idProvider AND COALESCE(x.deleted,0)=0) f_nuevas,
    (SELECT COUNT(*) FROM supplier_invoices x WHERE x.providerId=p.idProvider AND x.deleted=0) f_viejas,
    (SELECT COUNT(*) FROM provider_advances x WHERE x.provider_id=p.idProvider AND COALESCE(x.deleted,0)=0) anticipos,
    (SELECT COUNT(*) FROM supplier_orders x WHERE x.providerId=p.idProvider) ordenes,
    (SELECT COUNT(*) FROM product_providers x WHERE x.providerId=p.idProvider) prodprov,
    (SELECT COUNT(*) FROM expense_records x WHERE x.provider_id=p.idProvider AND COALESCE(x.deleted,0)=0) gastos,
    (SELECT COUNT(*) FROM products x WHERE x.provider=p.idProvider AND COALESCE(x.deleted,0)=0) productos
    FROM providers p WHERE p.idProvider IN ($candidatos)");
$bloqueados = array();
foreach ($uso as $u) {
    $duro = (int)$u['f_nuevas'] + (int)$u['f_viejas'] + (int)$u['anticipos'] + (int)$u['ordenes'] + (int)$u['prodprov'] + (int)$u['gastos'];
    printf("  %-3s %-16s productos:%-4s uso duro:%s%s\n", $u['idProvider'], $u['name'], $u['productos'], $duro, $duro > 0 ? '  ← NO SE ARCHIVA' : '');
    if ($duro > 0) $bloqueados[] = (int)$u['idProvider'];
}
if ($bloqueados) {
    echo "\nOJO: estos proveedores tienen uso más allá de productos y NO se archivan: " . implode(', ', $bloqueados) . "\n";
    $REASIGNAR   = array_values(array_diff($REASIGNAR, $bloqueados));
    $ARCHIVAR_YA = array_values(array_diff($ARCHIVAR_YA, $bloqueados));
}
echo "\n";

if ($APPLY) $m->begin_transaction();

// ── 1. Tabla de respaldo para poder revertir la reasignación ────────────────
run($m, $APPLY, "CREATE TABLE IF NOT EXISTS providers_cleanup_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    idProduct VARCHAR(50) NOT NULL,
    prev_provider INT(11) NOT NULL,
    new_provider INT(11) NOT NULL,
    batch VARCHAR(40) NOT NULL,
    created_at DATETIME DEFAULT current_timestamp(),
    PRIMARY KEY (id), KEY idx_batch (batch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── 2. Reasignar productos de los proveedores a archivar ────────────────────
if ($REASIGNAR) {
    $in = implode(',', $REASIGNAR);
    $prods = all($m, "SELECT idProduct, provider FROM products WHERE provider IN ($in) AND COALESCE(deleted,0)=0");
    echo "2) REASIGNAR " . count($prods) . " producto(s) a SIN PROVEEDOR (id $DEFAULT_PROV)\n";
    foreach ($prods as $p) echo "   {$p['idProduct']} (proveedor {$p['provider']})\n";
    run($m, $APPLY, "INSERT INTO providers_cleanup_log (idProduct, prev_provider, new_provider, batch)
        SELECT idProduct, provider, $DEFAULT_PROV, 'limpieza_20260820' FROM products
        WHERE provider IN ($in) AND COALESCE(deleted,0)=0");
    run($m, $APPLY, "UPDATE products SET provider = $DEFAULT_PROV, updated_at = NOW()
        WHERE provider IN ($in) AND COALESCE(deleted,0)=0");
}

// ── 3. Archivar (soft-delete) ───────────────────────────────────────────────
$archivar = array_merge($REASIGNAR, $ARCHIVAR_YA);
if ($archivar) {
    $in = implode(',', $archivar);
    $nombres = array();
    foreach (all($m, "SELECT idProvider, name FROM providers WHERE idProvider IN ($in)") as $p) $nombres[] = $p['name'];
    echo "\n3) ARCHIVAR " . count($archivar) . " proveedor(es): " . implode(', ', $nombres) . "\n";
    run($m, $APPLY, "UPDATE providers SET deleted = 1, deleted_at = NOW(), active = 0
        WHERE idProvider IN ($in)");
}

echo "\n4) SE QUEDAN: " . implode(', ', $KEEP) . " (SIN PROVEEDOR estructural + MAM en uso)\n";

if ($APPLY) {
    $m->commit();
    echo "\n=== APLICADO — VERIFICACION ===\n";
    foreach (all($m, "SELECT p.idProvider, p.name,
        (SELECT COUNT(*) FROM products x WHERE x.provider=p.idProvider AND COALESCE(x.deleted,0)=0) productos,
        (SELECT COALESCE(SUM(total-paid),0) FROM provider_invoices x WHERE x.provider_id=p.idProvider AND COALESCE(x.deleted,0)=0 AND x.status IN ('open','paid_partial','en_transito')) cxp
        FROM providers p WHERE COALESCE(p.deleted,0)=0 ORDER BY p.idProvider") as $p) {
        printf("  %-3s %-16s productos:%-6s CxP:%s\n", $p['idProvider'], $p['name'], $p['productos'], number_format($p['cxp'], 0, ',', '.'));
    }
    $v = one($m, "SELECT COUNT(*) n FROM products WHERE provider NOT IN (SELECT idProvider FROM providers WHERE COALESCE(deleted,0)=0) AND COALESCE(deleted,0)=0");
    echo "  productos apuntando a un proveedor archivado: {$v['n']} (debe ser 0)\n";
    echo "\nPara revertir la reasignación de productos:\n";
    echo "  UPDATE products p JOIN providers_cleanup_log l ON l.idProduct = p.idProduct\n";
    echo "     SET p.provider = l.prev_provider WHERE l.batch = 'limpieza_20260820';\n";
    echo "  UPDATE providers SET deleted = 0, active = 1 WHERE idProvider IN (...);\n";
} else {
    echo "\n=== FIN SIMULACION — nada se escribió ===\n";
}
