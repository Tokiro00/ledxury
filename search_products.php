<?php
$conn = new mysqli("localhost", "root", "", "dropshipping");

if ($conn->connect_error) {
    die(json_encode(["error" => $conn->connect_error]));
}

$conn->set_charset("utf8");

// Buscar todos los productos con voltaje 12V para ver los colores disponibles
$query = "SELECT idProduct, price FROM products WHERE (idProduct LIKE '3LED-12V-%' OR idProduct LIKE '6LED-12V-%' OR idProduct LIKE '6LED-24V-%' OR idProduct LIKE '3LED-24V-%' OR idProduct LIKE '2835-12V-%' OR idProduct LIKE '2835-24V-%') AND deleted = 0 ORDER BY idProduct";
$result = $conn->query($query);

$products = [];
while($row = $result->fetch_assoc()) {
    $products[] = $row;
}

header('Content-Type: application/json');
echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>
