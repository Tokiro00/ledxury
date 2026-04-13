<?php
// Script directo sin CodeIgniter para consultar usuario
$conn = new mysqli("localhost", "root", "", "dropshipping");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = '71339095';

echo "=== BUSCANDO USUARIO: $userId ===\n\n";

$stmt = $conn->prepare("SELECT idUser, name, uname, email, role, store, password FROM users WHERE idUser = ? OR uname = ?");
$stmt->bind_param("ss", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Usuario encontrado:\n";
    echo "ID: {$user['idUser']}\n";
    echo "Name: {$user['name']}\n";
    echo "Username: {$user['uname']}\n";
    echo "Email: {$user['email']}\n";
    echo "Role: {$user['role']}\n";
    echo "Store: {$user['store']}\n";
    echo "Password Hash: {$user['password']}\n";
    echo "\nNOTA: La contraseña está hasheada con bcrypt.\n";
    echo "No se puede recuperar la contraseña original.\n";
} else {
    echo "No se encontró usuario con ID/username: $userId\n\n";

    echo "=== LISTANDO USUARIOS ADMINISTRADORES (role=1) ===\n";
    $result = $conn->query("SELECT idUser, name, uname, email FROM users WHERE role = 1 LIMIT 10");
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['idUser']}, Username: {$row['uname']}, Name: {$row['name']}\n";
    }
}

$conn->close();
?>
