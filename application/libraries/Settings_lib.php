<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Settings_lib — lectura y escritura de settings de negocio almacenados
 * en la tabla `company_settings`.
 *
 * Reemplaza constantes hardcodeadas en controllers (META_VENTAS, etc.)
 * para que un admin las edite desde la UI sin necesidad de deploy.
 *
 * Ejemplos:
 *
 *     // Leer con cast automatico segun `type` en la tabla
 *     $metaVentas = $this->settings_lib->get('meta_ventas');       // int
 *     $stores     = $this->settings_lib->get('stores_mde');        // array
 *
 *     // Con fallback si la clave no existe en BD
 *     $x = $this->settings_lib->get('nope', 42);                    // 42
 *
 *     // Escribir (registra old/new en company_settings_log)
 *     $this->settings_lib->set('meta_ventas', 600000000, $uname);
 *
 *     // Devolver todos los settings como array asociativo casteado
 *     $all = $this->settings_lib->all();
 *
 * Cache: en memoria del request. Al primer get() se hace un SELECT *
 * de toda la tabla y se guarda. Lecturas subsiguientes no tocan DB.
 * set() invalida la cache.
 */
class Settings_lib
{
    /** @var \CI_Controller */
    private $CI;

    /** @var array<string,mixed>|null Cache por request. null = aun no cargada. */
    private $cache = null;

    /** @var array<string,string> setting_key => type */
    private $types = [];

    public function __construct()
    {
        $this->CI = get_instance();
        $this->CI->load->database();
    }

    /**
     * Lee un setting y lo devuelve casteado al tipo registrado.
     *
     * @param string $key
     * @param mixed  $default Si la clave no existe en BD, se devuelve esto
     *                       tras loguear warning (log_structured si esta
     *                       disponible, log_message fallback).
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $this->loadCache();

        if (!array_key_exists($key, $this->cache)) {
            $this->logMissing($key, $default);
            return $default;
        }

        return $this->cache[$key];
    }

    /**
     * Escribe un setting. Invalida cache y registra el cambio en
     * company_settings_log (old_value, new_value, changed_by).
     *
     * Si el setting no existe todavia en la tabla, lo crea con type
     * inferido del valor. No infiere entre 'int' y 'decimal' de forma
     * perfecta — si queres decimal exacto, insertalo a mano por SQL o
     * expone un parametro opcional $type.
     *
     * @param string       $key
     * @param mixed        $value
     * @param string       $userId  Quien hace el cambio (para el log)
     * @param string|null  $type    Opcional: fuerza el tipo al insertar nuevo
     * @return void
     */
    public function set($key, $value, $userId, $type = null)
    {
        $this->loadCache();

        // Leer el valor actual para el log
        $current = $this->CI->db->select('value, type')
            ->from('company_settings')
            ->where('setting_key', $key)
            ->get()->row();

        $resolvedType = $type;
        if ($resolvedType === null) {
            $resolvedType = $current ? $current->type : $this->inferType($value);
        }

        $serialized = $this->serialize($value, $resolvedType);

        $this->CI->db->trans_start();

        if ($current) {
            $this->CI->db->where('setting_key', $key)->update('company_settings', [
                'value'      => $serialized,
                'type'       => $resolvedType,
                'updated_by' => $userId,
            ]);
        } else {
            $this->CI->db->insert('company_settings', [
                'setting_key' => $key,
                'value'       => $serialized,
                'type'        => $resolvedType,
                'updated_by'  => $userId,
            ]);
        }

        $this->CI->db->insert('company_settings_log', [
            'setting_key' => $key,
            'old_value'   => $current ? $current->value : null,
            'new_value'   => $serialized,
            'changed_by'  => $userId,
        ]);

        $this->CI->db->trans_complete();

        // Invalidar cache: se reconstruira en el proximo get()
        $this->cache = null;
    }

    /**
     * Devuelve todos los settings casteados como array asociativo.
     *
     * @return array<string,mixed>
     */
    public function all()
    {
        $this->loadCache();
        return $this->cache;
    }

    /**
     * Devuelve el type registrado para un key ('int', 'decimal', 'json', 'string').
     * Util para la UI admin al decidir como renderizar el editor.
     *
     * @param string $key
     * @return string|null
     */
    public function getType($key)
    {
        $this->loadCache();
        return $this->types[$key] ?? null;
    }

    /**
     * Fuerza refresh de la cache en el proximo get().
     */
    public function reset()
    {
        $this->cache = null;
        $this->types = [];
    }

    // ───────────────────────────────────────────────────────────────

    private function loadCache()
    {
        if ($this->cache !== null) return;

        $this->cache = [];
        $this->types = [];

        $rows = $this->CI->db->select('setting_key, value, type')
            ->from('company_settings')
            ->get()->result();

        foreach ($rows as $r) {
            $this->cache[$r->setting_key] = $this->cast($r->value, $r->type);
            $this->types[$r->setting_key] = $r->type;
        }
    }

    /**
     * Castea el string de la BD al tipo PHP correspondiente.
     */
    private function cast($value, $type)
    {
        if ($value === null) return null;
        switch ($type) {
            case 'int':     return (int)$value;
            case 'decimal': return (float)$value;
            case 'json':    $decoded = json_decode($value, true);
                            return is_array($decoded) ? $decoded : null;
            case 'string':
            default:        return (string)$value;
        }
    }

    /**
     * Convierte el valor PHP al formato string para almacenar.
     */
    private function serialize($value, $type)
    {
        switch ($type) {
            case 'int':     return (string)(int)$value;
            case 'decimal': return (string)(float)$value;
            case 'json':    return json_encode($value, JSON_UNESCAPED_UNICODE);
            case 'string':
            default:        return (string)$value;
        }
    }

    /**
     * Inferencia trivial — se usa solo cuando se crea un setting nuevo
     * sin pasar $type explicito. No distingue int vs decimal perfecto:
     * un entero literal lo resuelve como 'int'.
     */
    private function inferType($value)
    {
        if (is_array($value))      return 'json';
        if (is_int($value))        return 'int';
        if (is_float($value))      return 'decimal';
        if (is_bool($value))       return 'int';
        return 'string';
    }

    private function logMissing($key, $default)
    {
        $msg = "[settings_lib] missing key='{$key}' default=" . var_export($default, true);
        if (function_exists('log_structured')) {
            log_structured('warning', 'settings', 'missing_key', [
                'key'     => $key,
                'default' => $default,
            ]);
        } else {
            log_message('warning', $msg);
        }
    }
}
