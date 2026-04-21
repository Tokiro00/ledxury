<?php
// Aplicar cédulas reales a los 4 nuevos empleados y reasignar bots GerMAM.
// Grants bots_access a Yamile (1126908266) y Christina (52107500) para ver comisiones.
date_default_timezone_set('America/Bogota');

$pass = $argv[1] ?? die("Uso: php 016c_users_real_cedulas.php <mysql_password>\n");
$m = new mysqli('localhost', 'admindbmam', $pass, 'mamdb');
if ($m->connect_error) die("DB error: " . $m->connect_error . "\n");
$m->set_charset('utf8mb4');

$m->begin_transaction();
try {
    echo "=== STEP 1: Diego 1000001 -> 98698192 ===\n";
    $m->query("UPDATE users SET idUser='98698192', updated_at=NOW() WHERE idUser='1000001'") or throw new Exception($m->error);
    echo "  OK\n";

    echo "=== STEP 2: Romantiezer 1000003 -> 25831391 ===\n";
    $m->query("UPDATE users SET idUser='25831391', updated_at=NOW() WHERE idUser='1000003'") or throw new Exception($m->error);
    echo "  OK\n";

    echo "=== STEP 3: Felipe 1000004 -> 1054567545 ===\n";
    $m->query("UPDATE users SET idUser='1054567545', updated_at=NOW() WHERE idUser='1000004'") or throw new Exception($m->error);
    echo "  OK\n";

    echo "=== STEP 4: Cristian Hodnett - copiar hash de placeholder a real y eliminar placeholder ===\n";
    $r = $m->query("SELECT password FROM users WHERE idUser='1000002'")->fetch_assoc();
    if ($r) {
        $stmt = $m->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE idUser='1035854260'");
        $stmt->bind_param('s', $r['password']);
        $stmt->execute();
        $m->query("DELETE FROM users WHERE idUser='1000002'") or throw new Exception($m->error);
        echo "  OK (password de 1000002 transferido a 1035854260, placeholder eliminado)\n";
    } else {
        echo "  SKIP (placeholder 1000002 no existe)\n";
    }

    echo "=== STEP 5: GerMAM/GerLedxury Medellin -> vendor=71211970 (Jorge) ===\n";
    $m->query("UPDATE builderbot_configs SET default_vendor_id='71211970' WHERE id=1") or throw new Exception($m->error);
    // Archivar el usuario placeholder 1234567 (no esta en el cuadro)
    $m->query("UPDATE users SET archived=1, updated_at=NOW() WHERE idUser='1234567'") or throw new Exception($m->error);
    echo "  OK\n";

    echo "=== STEP 6: Grant bots_access=1 a Yamile (1126908266) y Christina (52107500) ===\n";
    $m->query("UPDATE users SET bots_access=1, updated_at=NOW() WHERE idUser IN ('1126908266','52107500')") or throw new Exception($m->error);
    echo "  OK\n";

    $m->commit();
    echo "\n=== COMMIT OK ===\n";

    // Verificar
    echo "\n=== Estado final (activos, relevantes) ===\n";
    $res = $m->query("SELECT idUser, name, role, bots_access FROM users
                      WHERE archived=0 AND deleted=0 AND (
                          idUser IN ('98698192','25831391','1054567545','1035854260','1126908266','52107500','71211970','71339095')
                          OR name LIKE '%GerMAM%' OR name LIKE '%GerLedxury%'
                      ) ORDER BY role, name");
    while ($row = $res->fetch_assoc()) {
        printf("  %-16s | %-28s | role=%d | bots_access=%d\n",
            $row['idUser'], $row['name'], $row['role'], $row['bots_access']);
    }

    echo "\n=== Bot configs ===\n";
    $res = $m->query("SELECT id, name, default_vendor_id FROM builderbot_configs ORDER BY id");
    while ($row = $res->fetch_assoc()) {
        printf("  id=%d | %-30s | vendor=%s\n", $row['id'], $row['name'], $row['default_vendor_id']);
    }

} catch (Exception $e) {
    $m->rollback();
    echo "\nFALLO, rollback aplicado: " . $e->getMessage() . "\n";
    exit(1);
}
