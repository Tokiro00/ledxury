<?php
// Script directo para consultar la base de datos
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Información Base de Datos</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .item { padding: 5px; border-left: 3px solid #007bff; margin: 5px 0; padding-left: 10px; }
        .found { background: #d4edda; border-left-color: #28a745; }
        .notfound { background: #f8d7da; border-left-color: #dc3545; }
    </style>
</head>
<body>
<?php
$conn = new mysqli("localhost", "root", "", "dropshipping");

if ($conn->connect_error) {
    die("<div class='section notfound'>Error de conexión: " . $conn->connect_error . "</div>");
}

// Configurar charset
$conn->set_charset("utf8");

echo "<h1>📊 Información de la Base de Datos - Dropshipping</h1>";

// Primero verificar estructura de tabla users
$columns_result = $conn->query("SHOW COLUMNS FROM users");
$user_columns = [];
while($col = $columns_result->fetch_assoc()) {
    $user_columns[] = $col['Field'];
}

// 1. VENDEDORES (ROLE = 3)
echo "<div class='section'>";
echo "<h2>1. TODOS LOS VENDEDORES (Role = 3)</h2>";

// Usar solo columnas que existen
$select_fields = "idUser, name";
if(in_array('email', $user_columns)) $select_fields .= ", email";
if(in_array('role', $user_columns)) $select_fields .= ", role";
if(in_array('store', $user_columns)) $select_fields .= ", store";

echo "<p><em>Columnas disponibles en users: " . implode(", ", $user_columns) . "</em></p>";

// Mostrar TODOS los vendedores (role = 3)
$result = $conn->query("SELECT {$select_fields} FROM users WHERE role = 3 AND deleted = 0 ORDER BY name");

if ($result && $result->num_rows > 0) {
    echo "<p><strong>✓ Total vendedores encontrados: {$result->num_rows}</strong></p>";
    while($row = $result->fetch_assoc()) {
        // Destacar GerMam y Julian Germam
        $isGermam = (strpos(strtolower($row['name']), 'germam') !== false || strpos(strtolower($row['name']), 'maria') !== false);
        $highlight = $isGermam ? ' found' : '';

        echo "<div class='item{$highlight}'>";
        echo "ID: <strong>{$row['idUser']}</strong><br>";
        echo "Nombre: {$row['name']}<br>";
        if(isset($row['email'])) echo "Email: {$row['email']}<br>";
        if(isset($row['store'])) echo "Store: {$row['store']}";
        if($isGermam) echo "<br><strong>← VENDEDOR GERMAM/MARIA</strong>";
        echo "</div>";
    }
} else {
    echo "<div class='item notfound'><strong>✗ NO se encontraron vendedores (role=3)</strong></div>";
}
echo "</div>";

// 2. TIENDAS
echo "<div class='section'>";
echo "<h2>2. TIENDAS DISPONIBLES</h2>";
$result = $conn->query("SELECT idStore, name FROM stores ORDER BY idStore");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<div class='item'>";
        echo "ID: <strong>{$row['idStore']}</strong> | Nombre: {$row['name']}";
        echo "</div>";
    }
} else {
    echo "<div class='item notfound'>No hay tiendas en la base de datos</div>";
}
echo "</div>";

// 3. PRODUCTOS CON MODULO O LED
echo "<div class='section'>";
echo "<h2>3. PRODUCTOS (con 'módulo' o 'led' en el nombre)</h2>";

// Primero verificar columnas de products
$prod_columns_result = $conn->query("SHOW COLUMNS FROM products");
$prod_columns = [];
while($col = $prod_columns_result->fetch_assoc()) {
    $prod_columns[] = $col['Field'];
}
echo "<p><em>Columnas en products: " . implode(", ", $prod_columns) . "</em></p>";

// Buscar la columna de nombre (puede ser 'name', 'product_name', 'nombre', etc.)
$name_field = 'idProduct';
foreach(['name', 'product_name', 'nombre', 'productName'] as $field) {
    if(in_array($field, $prod_columns)) {
        $name_field = $field;
        break;
    }
}

$select_prod = "idProduct";
if($name_field != 'idProduct') $select_prod .= ", {$name_field}";
if(in_array('price', $prod_columns)) $select_prod .= ", price";
if(in_array('price_base', $prod_columns)) $select_prod .= ", price_base";

$result = $conn->query("SELECT {$select_prod} FROM products WHERE ({$name_field} LIKE '%modulo%' OR {$name_field} LIKE '%led%' OR {$name_field} LIKE '%módulo%') AND deleted = 0 ORDER BY {$name_field} LIMIT 40");

if ($result && $result->num_rows > 0) {
    echo "<p><strong>Total encontrados: {$result->num_rows}</strong></p>";
    while($row = $result->fetch_assoc()) {
        echo "<div class='item found'>";
        echo "Código: <strong>{$row['idProduct']}</strong><br>";
        if(isset($row[$name_field])) echo "Nombre: {$row[$name_field]}<br>";
        if(isset($row['price'])) echo "Precio: \${$row['price']}";
        if(isset($row['price_base'])) echo " | Precio Base: \${$row['price_base']}";
        echo "</div>";
    }
} else {
    echo "<div class='item notfound'>No se encontraron productos con 'módulo' o 'led'</div>";

    echo "<h3>Primeros 30 productos activos:</h3>";
    $result = $conn->query("SELECT {$select_prod} FROM products WHERE deleted = 0 ORDER BY {$name_field} LIMIT 30");
    if($result) {
        while($row = $result->fetch_assoc()) {
            echo "<div class='item'>";
            echo "Código: <strong>{$row['idProduct']}</strong>";
            if(isset($row[$name_field])) echo " | {$row[$name_field]}";
            if(isset($row['price'])) echo " | \${$row['price']}";
            echo "</div>";
        }
    }
}
echo "</div>";

// 4. DELIVERY TYPES - Verificar si existe la tabla
echo "<div class='section'>";
echo "<h2>4. TIPOS DE ENVÍO</h2>";

// Verificar si existe la tabla delivery_type (singular)
$tables = $conn->query("SHOW TABLES LIKE 'delivery_type'");
if($tables && $tables->num_rows > 0) {
    $result = $conn->query("SELECT * FROM delivery_type WHERE deleted = 0 ORDER BY idDeliveryType");
    if ($result && $result->num_rows > 0) {
        echo "<p><strong>✓ Encontrados en tabla delivery_type:</strong></p>";
        while($row = $result->fetch_assoc()) {
            echo "<div class='item found'>";
            echo "ID: <strong>{$row['idDeliveryType']}</strong> | Nombre: {$row['name']}";
            echo "</div>";
        }
    } else {
        echo "<div class='item notfound'>La tabla existe pero está vacía</div>";
    }
} else {
    echo "<div class='item notfound'>⚠️ La tabla 'delivery_type' NO existe en la base de datos</div>";
}
echo "</div>";

// 5. BUSCAR ESPECÍFICAMENTE 6LED
echo "<div class='section'>";
echo "<h2>5. PRODUCTOS 6LED ESPECÍFICOS</h2>";
$result = $conn->query("SELECT {$select_prod} FROM products WHERE {$name_field} LIKE '%6LED%' AND deleted = 0 ORDER BY {$name_field}");
if ($result && $result->num_rows > 0) {
    echo "<p><strong>✓ Total 6LED encontrados: {$result->num_rows}</strong></p>";
    while($row = $result->fetch_assoc()) {
        $isAzul = strpos($row['idProduct'], '-E') !== false;
        $highlight = $isAzul ? ' found' : '';
        echo "<div class='item{$highlight}'>";
        echo "Código: <strong>{$row['idProduct']}</strong>";
        if(isset($row[$name_field])) echo " | {$row[$name_field]}";
        if(isset($row['price'])) echo " | \${$row['price']}";
        if($isAzul) echo " <strong>← AZUL (letra E)</strong>";
        echo "</div>";
    }
} else {
    echo "<div class='item notfound'>❌ NO se encontraron productos con 6LED</div>";
}
echo "</div>";

// 6. MAPEO GOOGLE SHEET → BASE DE DATOS
echo "<div class='section'>";
echo "<h2>6. MAPEO: GOOGLE SHEET → BASE DE DATOS</h2>";
echo "<div class='item found'>";
echo "<strong>✅ CONFIGURACIÓN CONFIRMADA:</strong><br><br>";
echo "<strong>Google Sheet dice:</strong><br>";
echo "• Nombre: Jaime Álvaro Díaz Pinto<br>";
echo "• Documento: 98385185<br>";
echo "• Dirección: Barrio Jorge Eliecer Gaitán, Puerto Asís, Putumayo<br>";
echo "• Módulos: 40 módulos 6LED<br>";
echo "• Color: Azul 💙<br>";
echo "• Voltaje: 12V<br>";
echo "• Cantidad: 40<br>";
echo "• Total: \$80.000<br><br>";
echo "<strong>↓ SE MAPEA A ↓</strong><br><br>";
echo "<strong>Cliente:</strong><br>";
echo "• idNum: 98385185<br>";
echo "• name: Jaime Álvaro Díaz Pinto<br>";
echo "• address: Barrio Jorge Eliecer Gaitán - Oficina Interrapidisimo<br>";
echo "• city: Puerto Asís<br>";
echo "• state: Putumayo<br>";
echo "• vendor: <strong>1234567</strong> (GerMam)<br>";
echo "• store: <strong>1</strong> (Medellín)<br><br>";
echo "<strong>Producto:</strong><br>";
echo "• idProduct: <strong>6LED-12V-E</strong> (letra E = Azul 💙)<br>";
echo "• quantity: 40<br><br>";
echo "<strong>Presupuesto:</strong><br>";
echo "• e_commerce: true<br>";
echo "• hasIva: false (sin IVA)<br>";
echo "• deliverytypeId: <strong>5</strong> (Interrapidisimo por defecto)<br>";
echo "• Columna tipo_envio → deliverytypeId + comentarios<br>";
echo "</div>";
echo "</div>";

$conn->close();
?>
</body>
</html>
