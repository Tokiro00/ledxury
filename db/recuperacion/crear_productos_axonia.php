<?php
/**
 * Crea el catalogo de Xonia (Axonia Biotech) como productos reales:
 * familia PLANTAS AXONIA + proveedor AXONIA BIOTECH + 20 referencias del
 * prompt del bot, con foto donde existe (public/images/flores/).
 * Costos en 0 hasta que Alex confirme el acuerdo con Axonia.
 *
 *   php crear_productos_axonia.php            (simulacion)
 *   php crear_productos_axonia.php --apply
 *
 * Idempotente: si el idProduct ya existe, lo salta.
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

// 1) Familia
$fam = $m->query("SELECT idFamily FROM product_families WHERE name = 'PLANTAS AXONIA' AND deleted = 0")->fetch_assoc();
if ($fam) { $famId = (int)$fam['idFamily']; echo "familia PLANTAS AXONIA ya existe (#$famId)\n"; }
else {
    if ($APPLY) {
        $m->query("INSERT INTO product_families (name, created_at, updated_at, deleted) VALUES ('PLANTAS AXONIA', NOW(), NOW(), 0)");
        $famId = $m->insert_id;
    } else $famId = 0;
    echo "familia PLANTAS AXONIA -> crear\n";
}

// 2) Proveedor
$prov = $m->query("SELECT idProvider FROM providers WHERE name LIKE 'AXONIA%' AND deleted = 0")->fetch_assoc();
if ($prov) { $provId = (int)$prov['idProvider']; echo "proveedor AXONIA ya existe (#$provId)\n"; }
else {
    if ($APPLY) {
        $cols = array(); $r = $m->query("SHOW COLUMNS FROM providers");
        while ($x = $r->fetch_assoc()) $cols[$x['Field']] = $x;
        $data = array('name' => 'AXONIA BIOTECH', 'created_at' => date('Y-m-d H:i:s'),
                      'updated_at' => date('Y-m-d H:i:s'), 'deleted' => 0);
        if (isset($cols['tenant_id'])) $data['tenant_id'] = 1;
        foreach ($cols as $col => $def) {
            if (isset($data[$col]) || $col === 'idProvider') continue;
            if ($def['Null'] === 'NO' && $def['Default'] === null && stripos($def['Extra'], 'auto') === false)
                $data[$col] = (stripos($def['Type'], 'int') !== false || stripos($def['Type'], 'decimal') !== false) ? 0 : '';
        }
        $cn = array(); $vn = array();
        foreach ($data as $k => $v) { $cn[] = "`$k`"; $vn[] = is_int($v) ? (string)$v : "'" . $m->real_escape_string($v) . "'"; }
        $m->query("INSERT INTO providers (" . implode(',', $cn) . ") VALUES (" . implode(',', $vn) . ")");
        $provId = $m->insert_id;
    } else $provId = 0;
    echo "proveedor AXONIA BIOTECH -> crear\n";
}

// 3) Productos: codigo, descripcion, precio, foto (ruta relativa a public/images/)
$plantas = array(
    // aclimatadas
    array('AX-ACOSTAEA',            'ACOSTAEA COSTARICENSIS ACLIMATADA',                          60000,  null),
    array('AX-ANTHURIUM-ROJO',      'ANTHURIUM ANDREANUM VAR. ROJO (AROIDE) ACLIMATADA',          40000,  null),
    array('AX-LEP-TELIPO',          'LEPANTHES TELIPOGONIFLORA ACLIMATADA',                       90000,  null),
    array('AX-LEP-CALO',            'LEPANTHES CALODICTYON ACLIMATADA',                           80000,  'flores/lepanthes-calodictyon.jpg'),
    array('AX-MASD-FILARIA',        'MASDEVALLIA FILARIA ACLIMATADA',                             85000,  'flores/masdevallia-filaria-adulta.jpg'),
    array('AX-COMP-SPECIOSA',       'COMPARETTIA SPECIOSA ACLIMATADA',                            80000,  null),
    array('AX-ODONTO-CIRR',         'ODONTOGLOSSUM CIRRHOSUM ACLIMATADA',                         55000,  'flores/odontoglossum-cirrhosum-adulta.jpg'),
    array('AX-DRACULA-VESP',        'DRACULA VESPERTILIO ACLIMATADA',                             85000,  'flores/dracula-vespertilio-adulta.jpg'),
    array('AX-CATT-SEMIALBA',       'CATTLEYA SEMIALBA (HIBRIDO) ACLIMATADA',                     55000,  null),
    array('AX-CATT-MENDELII',       'CATTLEYA MENDELII (ESPECIE) ACLIMATADA',                     55000,  'flores/cattleya-mendelii-bebe.jpg'),
    array('AX-CATT-DOWIANA',        'CATTLEYA DOWIANA (ESPECIE) ACLIMATADA',                      55000,  null),
    array('AX-CATT-LUEDDE',         'CATTLEYA LUEDDEMANNIANA (ESPECIE) ACLIMATADA',               50000,  null),
    array('AX-CATT-SUZUKI',         'CATTLEYA SUZUKI PRECIOSA (HIBRIDO) ACLIMATADA',              55000,  'flores/cattleya-suzukis-bebe.jpg'),
    array('AX-CATT-SUNNY',          'CATTLEYA SUNNYSTATES ACLIMATADA',                            55000,  'flores/cattleya-sunnystates-adulta.jpg'),
    array('AX-RLC-SUNGYA-GUTTATA',  'RLC SUNG YA GREEN X CATTLEYA GUTTATA ALBA (HIBRIDO) ACLIMATADA', 55000, 'flores/rlc-sung-ya-green-x-cattleya-guttata-alba.jpg'),
    array('AX-CATT-VIRGINIA-TAEKO', 'CATTLEYA VIRGINIA RUIZ X RLC TAEKO TAMAKI (HIBRIDO) ACLIMATADA', 55000, 'flores/cattleya-virginia-ruiz-x-rlc-taeko-tamaki.jpg'),
    array('AX-RLC-WAIKIKI-SPANISH', 'RLC WAIKIKI GOLD X RLC SPANISH EYES (HIBRIDO) ACLIMATADA',   50000,  null),
    // in vitro
    array('AX-LAELIA-PURP-IV',      'LAELIA PURPURATA ALBA IN VITRO (EN FRASCO)',                 120000, null),
    array('AX-ODONTO-CIRR-IV',      'ODONTOGLOSSUM CIRRHOSUM IN VITRO (EN FRASCO)',               120000, 'flores/odontoglossum-cirrhosum-bebe.jpg'),
    // kit
    array('AX-KIT-3PLANTAS',        'KIT 3 PLANTAS A ELECCION + SUSTRATO + MACETA + ASISTENCIA IA', 125000, null),
);

$creados = 0; $saltados = 0; $conFoto = 0;
foreach ($plantas as $p) {
    list($code, $desc, $precio, $foto) = $p;
    $ex = $m->query("SELECT idProduct FROM products WHERE idProduct = '" . $m->real_escape_string($code) . "'");
    if ($ex && $ex->num_rows) { $saltados++; continue; }
    $pic = $foto ? $foto : 'products/no_image.png';
    if ($foto && !is_file('/var/www/html/public/images/' . $foto)) {
        echo "  OJO: falta la foto $foto (se crea sin foto)\n";
        $pic = 'products/no_image.png'; $foto = null;
    }
    if ($APPLY) {
        $ok = $m->query("INSERT INTO products
            (idProduct, description, section, family, picture_url, provider, not_settle,
             price, price_base, cost, cost_cop, cost_rmb, min, location, is_national,
             abc_type, abc_revenue, deleted, updated_at, created_at)
            VALUES ('" . $m->real_escape_string($code) . "', '" . $m->real_escape_string($desc) . "',
             NULL, $famId, '" . $m->real_escape_string($pic) . "', $provId, 0,
             $precio, $precio, 0, 0, 0, 0, '', 1,
             'N', 0.00, 0, NOW(), NOW())");
        if (!$ok) { echo "  ERROR $code: {$m->error}\n"; continue; }
    }
    printf("  %-24s %-58s %8s %s\n", $code, mb_substr($desc, 0, 58),
        number_format($precio, 0, ',', '.'), $foto ? '[foto]' : '');
    $creados++;
    if ($foto) $conFoto++;
}
printf("\ncreados: %d (%d con foto) | ya existian: %d\n", $creados, $conFoto, $saltados);
