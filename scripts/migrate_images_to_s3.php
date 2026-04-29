<?php
/**
 * scripts/migrate_images_to_s3.php
 *
 * Sube todas las imágenes de public/images/products/ al bucket S3 configurado
 * en application/config/image_storage.php (driver = 's3').
 *
 * Uso (desde producción):
 *   1) Configurar credenciales en application/config/secrets.php:
 *        $config['s3_access_key_id']     = 'AKIA...';
 *        $config['s3_secret_access_key'] = '...';
 *        $config['s3_bucket']            = 'ledxury-products';
 *        $config['s3_region']            = 'us-east-1';
 *   2) En application/config/image_storage.php cambiar driver = 's3'
 *   3) Ejecutar:  cd /var/www/html && sudo php scripts/migrate_images_to_s3.php
 *
 * El script es idempotente: si una imagen ya está en S3 (mismo key), la sobrescribe
 * (PutObject siempre actualiza). Para verificar primero usar --dry-run.
 *
 * Después de migrar exitosamente, las imágenes locales se pueden borrar:
 *   rm -rf public/images/products/*    (sólo después de verificar que el catálogo
 *                                       carga las imágenes desde S3)
 */

// Bootstrap CodeIgniter
$webroot = realpath(__DIR__ . '/..');
chdir($webroot);
define('FCPATH', $webroot . '/');
define('BASEPATH', $webroot . '/system/');
define('APPPATH', $webroot . '/application/');
define('VIEWPATH', APPPATH . 'views/');
define('SYSDIR', 'system');
define('ENVIRONMENT', 'production');

$dryRun = in_array('--dry-run', $argv ?? array(), true);

// Args
$dir = FCPATH . 'public/images/products/';
if (!is_dir($dir)) {
    fwrite(STDERR, "Directorio no existe: $dir\n");
    exit(1);
}

// Cargar config secrets
@include APPPATH . 'config/secrets.php';
@include APPPATH . 'config/image_storage.php';

$bucket = $config['s3_bucket'] ?? ($config['image_storage']['s3']['bucket'] ?? '');
$region = $config['s3_region'] ?? ($config['image_storage']['s3']['region'] ?? 'us-east-1');
$key    = $config['s3_access_key_id'] ?? '';
$secret = $config['s3_secret_access_key'] ?? '';
$prefix = $config['image_storage']['s3']['prefix'] ?? 'products/';

if (!$bucket || !$key || !$secret) {
    fwrite(STDERR, "ERROR: faltan credenciales S3 en secrets.php (bucket={$bucket}, key=" . ($key ? 'set' : 'EMPTY') . ", secret=" . ($secret ? 'set' : 'EMPTY') . ")\n");
    exit(1);
}

echo "Bucket: {$bucket} | Region: {$region} | Prefix: {$prefix}\n";
echo $dryRun ? "Modo: DRY-RUN (no sube)\n\n" : "Modo: REAL\n\n";

$files = array();
foreach (scandir($dir) as $f) {
    if ($f === '.' || $f === '..') continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) continue;
    $files[] = $f;
}
echo "Encontradas " . count($files) . " imágenes para subir.\n\n";

$ok = 0; $fail = 0;
foreach ($files as $i => $f) {
    $path = $dir . $f;
    $size = filesize($path);
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $object_key = $prefix . $f;

    if ($dryRun) {
        echo sprintf("[%4d/%4d] DRY: %s (%d bytes)\n", $i + 1, count($files), $object_key, $size);
        $ok++;
        continue;
    }

    $body = file_get_contents($path);
    $content_type = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
    $host = $bucket . '.s3.' . $region . '.amazonaws.com';
    $endpoint = 'https://' . $host . '/' . $object_key;

    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    $payload_hash = hash('sha256', $body);

    $canonical_headers = "content-type:{$content_type}\nhost:{$host}\nx-amz-acl:public-read\nx-amz-content-sha256:{$payload_hash}\nx-amz-date:{$amz_date}\n";
    $signed_headers = 'content-type;host;x-amz-acl;x-amz-content-sha256;x-amz-date';
    $canonical_request = "PUT\n/{$object_key}\n\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";

    $credential_scope = "{$date_stamp}/{$region}/s3/aws4_request";
    $string_to_sign = "AWS4-HMAC-SHA256\n{$amz_date}\n{$credential_scope}\n" . hash('sha256', $canonical_request);

    $kDate = hash_hmac('sha256', $date_stamp, 'AWS4' . $secret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $string_to_sign, $kSigning);

    $authorization = "AWS4-HMAC-SHA256 Credential={$key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: ' . $content_type,
            'Host: ' . $host,
            'x-amz-acl: public-read',
            'x-amz-content-sha256: ' . $payload_hash,
            'x-amz-date: ' . $amz_date,
            'Authorization: ' . $authorization,
        ),
        CURLOPT_TIMEOUT => 60,
    ));
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http >= 200 && $http < 300) {
        echo sprintf("[%4d/%4d] OK:  %s (%d bytes)\n", $i + 1, count($files), $object_key, $size);
        $ok++;
    } else {
        echo sprintf("[%4d/%4d] FAIL %s — HTTP %d  %s\n", $i + 1, count($files), $object_key, $http, substr((string)$resp, 0, 200));
        $fail++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "OK:    {$ok}\n";
echo "FAIL:  {$fail}\n";
echo "Total: " . count($files) . "\n";
echo $fail === 0 ? "\n✅ Todo subido. Activa driver=s3 en image_storage.php.\n" : "\n⚠️ Algunos uploads fallaron. Revisar errores arriba.\n";
