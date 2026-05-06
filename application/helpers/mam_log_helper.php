<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * mam_log_helper — helpers para logging estructurado.
 *
 * La meta: que todas las entradas de log en application/logs/ sean JSON
 * por linea, con campos consistentes (ts, level, module, action, user_id,
 * data). Asi queda parseable con jq, grep, o un shipper tipo Filebeat si
 * se monta despues.
 *
 * Motivacion: hoy los log_message del proyecto varian de formato entre
 * modulos — algunos solo texto, otros con prefijos tipo [BUDGET_APPROVAL],
 * otros embebiendo datos en el mensaje. Imposible extraer metricas o
 * armar dashboards.
 *
 * Uso:
 *
 *     log_structured('error', 'invoices.void', 'permission_denied', [
 *         'invoice_id' => 123,
 *         'requested_by' => 'uname-42',
 *     ]);
 *
 *     log_structured('info', 'ratelimit', '429_emitted', [
 *         'key' => 'login:ip:1.2.3.4',
 *         'count' => 21,
 *     ]);
 *
 * No agrega stack trace por default — si hace falta, incluirlo en `data`:
 *
 *     log_structured('error', 'budgets.embalar', 'unexpected', [
 *         'budget_id' => \$id,
 *         'trace'     => \$e->getTraceAsString(),
 *     ], \$e);
 *
 * Migracion: el codigo existente NO se cambia de golpe. Los call sites
 * se migran incrementalmente cuando se tocan por otro motivo.
 */

if (!function_exists('log_structured')) {
    /**
     * Registra una entrada estructurada en los logs de CodeIgniter.
     *
     * @param string               $level   'debug' | 'info' | 'warning' | 'error'
     * @param string               $module  modulo.sub-modulo (p. ej. 'invoices.void')
     * @param string               $action  evento discreto (p. ej. 'permission_denied')
     * @param array<string,mixed>  $data    payload adicional (tipos serializables)
     * @param \Throwable|null      $throwable si hay, se agregan exception.class/message/file/line
     * @return void
     */
    function log_structured($level, $module, $action, array $data = [], ?\Throwable $throwable = null)
    {
        // get_instance() devuelve por valor; no asignar por referencia
        // para no generar "Only variables should be assigned by reference".
        $CI = get_instance();

        $userId = null;
        if ($CI && isset($CI->session)) {
            $ud = $CI->session->userdata('user_data');
            if (is_array($ud) && !empty($ud['uname'])) {
                $userId = $ud['uname'];
            }
        }

        $entry = [
            'ts'      => date('c'),
            'level'   => $level,
            'module'  => $module,
            'action'  => $action,
            'user_id' => $userId,
        ];

        if ($throwable !== null) {
            $entry['exception'] = [
                'class'   => get_class($throwable),
                'message' => $throwable->getMessage(),
                'file'    => $throwable->getFile(),
                'line'    => $throwable->getLine(),
            ];
        }

        if (!empty($data)) {
            $entry['data'] = $data;
        }

        // JSON_UNESCAPED_UNICODE para que los acentos esten legibles;
        // JSON_PARTIAL_OUTPUT_ON_ERROR para no romper el log si un objeto
        // no serializa limpio (devuelve null en los campos problematicos).
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        // Si json_encode falla igual (null), hacer fallback a un mensaje
        // minimo en texto para no perder el evento.
        if ($json === false || $json === null) {
            $json = '{"ts":"' . date('c') . '","level":"' . $level
                  . '","module":"' . $module . '","action":"' . $action
                  . '","error":"log_encode_failed"}';
        }

        // CI3 nativo solo conoce ERROR/DEBUG/INFO en su map de niveles
        // (system/core/Log.php::$_levels). Si pasamos 'warning' el core
        // emite un PHP Warning "Undefined array key WARNING". El campo
        // `level` queda intacto en el JSON (por eso vemos "warning" en
        // el archivo de log igual); solo cambiamos el nivel SYSLOG con
        // que CI escribe la linea.
        $ciLevel = (strtolower($level) === 'warning') ? 'error' : $level;
        log_message($ciLevel, $json);
    }
}
