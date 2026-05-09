<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/reports/ReportRegistry.php';
require_once APPPATH . 'libraries/reports/ReportAuditLogger.php';
require_once APPPATH . 'libraries/reports/renderers/RendererFactory.php';
require_once APPPATH . 'libraries/reports/dispatchers/DispatcherFactory.php';

/**
 * Reports_v2 — controller del engine de reportes v2 (v1.30.0).
 *
 * URLs:
 *   GET  /sisvent/admin/reports/v2                       → listado de reportes accesibles
 *   GET  /sisvent/admin/reports/v2/{id}                  → HTML interactivo (default)
 *   GET  /sisvent/admin/reports/v2/{id}?format=pdf       → descarga PDF
 *   GET  /sisvent/admin/reports/v2/{id}?format=xlsx      → descarga Excel
 *   GET  /sisvent/admin/reports/v2/{id}?format=csv       → descarga CSV
 *   POST /sisvent/admin/reports/v2/{id}/email            → envía a email del cliente
 *   POST /sisvent/admin/reports/v2/{id}/whatsapp         → envía via WhatsApp
 *   GET  /sisvent/admin/reports/v2/{id}/audit            → historial de despachos del reporte
 *
 * Toda dispatch se persiste en report_dispatches (audit log). El controller
 * legacy `Reports.php` queda intacto: cada reporte se migra al engine v2
 * incrementalmente (v1.30.1, v1.30.2, v1.30.3).
 */
class Reports_v2 extends CI_Controller
{
    /** @var ReportRegistry */
    private $registry;

    /** @var ReportAuditLogger */
    private $auditLogger;

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control(); // Usuario logueado; el filtro por rol lo hace cada reporte
        $this->load->library('settings_lib');

