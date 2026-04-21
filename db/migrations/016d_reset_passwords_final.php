<?php
date_default_timezone_set('America/Bogota');

$pass = $argv[1] ?? die("Uso: php 016d_reset_passwords_final.php <mysql_password>\n");
$m = new mysqli('localhost', 'admindbmam', $pass, 'mamdb');
if ($m->connect_error) die("DB error: " . $m->connect_error . "\n");
$m->set_charset('utf8mb4');

function randPass($len = 10) {
    $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
    $p = '';
    for ($i = 0; $i < $len; $i++) $p .= $chars[random_int(0, strlen($chars) - 1)];
    return $p;
}

$users = $m->query("SELECT idUser, name, role, bots_access FROM users
                    WHERE archived=0 AND deleted=0
                      AND idUser NOT IN ('71211970','71339095')
                    ORDER BY role, name")->fetch_all(MYSQLI_ASSOC);

$results = [];
foreach ($users as $u) {
    $plain = randPass(10);
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $stmt = $m->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE idUser=?");
    $stmt->bind_param('ss', $hash, $u['idUser']);
    $stmt->execute();
    $results[] = array_merge($u, ['pass' => $plain]);
}

$role_labels = [1 => 'Superadmin', 2 => 'Admin', 3 => 'Vendedor', 4 => 'Bodeguero/Contador'];
echo "\n====================================\n";
echo "CONTRASENAS FINALES (" . count($results) . " usuarios)\n";
echo "====================================\n\n";
printf("%-16s | %-28s | %-18s | %-4s | %s\n", 'idUser', 'Nombre', 'Rol', 'Bots', 'Password');
printf("%s\n", str_repeat('-', 90));
foreach ($results as $r) {
    printf("%-16s | %-28s | %-18s | %-4s | %s\n",
        $r['idUser'],
        $r['name'],
        ($role_labels[$r['role']] ?? "Role {$r['role']}"),
        ($r['bots_access'] ? 'SI' : 'no'),
        $r['pass']);
}
echo "\nSuperadmin + bots (sin cambio):\n";
echo "  71211970         | JORGE CANO                   | Superadmin         | SI   | (no cambio)\n";
echo "  71339095         | Alexander Alzate             | Superadmin         | SI   | (no cambio)\n";
