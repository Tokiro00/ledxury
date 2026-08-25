#!/bin/bash
# Copia las llaves del API de Interrapidisimo desde accesoriosmam al servidor
# de Ledxury y prueba la conexion. NO muestra las llaves en pantalla.
#
# Correr en GIT BASH (no en cmd):   bash db/recuperacion/traer_llaves_inter.sh
set -e
cd /c/xampp/htdocs/ledxury
PEM="db/Amazon_MAM.pem"
MAM="ec2-user@34.207.188.31"
LED="ec2-user@54.145.102.94"
SSH="ssh -i $PEM -o StrictHostKeyChecking=no"

echo "1) Extrayendo el bloque en accesoriosmam..."
$SSH $MAM "sudo php -r 'define(\"BASEPATH\",\"x\"); \$config=[]; include \"/var/www/html/application/config/secrets.php\"; file_put_contents(\"/tmp/inter_block.json\", json_encode(\$config[\"interrapidisimo\"])); chmod(\"/tmp/inter_block.json\",0644); echo \"   ok\n\";'"

echo "2) Copiandolo al servidor de Ledxury..."
scp -3 -q -i "$PEM" -o StrictHostKeyChecking=no "$MAM:/tmp/inter_block.json" "$LED:/tmp/inter_block.json"
$SSH $MAM "sudo rm -f /tmp/inter_block.json"
echo "   ok"

echo "3) Creando el script de mezcla..."
$SSH $LED 'cat > /tmp/mezclar_inter.php' <<'FIN'
<?php
define('BASEPATH', 'x');
$mam = json_decode(file_get_contents('/tmp/inter_block.json'), true);
if (!$mam || empty($mam['signature']) || empty($mam['token'])) { echo "json invalido\n"; exit(1); }
$f = '/var/www/html/application/config/secrets.php';
$s = file_get_contents($f);
$nuevo = "\$config['interrapidisimo'] = array(\n"
    . "    'base_url'      => " . var_export($mam['base_url'], true) . ",\n"
    . "    // Llaves compartidas de la cuenta corporativa (mismas de accesoriosmam), 25/08/2026.\n"
    . "    'signature'     => " . var_export($mam['signature'], true) . ",\n"
    . "    'token'         => " . var_export($mam['token'], true) . ",\n"
    . "    'id_cliente'    => '14107',\n"
    . "    'id_sucursal'   => '201003',\n"
    . "    'ciudad_origen' => '05088000', // Bello, Antioquia\n"
    . ");";
$s2 = preg_replace("/\\\$config\\['interrapidisimo'\\] = array\\(.*?\\);/s", $nuevo, $s, 1, $n);
if (!$n) { echo "no encontre el bloque\n"; exit(1); }
copy($f, '/tmp/secrets_backup_' . date('Ymd_His') . '.php');
file_put_contents($f, $s2);
unlink('/tmp/inter_block.json');
echo "   secrets.php actualizado.\n";
$config = array(); include $f;
$i = $config['interrapidisimo'];
$ch = curl_init($i['base_url'] . '/ApiVentaCredito/api/ClientesCredito/ObtenerSucursalesActivasPorCliente?idCliente=' . $i['id_cliente']);
curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => array('x-app-signature: ' . $i['signature'], 'x-app-security_token: ' . $i['token'])));
$r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo "4) Prueba de conexion -> HTTP $c\n" . substr((string)$r, 0, 300) . "\n";
FIN

echo "4) Mezclando y probando..."
$SSH $LED "sudo php /tmp/mezclar_inter.php; sudo rm -f /tmp/mezclar_inter.php"
echo
echo "Listo. Si arriba dice HTTP 200 con las sucursales, la conexion quedo activa."
