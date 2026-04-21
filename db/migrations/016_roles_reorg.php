<?php
// Script de reorganizacion de roles y permisos (ejecutar una sola vez)
// Uso: php 016_roles_reorg.php <mysql_password>

date_default_timezone_set('America/Bogota');

$pass = $argv[1] ?? die("Uso: php 016_roles_reorg.php <mysql_password>\n");
$m = new mysqli('localhost', 'admindbmam', $pass, 'mamdb');
if ($m->connect_error) die("DB error: " . $m->connect_error . "\n");
$m->set_charset('utf8mb4');

function randPass($len = 10) {
    $chars = 'abcdefghjkmnpqrstuvwxyz23456789'; // sin 0/O/l/1/i para legibilidad
    $p = '';
    for ($i = 0; $i < $len; $i++) $p .= $chars[random_int(0, strlen($chars) - 1)];
    return $p;
}

echo "=== BACKUP users table ===\n";
$backup_file = '/tmp/users_backup_' . date('Ymd_His') . '.sql';
exec("mysqldump -u admindbmam -p" . escapeshellarg($pass) . " mamdb users > $backup_file 2>&1", $out, $rc);
echo "Backup: $backup_file (exit=$rc)\n\n";

echo "=== STEP 1: Add bots_access column ===\n";
$col_check = $m->query("SHOW COLUMNS FROM users LIKE 'bots_access'");
if ($col_check->num_rows === 0) {
    $m->query("ALTER TABLE users ADD COLUMN bots_access TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_store");
    echo "  Column added\n";
} else {
    echo "  Column already exists\n";
}

echo "\n=== STEP 2: Archive users not in organizational table ===\n";
$archive_list = ['12077935000', '5210750', '00000', '123456'];
foreach ($archive_list as $uid) {
    $stmt = $m->prepare("UPDATE users SET archived=1, updated_at=NOW() WHERE idUser=?");
    $stmt->bind_param('s', $uid);
    $stmt->execute();
    echo "  Archived: $uid (affected=" . $stmt->affected_rows . ")\n";
}

echo "\n=== STEP 3: Role adjustments ===\n";
$role_updates = [
    '1126908266' => 2, // Yamile Garcia -> Admin (Contabilidad)
    '712180788'  => 2, // Carlos Henao -> Admin (Jefe bodega local)
];
foreach ($role_updates as $uid => $role) {
    $stmt = $m->prepare("UPDATE users SET role=?, updated_at=NOW() WHERE idUser=?");
    $stmt->bind_param('is', $role, $uid);
    $stmt->execute();
    echo "  $uid -> role=$role\n";
}

echo "\n=== STEP 4: Assign bots_access ===\n";
$bots_users = ['71211970', '71339095']; // Jorge Cano, Alexander Alzate
foreach ($bots_users as $uid) {
    $stmt = $m->prepare("UPDATE users SET bots_access=1, updated_at=NOW() WHERE idUser=?");
    $stmt->bind_param('s', $uid);
    $stmt->execute();
    echo "  $uid -> bots_access=1\n";
}

echo "\n=== STEP 5: Rename bot vendor users ===\n";
$bot_rename = [
    '1234567'    => 'GerMAM Medellín',
    '1048937562' => 'GerMAM Barranquilla',
    '12345678'   => 'GerMAM Bogotá',
];
foreach ($bot_rename as $uid => $name) {
    $stmt = $m->prepare("UPDATE users SET name=?, updated_at=NOW() WHERE idUser=?");
    $stmt->bind_param('ss', $name, $uid);
    $stmt->execute();
    echo "  $uid -> $name\n";
}

echo "\n=== STEP 6: Create missing employees ===\n";
$new_users = [
    ['1000001', 'Diego Gutiérrez',       2, 0], // Admin - Jefe bodegas general
    ['1000002', 'Cristian Hodnett',      2, 0], // Admin - Jefe logística general
    ['1000003', 'Romantiezer Escalona',  4, 0], // Storer - Auxiliar bodega
    ['1000004', 'Felipe Tabarquino',     4, 0], // Storer - Auxiliar logística
];
$new_passwords = [];
foreach ($new_users as $u) {
    list($uid, $name, $role, $bots_access) = $u;
    $check = $m->query("SELECT idUser FROM users WHERE idUser = '" . $m->real_escape_string($uid) . "'");
    if ($check->num_rows > 0) {
        echo "  $uid already exists, skipping\n";
        continue;
    }
    $plain = randPass(10);
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $stmt = $m->prepare("INSERT INTO users (idUser, f_id, name, password, role, archived, deleted, bots_access, user_status, picture_url, created_at) VALUES (?, 0, ?, ?, ?, 0, 0, ?, 'active', 'users/general_1.png', NOW())");
    $stmt->bind_param('sssii', $uid, $name, $hash, $role, $bots_access);
    $stmt->execute();
    echo "  Created: $uid | $name | role=$role\n";
    $new_passwords[] = [$uid, $name, $plain];
}

echo "\n=== STEP 7: Reset passwords for existing employees (except Jorge & Alexander) ===\n";
$reset_users = $m->query("SELECT idUser, name, role FROM users WHERE archived=0 AND deleted=0 AND idUser NOT IN ('71211970', '71339095') ORDER BY role, name")->fetch_all(MYSQLI_ASSOC);

$reset_passwords = [];
foreach ($reset_users as $u) {
    // Skip newly created users (already have passwords set above)
    $found_new = false;
    foreach ($new_passwords as $np) {
        if ($np[0] === $u['idUser']) { $found_new = true; break; }
    }
    if ($found_new) {
        $reset_passwords[] = [$u['idUser'], $u['name'], $u['role'], array_column($new_passwords, 2, 0)[$u['idUser']]];
        continue;
    }
    $plain = randPass(10);
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $stmt = $m->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE idUser=?");
    $stmt->bind_param('ss', $hash, $u['idUser']);
    $stmt->execute();
    $reset_passwords[] = [$u['idUser'], $u['name'], $u['role'], $plain];
}

echo "\n=== DONE ===\n";
echo "\n====================================\n";
echo "NUEVAS CONTRASEÑAS (guardar ahora!)\n";
echo "====================================\n\n";

$role_labels = [1 => 'Superadmin', 2 => 'Admin', 3 => 'Vendedor', 4 => 'Bodeguero'];
$by_role = [];
foreach ($reset_passwords as $rp) {
    list($uid, $name, $role, $pass) = $rp;
    $by_role[$role][] = compact('uid', 'name', 'pass');
}
foreach ($by_role as $role => $users) {
    echo "--- " . ($role_labels[$role] ?? "Role $role") . " ---\n";
    foreach ($users as $u) {
        printf("  %-16s | %-30s | %s\n", $u['uid'], $u['name'], $u['pass']);
    }
    echo "\n";
}

echo "\nNo modificados (Superadmin + bots):\n";
echo "  71211970         | JORGE CANO                 | (no cambio)\n";
echo "  71339095         | Alexander Alzate           | (no cambio)\n";

echo "\nBackup users original en: $backup_file\n";
