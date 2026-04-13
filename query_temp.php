<?php
// Script temporal para consultar la base de datos
$conn = new mysqli("localhost", "root", "", "dropshipping");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== VENDEDORES (german/germam) ===\n";
$result = $conn->query("SELECT idUser, name, uname, email, role, store FROM users WHERE name LIKE '%german%' OR uname LIKE '%german%' OR name LIKE '%germam%' OR uname LIKE '%germam%'");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['idUser']}, Name: {$row['name']}, Username: {$row['uname']}, Email: {$row['email']}, Role: {$row['role']}, Store: {$row['store']}\n";
    }
} else {
    echo "No se encontró vendedor con ese nombre\n";
    echo "\n=== TODOS LOS VENDEDORES (role=3) ===\n";
    $result = $conn->query("SELECT idUser, name, uname, email, role, store FROM users WHERE role = 3 LIMIT 10");
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['idUser']}, Name: {$row['name']}, Username: {$row['uname']}\n";
    }
}

echo "\n=== TIENDAS ===\n";
$result = $conn->query("SELECT idStore, name FROM stores ORDER BY idStore");
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['idStore']}, Name: {$row['name']}\n";
}

echo "\n=== PRODUCTOS (ejemplo con 'modulo' o 'led') ===\n";
$result = $conn->query("SELECT idProduct, name, price, price_base FROM products WHERE name LIKE '%modulo%' OR name LIKE '%led%' OR name LIKE '%módulo%' LIMIT 20");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['idProduct']}, Name: {$row['name']}, Price: {$row['price']}, Base: {$row['price_base']}\n";
    }
} else {
    echo "No se encontraron productos con 'modulo' o 'led'\n";
}

echo "\n=== DELIVERY TYPES ===\n";
$result = $conn->query("SELECT * FROM deliverytypes ORDER BY idDeliverytype");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['idDeliverytype']}, Name: {$row['name']}\n";
    }
}

$conn->close();
?>