        $this->registry = ReportRegistry::getInstance();
        $this->auditLogger = new ReportAuditLogger($this->db, $this->session);
    }

    /**
     * GET /sisvent/admin/reports/v2
     * Listado de reportes accesibles para el usuario actual.
     */
    public function index(): void
    {
        $role = (int) $this->session->userdata('user_data')['role'];
        $reports = $this->registry->listAccessible($role);

        $this->load->view('sisvent/reports/index', [
            'reports' => $reports,
            'role'    => $role,
        ]);
    }

    /**
     * GET /sisvent/admin/reports/v2/{id}
     * Renderiza el reporte en el formato pedido (?format=html|pdf|xlsx|csv).
     */
    public function show(string $id): void
    {
        try {
            $report = $this->registry->get($id);
            $this->guardAccess($report);

            $format = $this->resolveFormat($report);
            $params = $this->parseParams($report);
            $data = $report->data($params);
            $meta = $report->meta($params);

            $renderer = RendererFactory::for($format);

            // Audit log: cuenta como 'download' (no se mando a nadie, fue un render del usuario logueado)
            $this->auditLogger->logDispatch([
                'report_id' => $report->id(),
                'format' => $format,
                'channel' => 'download',
                'recipient' => null,
                'recipient_client_id' => $meta['client_id'] ?? null,
                'params' => $params,
                'status' => 'sent',
            ]);

            $renderer->render($report, $params, $data, $meta);

        } catch (Mam_exception $e) {
            $this->renderError($e);
        } catch (Throwable $e) {
            log_structured('error', 'reports.show', 'unexpected', ['report_id' => $id], $e);
            $this->renderError(new BusinessRuleException(
                'Error generando reporte: ' . $e->getMessage(),
                'REPORT_ERROR'
            ));
        }
    }

    /**
     * POST /sisvent/admin/reports/v2/{id}/email
     * Body: format, recipient, [params del reporte]
     */
    public function sendEmail(string $id): void
    {
        $this->dispatchHandler($id, 'email');
    }

    /**
     * POST /sisvent/admin/reports/v2/{id}/whatsapp
     * Body: format, recipient (E.164), [params del reporte]
     */
    public function sendWhatsapp(string $id): void
    {
        $this->dispatchHandler($id, 'whatsapp');
    }

    /**
     * GET /sisvent/admin/reports/v2/_picker?type=client|vendor|provider&q=...
     *
     * Endpoint genérico para autocomplete de filtros tipo many2one.
     * Devuelve hasta 20 resultados con id + label + meta para mostrar en
     * el dropdown.
     *
     * Inspirado en el many2one widget de Odoo: search server-side por
     * nombre/documento/teléfono, no expone toda la base al cliente.
     */
    public function picker(): void
    {
        $type = (string) $this->input->get('type');
        $q = trim((string) $this->input->get('q'));
        if (mb_strlen($q) < 2) {
            $this->jsonResponse(['results' => []]);
            return;
        }

        $results = $this->fuzzySearch($type, $q);
        $this->jsonResponse(['results' => $results]);
    }

    /**
     * Search en 3 pases (estilo Odoo/SAP):
     *   1. Multi-token LIKE — split q por espacios, todos los tokens deben matchear.
     *      "lujos marin" → matchea "Lujos Marinilla" Y "Marinilla Lujos" (orden libre).
     *   2. LIKE substring exacto — fallback amplio sobre el query completo.
     *   3. SOUNDEX fonético — captura typos como "genezi" → "Genesis".
     *
     * Resultados unidos por id, hasta 20. Los del pass 1+2 (exactos) van primero,
     * los del pass 3 (fonéticos) al final como sugerencia.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fuzzySearch(string $type, string $q): array
    {
        $tokens = array_filter(preg_split('/\s+/', $q));
        $exactMatches = $this->searchExact($type, $q, $tokens);
        $found = array_column($exactMatches, 'id');
        $merged = $exactMatches;

        // Si encontramos pocos resultados exactos, intentar fonético
        if (count($merged) < 10) {
            $phonetic = $this->searchPhonetic($type, $q, $found);
            $merged = array_merge($merged, $phonetic);
        }

        return array_slice($merged, 0, 20);
    }

    /**
     * Pass 1+2: multi-token LIKE + substring LIKE.
     *
     * @param string[] $tokens
     * @return array<int, array<string, mixed>>
     */
    private function searchExact(string $type, string $q, array $tokens): array
    {
        $like = '%' . $q . '%';

        // Multi-token: cada token debe estar presente en el campo de búsqueda principal
        $tokenWhere = '';
        $tokenArgs = [];
        $primaryField = $type === 'vendor' ? 'name' : 'name';
        if (count($tokens) > 1) {
            $parts = [];
            foreach ($tokens as $t) {
                $parts[] = "$primaryField LIKE ?";
                $tokenArgs[] = '%' . $t . '%';
            }
            $tokenWhere = '(' . implode(' AND ', $parts) . ') OR ';
        }

        switch ($type) {
            case 'client':
                $rows = $this->db->query("
                    SELECT idClient AS id, name, idNum, city, cellphone
                    FROM clients
                    WHERE deleted = 0
                      AND ($tokenWhere name LIKE ? OR idNum LIKE ? OR cellphone LIKE ? OR phone LIKE ?)
                    ORDER BY
                      CASE WHEN name LIKE ? THEN 0 ELSE 1 END,
                      name ASC
                    LIMIT 20
                ", array_merge($tokenArgs, [$like, $like, $like, $like, $q . '%']))->result();
                return array_map(fn($r) => [
                    'id' => (int) $r->id,
                    'label' => $r->name,
                    'meta' => trim(($r->idNum ?? '') . ($r->city ? ' · ' . $r->city : '') . ($r->cellphone ? ' · ' . $r->cellphone : '')),
                ], $rows);

            case 'provider':
                $rows = $this->db->query("
                    SELECT idProvider AS id, name, idNum, city
                    FROM providers
                    WHERE ($tokenWhere name LIKE ? OR idNum LIKE ?)
                    ORDER BY
                      CASE WHEN name LIKE ? THEN 0 ELSE 1 END,
                      name ASC
                    LIMIT 20
                ", array_merge($tokenArgs, [$like, $like, $q . '%']))->result();
                return array_map(fn($r) => [
                    'id' => (int) $r->id,
                    'label' => $r->name,
                    'meta' => trim(($r->idNum ?? '') . ($r->city ? ' · ' . $r->city : '')),
                ], $rows);

            case 'vendor':
                // users.idUser es varchar (PK), no int. users.email reemplaza
                // al inexistente uname.
                $rows = $this->db->query("
                    SELECT idUser AS id, name, email
                    FROM users
                    WHERE deleted = 0 AND role = 3
                      AND ($tokenWhere name LIKE ? OR idUser LIKE ? OR email LIKE ?)
                    ORDER BY
                      CASE WHEN name LIKE ? THEN 0 ELSE 1 END,
                      name ASC
                    LIMIT 20
                ", array_merge($tokenArgs, [$like, $like, $like, $q . '%']))->result();
                return array_map(fn($r) => [
                    'id' => (string) $r->id,
                    'label' => $r->name ?: $r->id,
                    'meta' => $r->email ?: $r->id,
                ], $rows);

            case 'product':
                // products.idProduct es varchar (codigo, ej: ACS-F5-1).
                // Multi-token aplica sobre la descripcion. Tambien matchea
                // por idProduct directamente para cuando escribis un codigo
                // o parte (ej: "ACS-DRN").
                $tokenWhereDesc = '';
                $tokenArgsDesc = [];
                if (count($tokens) > 1) {
                    $parts = [];
                    foreach ($tokens as $t) {
                        $parts[] = "description LIKE ?";
                        $tokenArgsDesc[] = '%' . $t . '%';
                    }
                    $tokenWhereDesc = '(' . implode(' AND ', $parts) . ') OR ';
                }
                $rows = $this->db->query("
                    SELECT idProduct AS id, description, cost_cop, COALESCE(price, price_base) AS price
                    FROM products
                    WHERE deleted = 0
                      AND ($tokenWhereDesc idProduct LIKE ? OR description LIKE ?)
                    ORDER BY
                      CASE WHEN idProduct LIKE ? THEN 0 ELSE 1 END,
                      idProduct ASC
                    LIMIT 20
                ", array_merge($tokenArgsDesc, [$like, $like, $q . '%']))->result();
                return array_map(fn($r) => [
                    'id' => (string) $r->id,
                    'label' => $r->id,
                    'meta' => trim(($r->description ?? '') . ($r->price ? ' · $' . number_format((float)$r->price, 0, ',', '.') : '')),
                ], $rows);

            default:
                throw new ValidationException("Tipo de picker '$type' no soportado", 'INVALID_PICKER_TYPE');
        }
    }

    /**
     * Pass 3: SOUNDEX fonético. Atrapa typos: "genezi" tiene mismo SOUNDEX
     * que "Genesis" (G520), así sale el cliente correcto aunque escribas mal.
     *
     * Excluye ids ya encontrados en pass 1+2 para no duplicar.
     *
     * @param int[] $excludeIds
     * @return array<int, array<string, mixed>>
     */
    private function searchPhonetic(string $type, string $q, array $excludeIds): array
    {
        // SOUNDEX requiere palabra única; tomamos la primera para la comparación
        $first = preg_split('/\s+/', $q)[0] ?? $q;
        if (mb_strlen($first) < 3) return []; // SOUNDEX poco útil con <3 chars

        $excludeSql = !empty($excludeIds) ? 'AND id NOT IN (' . implode(',', array_map('intval', $excludeIds)) . ')' : '';

        switch ($type) {
            case 'client':
                $sql = str_replace('id NOT IN', 'idClient NOT IN', $excludeSql);
                $rows = $this->db->query("
                    SELECT idClient AS id, name, idNum, city, cellphone
                    FROM clients
                    WHERE deleted = 0 AND SOUNDEX(name) = SOUNDEX(?) $sql
                    ORDER BY name ASC
                    LIMIT 10
                ", [$first])->result();
                return array_map(fn($r) => [
                    'id' => (int) $r->id,
                    'label' => $r->name . '  ⊙', // marca visual de match fonético
                    'meta' => trim(($r->idNum ?? '') . ($r->city ? ' · ' . $r->city : '') . '  ·  similar fonéticamente'),
                ], $rows);

            case 'provider':
                $sql = str_replace('id NOT IN', 'idProvider NOT IN', $excludeSql);
                $rows = $this->db->query("
                    SELECT idProvider AS id, name, idNum, city
                    FROM providers
                    WHERE SOUNDEX(name) = SOUNDEX(?) $sql
                    ORDER BY name ASC
                    LIMIT 10
                ", [$first])->result();
                return array_map(fn($r) => [
                    'id' => (int) $r->id,
                    'label' => $r->name . '  ⊙',
                    'meta' => trim(($r->idNum ?? '') . ($r->city ? ' · ' . $r->city : '') . '  ·  similar fonéticamente'),
                ], $rows);

            case 'vendor':
                // users.idUser es varchar — el intval del exclude lo rompe;
                // construimos exclude string-safe via escape() de CI.
                $vendorExclude = '';
                if (!empty($excludeIds)) {
                    $escaped = array_map(fn($id) => $this->db->escape((string)$id), $excludeIds);
                    $vendorExclude = 'AND idUser NOT IN (' . implode(',', $escaped) . ')';
                }
                $rows = $this->db->query("
                    SELECT idUser AS id, name, email
                    FROM users
                    WHERE deleted = 0 AND role = 3 AND SOUNDEX(name) = SOUNDEX(?) $vendorExclude
                    ORDER BY name ASC
                    LIMIT 10
                ", [$first])->result();
                return array_map(fn($r) => [
                    'id' => (string) $r->id,
                    'label' => ($r->name ?: $r->id) . '  ⊙',
                    'meta' => ($r->email ?: $r->id) . '  ·  similar fonéticamente',
                ], $rows);

            case 'product':
                // SOUNDEX sobre description (idProduct es codigo, no fonetico).
                $productExclude = '';
                if (!empty($excludeIds)) {
                    $escaped = array_map(fn($id) => $this->db->escape((string)$id), $excludeIds);
                    $productExclude = 'AND idProduct NOT IN (' . implode(',', $escaped) . ')';
                }
                $rows = $this->db->query("
                    SELECT idProduct AS id, description, COALESCE(price, price_base) AS price
                    FROM products
                    WHERE deleted = 0 AND SOUNDEX(description) = SOUNDEX(?) $productExclude
                    ORDER BY description ASC
                    LIMIT 10
                ", [$first])->result();
                return array_map(fn($r) => [
                    'id' => (string) $r->id,
                    'label' => $r->id . '  ⊙',
                    'meta' => trim(($r->description ?? '') . '  ·  similar fonéticamente'),
                ], $rows);

            default:
                return [];
        }
    }

    /**
     * GET /sisvent/admin/reports/v2/_label?type=client&id=3
     *
     * Resuelve un id a su label legible. Usado para mostrar en el picker
     * cuando ya viene un id en la URL (ej. ?client_id=3 → mostrar
     * "Lujos Marinilla" en el input).
     */
    public function pickerLabel(): void
    {
        $type = (string) $this->input->get('type');
        // vendor + product: ids varchar; client/provider: int
        $idRaw = (string) $this->input->get('id');
        $id = in_array($type, ['vendor', 'product'], true) ? $idRaw : (int) $idRaw;
        if ($id === '' || $id === 0) {
            $this->jsonResponse(['id' => null, 'label' => null, 'meta' => null]);
            return;
        }

        $row = null;
        switch ($type) {
            case 'client':
                $row = $this->db->query("SELECT idClient AS id, name, idNum, city, cellphone FROM clients WHERE idClient = ? AND deleted = 0", [$id])->row();
                if ($row) {
                    $this->jsonResponse([
                        'id' => (int) $row->id,
                        'label' => $row->name,
                        'meta' => trim(($row->idNum ?? '') . ($row->city ? ' · ' . $row->city : '')),
                    ]);
                    return;
                }
                break;
            case 'provider':
                $row = $this->db->query("SELECT idProvider AS id, name, idNum, city FROM providers WHERE idProvider = ?", [$id])->row();
                if ($row) {
                    $this->jsonResponse([
                        'id' => (int) $row->id,
                        'label' => $row->name,
                        'meta' => trim(($row->idNum ?? '') . ($row->city ? ' · ' . $row->city : '')),
                    ]);
                    return;
                }
                break;
            case 'vendor':
                $row = $this->db->query("SELECT idUser AS id, name, email FROM users WHERE idUser = ? AND deleted = 0", [$id])->row();
                if ($row) {
                    $this->jsonResponse([
                        'id' => (string) $row->id,
                        'label' => $row->name ?: $row->id,
                        'meta' => $row->email ?: $row->id,
                    ]);
                    return;
                }
                break;
            case 'product':
                $row = $this->db->query("SELECT idProduct AS id, description FROM products WHERE idProduct = ? AND deleted = 0", [$idRaw])->row();
                if ($row) {
                    $this->jsonResponse([
                        'id' => (string) $row->id,
                        'label' => $row->id,
                        'meta' => $row->description ?? '',
                    ]);
                    return;
                }
                break;
        }

        $this->jsonResponse(['id' => null, 'label' => null, 'meta' => null]);
    }

    /**
     * GET /sisvent/admin/reports/v2/{id}/audit
     * Historial de despachos del reporte (últimos 100).
     */
    public function audit(string $id): void
    {
        try {
            $report = $this->registry->get($id);
            $this->guardAccess($report);

            $history = $this->auditLogger->history($report->id(), 100);

            $this->load->view('sisvent/reports/audit', [
                'report' => $report,
                'history' => $history,
            ]);
        } catch (Mam_exception $e) {
            $this->renderError($e);
        }
    }

    // ─── helpers ──────────────────────────────────────────────────────────

    private function dispatchHandler(string $id, string $channel): void
    {
        try {
            $report = $this->registry->get($id);
            $this->guardAccess($report);

            $format = $this->input->post('format') ?: 'pdf';
            $recipient = trim((string) $this->input->post('recipient'));
            if ($recipient === '') {
                throw new ValidationException(
                    'Recipient requerido',
                    'MISSING_RECIPIENT',
                    ['recipient' => 'Email o phone obligatorio']
                );
            }

            // Validar que el reporte permite este canal
            if (!in_array($channel, $report->availableChannels(), true)) {
                throw new BusinessRuleException(
                    "Reporte '$id' no permite envío por $channel",
                    'CHANNEL_NOT_ALLOWED'
                );
            }

            // Validar que el formato es válido para el reporte
            if (!in_array($format, $report->availableFormats(), true)) {
                throw new ValidationException(
                    "Formato '$format' no soportado por el reporte",
                    'INVALID_FORMAT'
                );
            }

            $params = $this->parseParams($report);
            $data = $report->data($params);
            $meta = $report->meta($params);

            $renderer = RendererFactory::for($format);
            $binary = $renderer->renderToString($report, $params, $data, $meta);

            $dispatcher = DispatcherFactory::for($channel, $this->auditLogger, $this->settings_lib);

            if (!$dispatcher->isEnabled()) {
                throw new BusinessRuleException(
                    "Dispatcher '$channel' deshabilitado en este entorno",
                    'DISPATCHER_DISABLED'
                );
            }

            $logId = $dispatcher->send($report, $params, $meta, $format, $binary, $recipient);

            $this->jsonResponse([
                'status' => 'ok',
                'dispatch_id' => $logId,
                'message' => 'Reporte enviado',
            ]);

        } catch (Mam_exception $e) {
            $this->jsonResponse([
                'status' => 'error',
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'errors' => method_exists($e, 'getErrors') ? $e->getErrors() : null,
            ], 422);
        } catch (Throwable $e) {
            log_structured('error', 'reports.dispatch', 'unexpected', [
                'report_id' => $id,
                'channel' => $channel,
            ], $e);
            $this->jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function guardAccess(ReportInterface $report): void
    {
        $role = (int) $this->session->userdata('user_data')['role'];
        if ($report instanceof AbstractReport) {
            if (!$report->userCanAccess($role)) {
                throw new AuthorizationException(
                    "Sin permiso para reporte '{$report->id()}'",
                    'REPORT_FORBIDDEN'
                );
            }
        } else {
            // role 1 + matriz override 'reportes_v2' bypassan el array hardcoded
            $hasOverride = (function_exists('has_permission') && has_permission('reportes_v2'));
            if ($role !== 1 && !$hasOverride && !in_array($role, $report->requiredRoles(), true)) {
                throw new AuthorizationException(
                    "Sin permiso para reporte '{$report->id()}'",
                    'REPORT_FORBIDDEN'
                );
            }
        }
    }

    private function resolveFormat(ReportInterface $report): string
    {
        $format = $this->input->get('format') ?: $report->defaultFormat();
        if (!in_array($format, $report->availableFormats(), true)) {
            throw new ValidationException(
                "Formato '$format' no soportado por el reporte",
                'INVALID_FORMAT'
            );
        }
        return $format;
    }

    /**
     * Parsea filtros desde GET/POST aplicando defaults de filterDefinitions().
     *
     * @return array<string, mixed>
     */
    private function parseParams(ReportInterface $report): array
    {
        $params = [];
        foreach ($report->filterDefinitions() as $f) {
            $name = $f['name'];
            $val = $this->input->get_post($name);
            if ($val === false || $val === null || $val === '') {
                $val = $f['default'] ?? null;
            }
            $params[$name] = $val;
        }
        return $params;
    }

    private function renderError(Throwable $e): void
    {
        $code = method_exists($e, 'getErrorCode') ? $e->getErrorCode() : 'ERROR';
        $http = $code === 'REPORT_NOT_FOUND' ? 404 : ($code === 'REPORT_FORBIDDEN' ? 403 : 422);
        http_response_code($http);
        $this->load->view('sisvent/reports/error', [
            'message' => $e->getMessage(),
            'code'    => $code,
        ]);
    }

    private function jsonResponse(array $data, int $http = 200): void
    {
        http_response_code($http);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
