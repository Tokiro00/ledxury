<?php
$conn = new mysqli("localhost", "root", "", "dropshipping");

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

echo "=== COLUMNAS DE LA TABLA BUDGETS ===\n\n";
$result = $conn->query("SHOW COLUMNS FROM budgets");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

$conn->close();
?>
