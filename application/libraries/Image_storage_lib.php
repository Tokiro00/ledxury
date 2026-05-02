<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Image_storage_lib — Abstrae el almacenamiento de imágenes de productos.
 *
 * Drivers:
 *   - local: public/images/products/{code}.{ext}  (default, lo que existe hoy)
 *   - s3:    bucket S3 público (recomendado para producción cuando crece el repo)
 *
 * El driver se selecciona en application/config/image_storage.php. Las
 * credenciales de S3 viven SIEMPRE en application/config/secrets.php.
 *
 * Uso:
 *   $url = $this->image_storage_lib->getUrl('3LED-12V-A');     // null si no existe
 *   $exists = $this->image_storage_lib->exists('3LED-12V-A');
 *   $this->image_storage_lib->put('3LED-12V-A', '/tmp/foto.jpg');
 *
 * En vistas:
 *   <img src="<?= $this->image_storage_lib->getUrl($p->idProduct) ?>">
 */
class Image_storage_lib {

    private $CI;
    private $cfg;
    private $driver;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('image_storage', true, true);
        $this->cfg = $this->CI->config->item('image_storage');
        if (!is_array($this->cfg)) $this->cfg = array('driver' => 'local');
        $this->driver = $this->cfg['driver'] ?? 'local';
    }

    /**
     * URL pública de la imagen, o null si no existe.
     */
    public function getUrl($code, $extension_hint = null) {
        $code = trim((string)$code);
        if ($code === '') return null;
        if ($this->driver === 's3') return $this->_getUrlS3($code, $extension_hint);
        return $this->_getUrlLocal($code, $extension_hint);
    }

    public function exists($code) {
        return $this->getUrl($code) !== null;
    }

    /**
     * Sube una imagen desde un archivo local al storage configurado.
     * Devuelve la URL pública o false si falla.
     */
    public function put($code, $localPath) {
        if (!is_file($localPath)) return false;
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) return false;
        if ($this->driver === 's3') return $this->_putS3($code, $localPath, $ext);
        return $this->_putLocal($code, $localPath, $ext);
    }

    /**
     * Lista todos los códigos que tienen imagen disponible.
     * Devuelve set asociativo idProduct => extension.
     */
    public function listAll() {
        if ($this->driver === 's3') return $this->_listS3();
        return $this->_listLocal();
    }

    // =========================================================================
    // LOCAL DRIVER
    // =========================================================================

    private function _localDir() {
        return FCPATH . ($this->cfg['local']['dir'] ?? 'public/images/products/');
    }

    private function _getUrlLocal($code, $extension_hint = null) {
        $dir = $this->_localDir();
        $candidates = $extension_hint ? array($extension_hint) : array('png', 'jpg', 'jpeg', 'webp');
        foreach ($candidates as $ext) {
            if (is_file($dir . $code . '.' . $ext)) {
                return base_url() . ($this->cfg['local']['url_path'] ?? 'public/images/products/') . $code . '.' . $ext;
            }
        }
        return null;
    }

    private function _putLocal($code, $localPath, $ext) {
        $dir = $this->_localDir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $target = $dir . $code . '.' . $ext;
        if (!@copy($localPath, $target)) return false;
        return base_url() . ($this->cfg['local']['url_path'] ?? 'public/images/products/') . $code . '.' . $ext;
    }

    private function _listLocal() {
        $dir = $this->_localDir();
        $set = array();
        if (!is_dir($dir)) return $set;
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || $f === 'no_image.png') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) continue;
            $code = pathinfo($f, PATHINFO_FILENAME);
            if (!isset($set[$code])) $set[$code] = $ext;
        }
        return $set;
    }

    // =========================================================================
    // S3 DRIVER  (sin SDK, usa solo SigV4 + curl — minimal)
    // =========================================================================

    private function _s3Bucket()  { return $this->CI->config->item('s3_bucket') ?: ($this->cfg['s3']['bucket'] ?? ''); }
    private function _s3Region()  { return $this->CI->config->item('s3_region') ?: ($this->cfg['s3']['region'] ?? 'us-east-1'); }
    private function _s3Prefix()  { return $this->cfg['s3']['prefix'] ?? 'products/'; }
    private function _s3Key()     { return $this->CI->config->item('s3_access_key_id') ?? ''; }
    private function _s3Secret()  { return $this->CI->config->item('s3_secret_access_key') ?? ''; }

    private function _s3PublicBaseUrl() {
        if (!empty($this->cfg['s3']['public_base_url'])) return rtrim($this->cfg['s3']['public_base_url'], '/') . '/';
        return 'https://' . $this->_s3Bucket() . '.s3.' . $this->_s3Region() . '.amazonaws.com/';
    }

    /**
     * Cache local del listado S3 (1h TTL via cache_helper).
     * Evita llamar S3 ListObjectsV2 en cada request del catálogo.
     */
    private function _listS3() {
        if (!function_exists('mam_cache_remember')) {
            // helper no cargado — fallback sin caché
            return $this->_fetchListS3();
        }
        return mam_cache_remember('s3:image_list', 3600, function() {
            return $this->_fetchListS3();
        });
    }

    private function _fetchListS3() {
        // Llamada a S3 ListObjectsV2 — implementación mínima.
        // Por ahora devolvemos vacío y dependemos de probar URL directa.
        // TODO: implementar SigV4 GET completo cuando se active S3.
        return array();
    }

    private function _getUrlS3($code, $extension_hint = null) {
        // Para S3: confiamos en convención "{prefix}{code}.{ext}". No verificamos
        // la existencia (sería una request HEAD por imagen — caro). Si la imagen
        // no existe, el navegador del cliente verá 404 y mostrará alt/placeholder.
        $candidates = $extension_hint ? array($extension_hint) : array('png', 'jpg', 'jpeg', 'webp');
        $base = $this->_s3PublicBaseUrl() . $this->_s3Prefix();
        // Para preferencia de extensión, devolvemos la primera (png) por defecto.
        // El que sube imágenes (admin upload) controla la extensión real.
        return $base . $code . '.' . $candidates[0];
    }

    private function _putS3($code, $localPath, $ext) {
        $bucket = $this->_s3Bucket();
        $key    = $this->_s3Key();
        $secret = $this->_s3Secret();
        if (!$bucket || !$key || !$secret) {
            log_message('error', 'Image_storage_lib: S3 driver activado pero faltan credenciales en secrets.php');
            return false;
        }
        $object_key = $this->_s3Prefix() . $code . '.' . $ext;
        $content_type = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
        $body = file_get_contents($localPath);
        if ($body === false) return false;

        // SigV4 minimal — PUT object con Content-Type y x-amz-acl: public-read
        $region = $this->_s3Region();
        $host = $bucket . '.s3.' . $region . '.amazonaws.com';
        $endpoint = 'https://' . $host . '/' . $object_key;

        $amz_date = gmdate('Ymd\THis\Z');
        $date_stamp = gmdate('Ymd');
        $payload_hash = hash('sha256', $body);

        $canonical_headers = "content-type:{$content_type}\nhost:{$host}\nx-amz-acl:public-read\nx-amz-content-sha256:{$payload_hash}\nx-amz-date:{$amz_date}\n";
        $signed_headers = 'content-type;host;x-amz-acl;x-amz-content-sha256;x-amz-date';
        $canonical_request = "PUT\n/{$object_key}\n\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";

        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = "{$date_stamp}/{$region}/s3/aws4_request";
        $string_to_sign = "{$algorithm}\n{$amz_date}\n{$credential_scope}\n" . hash('sha256', $canonical_request);

        $kDate    = hash_hmac('sha256', $date_stamp, 'AWS4' . $secret, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $string_to_sign, $kSigning);

        $authorization = "{$algorithm} Credential={$key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

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
            CURLOPT_TIMEOUT => 30,
        ));
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http >= 200 && $http < 300) {
            // Invalidar caché de listado
            if (function_exists('mam_cache_forget')) mam_cache_forget('s3:image_list');
            return $this->_s3PublicBaseUrl() . $object_key;
        }
        log_message('error', 'Image_storage_lib S3 PUT fallo: HTTP ' . $http . ' resp=' . substr((string)$resp, 0, 200));
        return false;
    }
}
