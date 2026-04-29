<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cache helper sencillo basado en archivo (sin Redis ni APCu).
 * Pensado para queries agregadas del Dashboard que se ejecutan en cada page load
 * y traen los mismos datos durante minutos. Reduce dramáticamente la carga de DB.
 *
 * Uso típico:
 *   $stats = mam_cache_remember('dashboard:ventas_hoy:store_1', 300, function() {
 *       return $this->compute_ventas_hoy(1);
 *   });
 *
 * Si el caller necesita acceso a `$this`, debe pasar el callback con use(...) o
 * usar funciones globales. Para code in controllers, mam_cache_remember() es lo
 * más práctico — el callback ejecuta solo si la entrada cacheada está expirada.
 */

if (!function_exists('_mam_cache_dir')) {
    function _mam_cache_dir() {
        $dir = APPPATH . 'cache/mam/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }
}

if (!function_exists('_mam_cache_path')) {
    function _mam_cache_path($key) {
        return _mam_cache_dir() . md5($key) . '.cache';
    }
}

/**
 * Lee del caché. Retorna $default si no existe o expiró.
 */
if (!function_exists('mam_cache_get')) {
    function mam_cache_get($key, $default = null) {
        $f = _mam_cache_path($key);
        if (!is_file($f)) return $default;
        $raw = @file_get_contents($f);
        if ($raw === false) return $default;
        $data = @unserialize($raw);
        if (!is_array($data) || !isset($data['expires'], $data['value'])) return $default;
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($f); // expirado
            return $default;
        }
        return $data['value'];
    }
}

/**
 * Guarda en caché por $ttl segundos. ttl=0 → sin expiración.
 */
if (!function_exists('mam_cache_set')) {
    function mam_cache_set($key, $value, $ttl = 300) {
        $f = _mam_cache_path($key);
        $data = array(
            'expires' => $ttl > 0 ? (time() + $ttl) : 0,
            'value'   => $value,
        );
        return @file_put_contents($f, serialize($data), LOCK_EX) !== false;
    }
}

/**
 * Borra una entrada (o todas si pasas null para invalidar todo).
 */
if (!function_exists('mam_cache_forget')) {
    function mam_cache_forget($key = null) {
        if ($key === null) {
            $dir = _mam_cache_dir();
            foreach (glob($dir . '*.cache') as $f) @unlink($f);
            return true;
        }
        $f = _mam_cache_path($key);
        return is_file($f) ? @unlink($f) : true;
    }
}

/**
 * "remember": si está en caché, devuelve. Si no, ejecuta el callback, guarda, y devuelve.
 *
 * @param string   $key       Clave de caché
 * @param int      $ttl       Segundos de vida
 * @param callable $callback  Función que produce el valor cuando hay miss
 */
if (!function_exists('mam_cache_remember')) {
    function mam_cache_remember($key, $ttl, $callback) {
        $hit = mam_cache_get($key, '__MAM_CACHE_MISS__');
        if ($hit !== '__MAM_CACHE_MISS__') return $hit;
        $value = $callback();
        mam_cache_set($key, $value, $ttl);
        return $value;
    }
}
