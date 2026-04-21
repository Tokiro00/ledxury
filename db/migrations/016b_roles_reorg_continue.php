<?php
// Continuacion del paso 6 y 7 de 016_roles_reorg.php
date_default_timezone_set('America/Bogota');

$pass = $argv[1] ?? die("Uso: php 016b_roles_reorg_continue.php <mysql_password>\n");
$m = new mysqli('localhost', 'admindbmam', $pass, 'mamdb');
if ($m->connect_error) die("DB error: " . $m->connect_error . "\n");
$m->set_charset('utf8mb4');

function randPass($len = 10) {
    $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
    $p = '';
    for ($i = 0; $i < $len; $i++) $p .= $chars[random_int(0, strlen($chars) - 1)];
    return $p;
}

echo "=== STEP 6: Create missing employees ===\n";
$new_users = [
    ['1000001', 'Diego Gutiérrez',       2, 0],
    ['1000002', 'Cristian Hodnett',      2, 0],
    ['1000003', 'Romantiezer Escalona',  4, 0],
    ['1000004', 'Felipe Tabarquino',     4, 0],
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
    $sql = "INSERT INTO users (idUser, f_id, name, password, role, archived, deleted, bots_access, user_status, picture_url, last_logout, created_at)
            VALUES (?, 0, ?, ?, ?, 0, 0, ?, 'active', 'users/general_1.png', '', NOW())";
    $stmt = $m->prepare($sql);
    $stmt->bind_param('sssii', $uid, $name, $hash, $role, $bots_access);
    $stmt->execute();
    echo "  Created: $uid | $name | role=$role\n";
    $new_passwords[$uid] = $plain;
}

echo "\n=== STEP 7: Reset passwords for existing employees (except Jorge & Alexander) ===\n";
$reset_users = $m->query("SELECT idUser, name, role FROM users WHERE archived=0 AND deleted=0 AND idUser NOT IN ('71211970', '71339095') ORDER BY role, name")->fetch_all(MYSQLI_ASSOC);

$reset_passwords = [];
foreach ($reset_users as $u) {
    if (isset($new_passwords[$u['idUser']])) {
        $reset_passwords[] = [$u['idUser'], $u['name'], $u['role'], $new_passwords[$u['idUser']]];
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
ksort($by_role);
foreach ($by_role as $role => $users) {
    echo "--- " . ($role_labels[$role] ?? "Role $role") . " ---\n";
    foreach ($users as $u) {
        printf("  %-16s | %-30s | %s\n", $u['uid'], $u['name'], $u['pass']);
    }
    echo "\n";
}

echo "No modificados (Superadmin + bots):\n";
echo "  71211970         | JORGE CANO                    | (no cambio)\n";
echo "  71339095         | Alexander Alzate              | (no cambio)\n";
