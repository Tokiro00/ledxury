<?php
/**
 * Aplica la migración 071 (libro diario con asientos compuestos).
 *
 *   php aplicar_migracion_071.php            (simulación)
 *   php aplicar_migracion_071.php --apply
 *
 * Crea entry_groups, entry_group_lines y la columna entries.entryGroupId.
 * Idempotente: usa CREATE TABLE IF NOT EXISTS y comprueba la columna antes de
 * agregarla, así que correrlo dos veces no hace nada la segunda.
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

$errores = 0;
function go($m, $sql) { $r = $m->query($sql); if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    if ($r === true) return array(); $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o; }
function paso($m, $APPLY, $label, $sql, &$errores) {
    echo "  {$label}\n";
    if (!$APPLY) { echo "     [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 96)) . "\n"; return; }
    if ($m->query($sql) === false) { echo "     ERROR: {$m->error}\n"; $errores++; return; }
    echo "     ok\n";
}

echo "── estado actual\n";
$t1 = go($m, "SHOW TABLES LIKE 'entry_groups'");
$t2 = go($m, "SHOW TABLES LIKE 'entry_group_lines'");
$col = go($m, "SHOW COLUMNS FROM entries LIKE 'entryGroupId'");
echo "  entry_groups: " . ($t1 ? 'existe' : 'no existe') . "\n";
echo "  entry_group_lines: " . ($t2 ? 'existe' : 'no existe') . "\n";
echo "  entries.entryGroupId: " . ($col ? 'existe' : 'no existe') . "\n\n";

echo "── creando\n";
if (!$t1) {
    paso($m, $APPLY, 'tabla entry_groups', "
        CREATE TABLE IF NOT EXISTS `entry_groups` (
          `id`          INT(11) NOT NULL AUTO_INCREMENT,
          `group_date`  DATE NOT NULL,
          `description` VARCHAR(255) NOT NULL,
          `store_id`    INT(11) NOT NULL DEFAULT 1,
          `total`       DECIMAL(18,2) NOT NULL DEFAULT 0.00,
          `created_by`  VARCHAR(100) DEFAULT NULL,
          `created_at`  DATETIME DEFAULT NULL,
          `deleted`     TINYINT(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          KEY `idx_eg_date` (`group_date`),
          KEY `idx_eg_deleted` (`deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $errores);
} else echo "  tabla entry_groups: ya existe\n";

if (!$t2) {
    paso($m, $APPLY, 'tabla entry_group_lines', "
        CREATE TABLE IF NOT EXISTS `entry_group_lines` (
          `id`            INT(11) NOT NULL AUTO_INCREMENT,
          `group_id`      INT(11) NOT NULL,
          `ord`           INT(11) NOT NULL DEFAULT 1,
          `subaccount_id` INT(11) NOT NULL,
          `aux_id`        INT(11) DEFAULT NULL,
          `concepto`      VARCHAR(255) DEFAULT NULL,
          `debe`          DECIMAL(18,2) NOT NULL DEFAULT 0.00,
          `haber`         DECIMAL(18,2) NOT NULL DEFAULT 0.00,
          PRIMARY KEY (`id`),
          KEY `idx_egl_group` (`group_id`),
          KEY `idx_egl_sub` (`subaccount_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $errores);
} else echo "  tabla entry_group_lines: ya existe\n";

if (!$col) {
    paso($m, $APPLY, 'columna entries.entryGroupId',
        "ALTER TABLE `entries` ADD COLUMN `entryGroupId` INT(11) NULL DEFAULT NULL AFTER `entryTransactionId`", $errores);
    paso($m, $APPLY, 'índice idx_entries_group',
        "ALTER TABLE `entries` ADD INDEX `idx_entries_group` (`entryGroupId`)", $errores);
} else echo "  columna entries.entryGroupId: ya existe\n";

if ($errores) { echo "\n{$errores} error(es).\n"; exit(1); }

echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if (!$APPLY) exit(0);
foreach (array('entry_groups', 'entry_group_lines') as $t) {
    echo "\n  {$t}:\n";
    foreach (go($m, "SHOW COLUMNS FROM `{$t}`") as $x)
        printf("    %-16s %s\n", $x['Field'], $x['Type']);
}
$col = go($m, "SHOW COLUMNS FROM entries LIKE 'entryGroupId'");
echo "\n  entries.entryGroupId: " . ($col ? $col[0]['Type'] . ' null=' . $col[0]['Null'] : 'FALTA') . "\n";
