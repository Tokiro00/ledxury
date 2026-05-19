<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ventas extends CI_Controller {

    // Fecha de corte: presupuestos pendientes anteriores son pruebas historicas, se ocultan del PWA
    const PENDING_CUTOFF_DATE = '2025-07-01';

    private $vendor_id;
    private $vendor;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('budgets_model');
        $this->load->model('invoices_model');
        $this->load->model('clients_model');
        $this->load->model('users_model');
        $this->load->model('stores_model');
        $this->load->model('products_model');
    }

    /**
     * Verificar sesión de vendedor
     */
    private function _checkAuth()
    {
        if (!is_logged_in()) {
            redirect(base_url() . 'ventas/login');
            return false;
        }
        $ud = $this->session->userdata('user_data');
        $this->vendor_id = $ud['uname'];
        $this->vendor = $this->users_model->getAnyUser($this->vendor_id);
        return true;
    }

    /**
     * Redirect a login o dashboard
     */
    public function index()
    {
        if (is_logged_in()) {
            redirect(base_url() . 'ventas/dashboard');
        } else {
            redirect(base_url() . 'ventas/login');
        }
    }

    /**
     * Login móvil
     */
    public function login()
    {
        if (is_logged_in()) {
            redirect(base_url() . 'ventas/dashboard');
            return;
        }
        $this->load->view('ventas/login');
    }

    /**
     * Validar login
     */
    public function validate()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $this->load->model('login_model');
        if ($this->login_model->login(test_input($this->input->post('uid')), $this->input->post('ups'))) {
            date_default_timezone_set("America/Bogota");
            $uid = $this->session->userdata('user_data')['uname'];
            $this->db->insert('user_activity_log', array(
                'user_id' => $uid,
                'action' => 'login_mobile',
                'ip_address' => $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s'),
            ));
            redirect(base_url() . 'ventas/dashboard');
        } else {
            $this->session->set_flashdata('login_error', 'Usuario o contraseña incorrectos');
            redirect(base_url() . 'ventas/login');
        }
    }

    /**
     * Dashboard principal del vendedor
     */
    public function dashboard()
    {
        if (!$this->_checkAuth()) return;
        date_default_timezone_set("America/Bogota");

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);

        // KPIs
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');

        // VENTAS = SOLO FACTURAS (invoices), no presupuestos.
        // Política Ledxury: la venta se cuenta cuando el bodeguero/vendedor
        // aprueba el presupuesto y se genera la factura. Mientras esté en
        // borrador (budgets state=0) o revisado (state=2), todavía no es venta.
        // Antes esto contaba budgets sin filtrar y daba cifras infladas
        // ($284M de "ventas" en un día sin actividad real).

        // Ventas hoy (facturadas)
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('invoices');
        $this->db->where('date >=', $today . ' 00:00:00');
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $sales_today = $this->db->get()->row();

        // Ventas semana (facturadas)
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('invoices');
        $this->db->where('date >=', $weekStart . ' 00:00:00');
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $sales_week = $this->db->get()->row();

        // Ventas mes (facturadas)
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('invoices');
        $this->db->where('date >=', $monthStart . ' 00:00:00');
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $sales_month = $this->db->get()->row();

        // Presupuestos aprobados este mes
        $this->db->select('COUNT(*) as count');
        $this->db->from('budgets');
        $this->db->where('state', 1);
        $this->db->where('date >=', $monthStart . ' 00:00:00');
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $approved_month = $this->db->get()->row();

        // Facturado y recaudado del mes (invoices)
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('invoices');
        $this->db->where('date >=', $monthStart . ' 00:00:00');
        $this->db->where('deleted', 0);
        $this->db->where('total >', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $billed_month = $this->db->get()->row();

        $this->db->select('COALESCE(SUM(total),0) as total');
        $this->db->from('invoices');
        $this->db->where('date >=', $monthStart . ' 00:00:00');
        $this->db->where('state', 2);
        $this->db->where('deleted', 0);
        $this->db->where('total >', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $collected_month = $this->db->get()->row();

        // Ticket promedio del mes
        $avg_ticket = ($sales_month->count > 0) ? ($sales_month->total / $sales_month->count) : 0;

        // Días hábiles restantes del mes (lunes a sábado)
        $days_left = 0;
        $last_day = (int)date('t');
        for ($d = (int)date('j') + 1; $d <= $last_day; $d++) {
            $ts = mktime(0, 0, 0, (int)date('n'), $d, (int)date('Y'));
            if ((int)date('N', $ts) !== 7) $days_left++; // excluye domingo
        }

        // Ranking del vendedor: puesto por total de ventas FACTURADAS del mes
        // (consistente con la política: solo cuenta lo facturado, no budgets)
        $ranking_position = 0;
        $ranking_total = 0;
        if (!$is_admin) {
            $rows = $this->db->select('vendorId, COALESCE(SUM(total),0) as t')
                ->from('invoices')
                ->where('date >=', $monthStart . ' 00:00:00')
                ->where('deleted', 0)
                ->group_by('vendorId')
                ->order_by('t', 'DESC')
                ->get()->result();
            $ranking_total = count($rows);
            foreach ($rows as $i => $r) {
                if ($r->vendorId === $this->vendor_id) { $ranking_position = $i + 1; break; }
            }
        }

        // Clientes inactivos (>30 días sin presupuesto)
        $inactive_clients = 0;
        if (!$is_admin) {
            $cutoff = date('Y-m-d', strtotime('-30 days'));
            $sql = "SELECT COUNT(*) AS c FROM clients c
                    WHERE c.vendor = ? AND c.deleted = 0
                    AND NOT EXISTS (
                        SELECT 1 FROM budgets b
                        WHERE b.clientId = c.idClient AND b.deleted = 0 AND b.date >= ?
                    )";
            $q = $this->db->query($sql, array($this->vendor_id, $cutoff));
            $r = $q->row();
            $inactive_clients = $r ? (int)$r->c : 0;
        }

        // Presupuestos pendientes (excluye pruebas anteriores a PENDING_CUTOFF_DATE)
        $this->db->select('COUNT(*) as count');
        $this->db->from('budgets');
        $this->db->where('state', 0);
        $this->db->where('deleted', 0);
        $this->db->where('archived', 0);
        $this->db->where('date >=', self::PENDING_CUTOFF_DATE);
        $bot_scope = $this->_resolveBotVendorScope($this->vendor_id);
        if (is_array($bot_scope) && !empty($bot_scope)) {
            $scope_ids = $bot_scope;
            if (!in_array($this->vendor_id, $scope_ids)) $scope_ids[] = $this->vendor_id;
            $this->db->where_in('vendorId', $scope_ids);
        } elseif ($bot_scope === 'all' && $is_admin) {
            // admin con scope 'all' ve todo
        } else {
            $this->db->where('vendorId', $this->vendor_id);
        }
        $pending = $this->db->get()->row();

        // Ventas fallidas del bot
        $this->db->select('COUNT(*) as count')->from('bot_sales_queue')->where('status', 'failed');
        if (!$is_admin) $this->db->where('vendor_id', $this->vendor_id);
        $failed = $this->db->get()->row();

        // Comisión del periodo actual (21 mes anterior -> 20 mes actual)
        $today_ts = time();
        $cy = (int)date('Y', $today_ts);
        $cm = (int)date('n', $today_ts);
        $cd = (int)date('j', $today_ts);
        // Si hoy es antes del 21, el periodo actual termina el 20 de este mes
        if ($cd <= 20) {
            $period_from = date('Y-m-d', mktime(0, 0, 0, $cm - 1, 21, $cy));
            $period_to = date('Y-m-d', mktime(0, 0, 0, $cm, 20, $cy));
        } else {
            $period_from = date('Y-m-d', mktime(0, 0, 0, $cm, 21, $cy));
            $period_to = date('Y-m-d', mktime(0, 0, 0, $cm + 1, 20, $cy));
        }
        $has_commission_config = false;
        $commission_total_cobrada = 0;
        $commission_total_pendiente = 0;
        if ($this->db->where('user_id', $this->vendor_id)->where('is_active', 1)->count_all_results('bot_commission_config') > 0) {
            $has_commission_config = true;
            $r = $this->_computeCommissions($this->vendor_id, $period_from, $period_to);
            $commission_total_cobrada = $r['total_com_cobrada'];
            $commission_total_pendiente = $r['total_com_pendiente'];
        }

        $data = array(
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
            'sales_today' => $sales_today,
            'sales_week' => $sales_week,
            'sales_month' => $sales_month,
            'approved_month' => (int)$approved_month->count,
            'billed_month' => $billed_month,
            'collected_month' => (int)$collected_month->total,
            'avg_ticket' => (int)$avg_ticket,
            'days_left' => $days_left,
            'ranking_position' => $ranking_position,
            'ranking_total' => $ranking_total,
            'inactive_clients' => $inactive_clients,
            'pending_count' => $pending->count,
            'failed_count' => (int)$failed->count,
            'has_commission_config' => $has_commission_config,
            'commission_total_cobrada' => $commission_total_cobrada,
            'commission_total_pendiente' => $commission_total_pendiente,
            'commission_period_from' => $period_from,
            'commission_period_to' => $period_to,
        );
        $this->load->view('ventas/dashboard', $data);
    }

    /**
     * Lista de ventas del bot que fallaron
     */
    public function fallidos()
    {
        if (!$this->_checkAuth()) return;
        date_default_timezone_set("America/Bogota");

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);

        $this->db->from('bot_sales_queue')->where('status', 'failed');
        if (!$is_admin) $this->db->where('vendor_id', $this->vendor_id);
        $items = $this->db->order_by('created_at', 'DESC')->limit(200)->get()->result();

        $data = array(
            'items' => $items,
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
        );
        $this->load->view('ventas/fallidos', $data);
    }

    /**
     * AJAX: devuelve el payload decodificado de un item fallido.
     */
    public function fallido_detail()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = (int)$this->input->get('id');
        $item = $this->db->where('id', $id)->get('bot_sales_queue')->row();
        if (!$item) { echo json_encode(['ok' => false, 'error' => 'No encontrado']); return; }

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);
        if (!$is_admin && $item->vendor_id !== $this->vendor_id) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']); return;
        }

        echo json_encode([
            'ok' => true,
            'item' => $item,
            'payload' => json_decode($item->payload, true),
            'is_agotado' => (stripos((string)$item->error_message, 'agotado') !== false),
        ]);
    }

    /**
     * AJAX: reintenta un fallido con payload editado por el vendedor.
     * Requiere: id, payload (json string con los campos corregidos).
     */
    public function fallido_retry()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = (int)$this->input->post('id');
        $new_payload = $this->input->post('payload');
        if (!$id || !$new_payload) { echo json_encode(['ok' => false, 'error' => 'Faltan datos']); return; }

        $item = $this->db->where('id', $id)->get('bot_sales_queue')->row();
        if (!$item) { echo json_encode(['ok' => false, 'error' => 'No encontrado']); return; }

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);
        if (!$is_admin && $item->vendor_id !== $this->vendor_id) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']); return;
        }

        $payload = json_decode($new_payload, true);
        if (!is_array($payload)) { echo json_encode(['ok' => false, 'error' => 'Payload invalido']); return; }

        // Guardar payload editado y reintentar usando la logica de BotImport
        $this->db->where('id', $id)->update('bot_sales_queue', ['payload' => json_encode($payload)]);

        require_once(APPPATH . 'controllers/sisvent/rest/BotImport.php');
        $bi = new BotImport();
        $ref = new ReflectionClass($bi);
        $method = $ref->getMethod('process_webhook_sale');
        $method->setAccessible(true);

        try {
            $result = $method->invoke($bi, $payload, $item->vendor_id);
        } catch (Exception $e) {
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        if (!empty($result['success'])) {
            $this->db->where('id', $id)->update('bot_sales_queue', [
                'status' => 'completed',
                'budget_id' => $result['budget_id'],
                'error_message' => null,
                'attempts' => (int)$item->attempts + 1,
                'processed_at' => date('Y-m-d H:i:s'),
            ]);
            echo json_encode([
                'ok' => true,
                'budget_id' => $result['budget_id'],
                'message' => 'Presupuesto #' . $result['budget_id'] . ' creado correctamente',
            ]);
        } else {
            $this->db->where('id', $id)->update('bot_sales_queue', [
                'error_message' => $result['error'] ?? 'Error desconocido',
                'attempts' => (int)$item->attempts + 1,
                'processed_at' => date('Y-m-d H:i:s'),
            ]);
            echo json_encode([
                'ok' => false,
                'error' => $result['error'] ?? 'Error desconocido',
            ]);
        }
    }

    /**
     * AJAX: elimina un item fallido (lo saca de la cola).
     */
    public function fallido_delete()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = (int)$this->input->post('id');
        $item = $this->db->where('id', $id)->get('bot_sales_queue')->row();
        if (!$item) { echo json_encode(['ok' => false, 'error' => 'No encontrado']); return; }

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);
        if (!$is_admin && $item->vendor_id !== $this->vendor_id) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']); return;
        }

        $this->db->where('id', $id)->delete('bot_sales_queue');
        echo json_encode(['ok' => true, 'message' => 'Venta fallida eliminada']);
    }

    /**
     * AJAX: envia mensaje WhatsApp al cliente informando que el producto esta agotado.
     */
    public function fallido_send_agotado()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = (int)$this->input->post('id');
        $item = $this->db->where('id', $id)->get('bot_sales_queue')->row();
        if (!$item) { echo json_encode(['ok' => false, 'error' => 'No encontrado']); return; }

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);
        if (!$is_admin && $item->vendor_id !== $this->vendor_id) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']); return;
        }

        $payload = json_decode($item->payload, true);
        $nombre = $payload['nombre'] ?? 'Cliente';
        $celular = $payload['celular'] ?? '';
        if ($celular === '') { echo json_encode(['ok' => false, 'error' => 'Sin celular en el payload']); return; }

        // Extraer codigo(s) del error (pattern: "Producto agotado: CODIGO")
        preg_match('/agotado:\s*([A-Z0-9\-_]+)/i', (string)$item->error_message, $m);
        $codigo = $m[1] ?? '';

        // Elegir bot config del vendedor
        $bot = $this->db->where('default_vendor_id', $item->vendor_id)
                        ->where('is_active', 1)
                        ->limit(1)
                        ->get('builderbot_configs')->row();
        if (!$bot) {
            // Fallback: cualquier bot activo
            $bot = $this->db->where('is_active', 1)->limit(1)->get('builderbot_configs')->row();
        }
        if (!$bot) { echo json_encode(['ok' => false, 'error' => 'No hay bot configurado']); return; }

        // Normalizar celular (prefijo 57 si empieza en 3 y tiene 10 digitos)
        $tel = preg_replace('/\D/', '', $celular);
        if (strlen($tel) === 10 && substr($tel, 0, 1) === '3') $tel = '57' . $tel;

        $mensaje = "Hola " . $nombre . "!\n\n"
                 . "Te escribimos de *Ledxury*. Lamentablemente el producto"
                 . ($codigo ? " *" . $codigo . "*" : "")
                 . " que solicitaste esta agotado en este momento.\n\n"
                 . "Nos gustaria ofrecerte una alternativa similar. ¿Deseas que te mostremos otras opciones disponibles?\n\n"
                 . "Responde a este mensaje y con gusto te ayudamos.";

        $this->load->library('builderbot_lib');
        $res = $this->builderbot_lib->sendMessage($bot, $tel, $mensaje);

        if (!empty($res['success'])) {
            echo json_encode(['ok' => true, 'message' => 'Mensaje enviado a ' . $tel]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'No se pudo enviar: HTTP ' . ($res['http_code'] ?? 0)]);
        }
    }

    /**
     * Lista de TODOS los presupuestos del vendedor con filtro por estado.
     * GET /ventas/presupuestos?state=todos|pendientes|revisados|aprobados|archivados&q=...
     */
    public function presupuestos()
    {
        if (!$this->_checkAuth()) return;
        date_default_timezone_set("America/Bogota");

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);
        $stateFilter = $this->input->get('state') ?: 'todos';
        $q = trim((string)$this->input->get('q'));

        $this->db->select('b.*, c.name as client_name, c.cellphone as client_phone, c.idNum as client_doc, u.name as vendor_name, inv.idInvoice as invoice_id');
        $this->db->from('budgets b');
        $this->db->join('clients c', 'c.idClient = b.clientId', 'left');
        $this->db->join('users u', 'u.idUser = b.vendorId', 'left');
        $this->db->join('invoices inv', 'inv.budgetId = b.idBudget AND inv.deleted = 0', 'left');
        $this->db->where('b.deleted', 0);

        // Filtro por estado
        switch ($stateFilter) {
            case 'pendientes':
                $this->db->where('b.state', 0); $this->db->where('b.archived', 0); break;
            case 'revisados':
                $this->db->where('b.state', 2); $this->db->where('b.archived', 0); break;
            case 'aprobados':
                $this->db->where('b.state', 1); $this->db->where('b.archived', 0); break;
            case 'archivados':
                $this->db->where('b.archived', 1); break;
            default: // todos
                $this->db->where('b.archived', 0);
                break;
        }

        if ($q !== '') {
            $this->db->group_start()
                ->like('b.idBudget', $q)
                ->or_like('c.name', $q)
                ->or_like('c.cellphone', $q)
                ->or_like('c.idNum', $q)
                ->or_like('b.comments', $q)
                ->group_end();
        }

        // Scope por vendedor / bot
        $bot_scope = $this->_resolveBotVendorScope($this->vendor_id);
        if (is_array($bot_scope) && !empty($bot_scope)) {
            $scope_ids = $bot_scope;
            if (!in_array($this->vendor_id, $scope_ids)) $scope_ids[] = $this->vendor_id;
            $this->db->where_in('b.vendorId', $scope_ids);
        } elseif ($bot_scope === 'all' && $is_admin) {
            // admin con scope 'all' ve todo
        } else {
            $this->db->where('b.vendorId', $this->vendor_id);
        }

        $total_count = $this->db->count_all_results('', false);

        $this->db->order_by("FIELD(b.state, 0, 2, 1, 4)", '', false);
        $this->db->order_by('b.date', 'DESC');
        $this->db->limit(100);
        $budgets = $this->db->get()->result();

        // Conteos por estado para los chips
        $counts = array();
        $base_scope_sql = $this->_buildScopeWhere($bot_scope, $is_admin);
        foreach (array(
            'pendientes' => "state=0 AND archived=0",
            'revisados'  => "state=2 AND archived=0",
            'aprobados'  => "state=1 AND archived=0",
            'archivados' => "archived=1",
            'todos'      => "archived=0",
        ) as $key => $cond) {
            $sql = "SELECT COUNT(*) AS c FROM budgets b WHERE deleted=0 AND $cond AND $base_scope_sql";
            $row = $this->db->query($sql)->row();
            $counts[$key] = (int)($row->c ?? 0);
        }

        $data = array(
            'budgets'     => $budgets,
            'total_count' => (int)$total_count,
            'state'       => $stateFilter,
            'q'           => $q,
            'counts'      => $counts,
            'vendor'      => $this->vendor,
            'is_admin'    => $is_admin,
        );
        $this->load->view('ventas/presupuestos', $data);
    }

    /**
     * Helper interno: construye el WHERE del scope (bot/vendor) como SQL plano
     * para usar en queries raw de conteos.
     */
    private function _buildScopeWhere($bot_scope, $is_admin)
    {
        if (is_array($bot_scope) && !empty($bot_scope)) {
            $scope_ids = $bot_scope;
            if (!in_array($this->vendor_id, $scope_ids)) $scope_ids[] = $this->vendor_id;
            $escaped = array_map(function($v){ return "'" . $this->db->escape_str($v) . "'"; }, $scope_ids);
            return "b.vendorId IN (" . implode(',', $escaped) . ")";
        }
        if ($bot_scope === 'all' && $is_admin) {
            return "1=1";
        }
        return "b.vendorId = '" . $this->db->escape_str($this->vendor_id) . "'";
    }

    /**
     * Lista de presupuestos pendientes.
     * Cada usuario (incluido admin) ve solo los presupuestos de sus bots asignados
     * (via bot_commission_config). 'all' ve todos. Sin asignacion: admin ve todos,
     * operador cae al filtro por vendorId propio.
     */
    public function pendientes()
    {
        if (!$this->_checkAuth()) return;
        date_default_timezone_set("America/Bogota");

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);

        $this->db->select('b.*, c.name as client_name, c.cellphone as client_phone, c.idNum as client_doc, u.name as vendor_name');
        $this->db->from('budgets b');
        $this->db->join('clients c', 'c.idClient = b.clientId', 'left');
        $this->db->join('users u', 'u.idUser = b.vendorId', 'left');
        $this->db->where('b.state', 0);
        $this->db->where('b.deleted', 0);
        $this->db->where('b.archived', 0);
        $this->db->where('b.date >=', self::PENDING_CUTOFF_DATE);

        $bot_scope = $this->_resolveBotVendorScope($this->vendor_id);
        if (is_array($bot_scope) && !empty($bot_scope)) {
            $scope_ids = $bot_scope;
            if (!in_array($this->vendor_id, $scope_ids)) $scope_ids[] = $this->vendor_id;
            $this->db->where_in('b.vendorId', $scope_ids);
        } elseif ($bot_scope === 'all' && $is_admin) {
            // admin con scope 'all' ve todo
        } else {
            // default: solo propios
            $this->db->where('b.vendorId', $this->vendor_id);
        }

        // Count real total antes de aplicar limit
        $total_count = $this->db->count_all_results('', false);

        $this->db->order_by('b.date', 'DESC');
        $this->db->limit(100);
        $budgets = $this->db->get()->result();

        $data = array(
            'budgets' => $budgets,
            'total_count' => (int)$total_count,
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
        );
        $this->load->view('ventas/pendientes', $data);
    }

    /**
     * Determina el alcance de vendorIds que un usuario puede ver segun bot_commission_config.
     * Retorna:
     *   'all'    => ve presupuestos de todos los bots
     *   array[]  => lista de vendorIds (default_vendor_id de los bots asignados)
     *   'own'    => no tiene configuracion de bot; cae al filtro por vendorId propio
     */
    private function _resolveBotVendorScope($user_id)
    {
        // Usar query raw para evitar heredar select/where del query builder del caller.
        $configs = $this->db->query(
            "SELECT applies_to FROM bot_commission_config WHERE user_id = ? AND is_active = 1",
            array($user_id)
        )->result();
        if (empty($configs)) return 'own';

        $bot_ids = array();
        foreach ($configs as $cfg) {
            if ($cfg->applies_to === 'all') return 'all';
            $bot_ids[] = (int)$cfg->applies_to;
        }
        if (empty($bot_ids)) return 'own';

        $in = implode(',', array_map('intval', $bot_ids));
        $vendors = $this->db->query(
            "SELECT default_vendor_id FROM builderbot_configs WHERE id IN ({$in})"
        )->result();
        $ids = array();
        foreach ($vendors as $v) if (!empty($v->default_vendor_id)) $ids[] = $v->default_vendor_id;
        return !empty($ids) ? $ids : 'own';
    }

    /**
     * Ver/editar presupuesto
     */
    public function ver($id)
    {
        if (!$this->_checkAuth()) return;

        $budget = $this->budgets_model->getBudget($id);
        if (!$budget) show_404();

        $details = $this->budgets_model->getDetails($id);
        $client = $this->clients_model->getClient($budget->clientId);
        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);

        $data = array(
            'budget' => $budget,
            'details' => $details,
            'client' => $client,
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
        );
        $this->load->view('ventas/ver', $data);
    }

    /**
     * AJAX: Aprobar presupuesto (solo jefe de bodega / admin).
     * Acepta state=0 (pendiente) o state=2 (revisado) -> 1 (aprobado).
     */
    public function aprobar()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);
        if (!$is_admin) {
            echo json_encode(array('success' => false, 'error' => 'Solo el jefe de bodega puede aprobar'));
            return;
        }

        $id = $this->input->post('id');
        if (!$id) {
            echo json_encode(array('success' => false, 'error' => 'ID requerido'));
            return;
        }

        $budget = $this->budgets_model->getBudget($id);
        if (!$budget) {
            echo json_encode(array('success' => false, 'error' => 'Presupuesto no encontrado'));
            return;
        }

        date_default_timezone_set("America/Bogota");
        $this->budgets_model->update($id, array('state' => 1, 'updated_at' => date('Y-m-d H:i:s')));

        echo json_encode(array('success' => true, 'message' => 'Presupuesto #' . $id . ' aprobado'));
    }

    /**
     * AJAX: Marcar presupuesto como revisado por el vendedor.
     * state=0 -> state=2. Jefe de bodega luego lo aprobara.
     */
    public function revisar()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = $this->input->post('id');
        if (!$id) { echo json_encode(array('success' => false, 'error' => 'ID requerido')); return; }

        $budget = $this->budgets_model->getBudget($id);
        if (!$budget) { echo json_encode(array('success' => false, 'error' => 'Presupuesto no encontrado')); return; }
        if ((int)$budget->state !== 0) { echo json_encode(array('success' => false, 'error' => 'Solo se pueden marcar como revisados los pendientes')); return; }

        date_default_timezone_set("America/Bogota");
        $this->budgets_model->update($id, array('state' => 2, 'updated_at' => date('Y-m-d H:i:s')));

        echo json_encode(array('success' => true, 'message' => 'Presupuesto #' . $id . ' marcado como revisado'));
    }

    /**
     * Ver mis comisiones con detalle de facturas (cobradas + pendientes) e historial.
     * Admin ve todas; vendedor solo las propias.
     * Filtros: ?month=YYYY-MM (default periodo 21->20) o ?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function comisiones()
    {
        if (!$this->_checkAuth()) return;
        date_default_timezone_set("America/Bogota");

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);

        // Periodo default 21 -> 20. Soporta navegar periodos pasados/futuros con ?month=YYYY-MM
        $month = $this->input->get('month') ?: date('Y-m');
        $parts = explode('-', $month);
        $year = (int)$parts[0];
        $m = (int)$parts[1];
        $period_start = date('Y-m-d', mktime(0, 0, 0, $m - 1, 21, $year));
        $period_end = date('Y-m-d', mktime(0, 0, 0, $m, 20, $year));
        $period_label = date('F Y', mktime(0, 0, 0, $m, 1, $year));

        // Periodos prev/next para navegación
        $prev_month = date('Y-m', mktime(0, 0, 0, $m - 1, 1, $year));
        $next_month = date('Y-m', mktime(0, 0, 0, $m + 1, 1, $year));
        $this_month = date('Y-m');

        // Rango custom opcional (override del periodo)
        $from = $this->input->get('from') ?: $period_start;
        $to = $this->input->get('to') ?: $period_end;
        $is_custom_range = ($this->input->get('from') || $this->input->get('to'));

        $period = $this->db->where('period_start', $period_start)->where('period_end', $period_end)->get('bot_commission_periods')->row();

        // Admin ve todas, vendedor solo las suyas
        $target_user_id = $this->vendor_id;
        if ($is_admin && $this->input->get('user_id')) {
            $target_user_id = $this->input->get('user_id');
        }

        $result = $this->_computeCommissions($target_user_id, $from, $to);

        // Totales "oficiales" que coinciden con Liquidaciones (mam_helper.calculateSettlementValues)
        // getVendorSettlement: facturas pagadas (state=2) sin liquidar -> monto que aparece en Liquidaciones
        // getVendorPossibleSettlement: facturas no pagadas aun (state 0/1)
        $this->load->model('vendors_model');
        $this->load->model('vouchers_model');
        $this->load->helper('mam');
        $liq_pagada = getVendorSettlement($target_user_id);
        $liq_pendiente = getVendorPossibleSettlement($target_user_id);

        // Si el periodo ya fue liquidado y NO hay rango custom, usar snapshot persistido
        if (!$is_custom_range && $period && $period->status === 'liquidado') {
            $details = $this->db->where('period_id', $period->id)->where('user_id', $target_user_id)->get('bot_commission_details')->result();
            $snapshot_total = 0;
            foreach ($details as $d) $snapshot_total += $d->commission_amount;
            $result['liquidated_details'] = $details;
            $result['snapshot_total'] = $snapshot_total;
            // Items (facturas que compusieron la comisión liquidada)
            $result['snapshot_items'] = $this->db
                ->where('period_id', $period->id)
                ->where('user_id', $target_user_id)
                ->order_by('invoice_date', 'DESC')
                ->get('bot_commission_invoice_items')->result();
        }

        // Historial
        $historial = $this->db->select('p.id as period_id, p.period_label, p.period_start, p.period_end, p.status as period_status, d.commission_amount, d.base_amount, d.percentage, d.status, d.bot_name, d.commission_type')
            ->from('bot_commission_details d')
            ->join('bot_commission_periods p', 'p.id = d.period_id')
            ->where('d.user_id', $target_user_id)
            ->order_by('p.period_start', 'DESC')
            ->limit(12)
            ->get()->result();

        // Usuarios con config (solo admin)
        $users_with_config = [];
        if ($is_admin) {
            $users_with_config = $this->db->select('c.user_id, u.name')->distinct()
                ->from('bot_commission_config c')
                ->join('users u', 'u.idUser = c.user_id', 'left')
                ->where('c.is_active', 1)
                ->get()->result();
        }

        $data = array_merge($result, array(
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
            'target_user_id' => $target_user_id,
            'users_with_config' => $users_with_config,
            'period_label' => $period_label,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'month' => $month,
            'prev_month' => $prev_month,
            'next_month' => $next_month,
            'this_month' => $this_month,
            'from' => $from,
            'to' => $to,
            'is_custom_range' => $is_custom_range,
            'period' => $period,
            'historial' => $historial,
            'liq_pagada' => (float)($liq_pagada->total ?? 0),
            'liq_pendiente' => (float)($liq_pendiente->total ?? 0),
        ));
        $this->load->view('ventas/comisiones', $data);
    }

    /**
     * Mis guias: vendedor ve guias de envio de sus clientes.
     * Admin/jefe de bodega ve todas. Vendedor solo las propias (con scope de bots).
     */
    public function guias()
    {
        if (!$this->_checkAuth()) return;
        $this->load->model('shipping_model');

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);
        $search = trim((string)$this->input->get('q'));
        $status = $this->input->get('status') ?: 'all';

        // Determinar scope de vendorIds segun bots
        $vendor_filter = 'all';
        if (!$is_admin) {
            $bot_scope = $this->_resolveBotVendorScope($this->vendor_id);
            if (is_array($bot_scope) && !empty($bot_scope)) {
                $scope_ids = $bot_scope;
                if (!in_array($this->vendor_id, $scope_ids)) $scope_ids[] = $this->vendor_id;
                // Shipping_model no soporta where_in nativo en getShipments -> filtrar resultado
                $vendor_filter = 'scope';
                $scope = $scope_ids;
            } else {
                $vendor_filter = $this->vendor_id;
            }
        }

        if ($vendor_filter === 'scope') {
            // Caso multi-vendor: traer todo y filtrar en PHP
            $all = $this->shipping_model->getShipments(-1, $status, null, null, $search, 1, 200, 'all');
            $items = array();
            foreach ($all as $g) {
                if (in_array($g->vendorId, $scope)) $items[] = $g;
            }
            $items = array_slice($items, 0, 100);
        } else {
            $items = $this->shipping_model->getShipments(-1, $status, null, null, $search, 1, 100, $vendor_filter);
        }

        $data = array(
            'items' => $items,
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
            'q' => $search,
            'status' => $status,
        );
        $this->load->view('ventas/guias', $data);
    }

    /**
     * Calcula comisiones de un usuario en un rango: breakdown por bot + listas de facturas
     * cobradas (state=2) y pendientes (state=1).
     * Retorna: configs, breakdown, cobradas, pendientes, total_cobrado/pendiente, total_com_*.
     */
    private function _computeCommissions($user_id, $from, $to)
    {
        $configs = $this->db->where('is_active', 1)->where('user_id', $user_id)->get('bot_commission_config')->result();

        // Bots activos: map id -> {name, default_vendor_id}
        $bots = $this->db->where('is_active', 1)->get('builderbot_configs')->result();
        $bots_by_id = [];
        $all_vendor_ids = [];
        foreach ($bots as $b) {
            $bots_by_id[$b->id] = $b;
            if (!empty($b->default_vendor_id)) $all_vendor_ids[] = $b->default_vendor_id;
        }

        $breakdown = [];
        $cobradas = [];
        $pendientes = [];
        $total_cobrado = 0;
        $total_pendiente = 0;
        $total_com_cobrada = 0;
        $total_com_pendiente = 0;
        $seen_paid = [];
        $seen_pend = [];

        foreach ($configs as $cfg) {
            if ($cfg->applies_to === 'all') {
                $scope_ids = $all_vendor_ids;
                $bot_name = 'Todos los bots';
                $bot_id_label = null;
            } else {
                $bot_id_label = (int)$cfg->applies_to;
                $bot = $bots_by_id[$bot_id_label] ?? null;
                $scope_ids = ($bot && !empty($bot->default_vendor_id)) ? [$bot->default_vendor_id] : [];
                $bot_name = $bot ? $bot->name : 'Bot #' . $bot_id_label;
            }
            if (empty($scope_ids)) continue;

            $rows_p = $this->_getInvoicesInScope($scope_ids, 2, $from, $to);
            $rows_pend = $this->_getInvoicesInScope($scope_ids, 1, $from, $to);

            $base_pagada = 0;
            foreach ($rows_p as $inv) {
                $base_pagada += (float)$inv->total;
                $k = $bot_name . '|' . $inv->idInvoice;
                if (!isset($seen_paid[$k])) {
                    $inv->bot_name = $bot_name;
                    $inv->percentage = $cfg->percentage;
                    $inv->commission = round((float)$inv->total * ($cfg->percentage / 100));
                    $cobradas[] = $inv;
                    $seen_paid[$k] = true;
                }
            }
            $base_pendiente = 0;
            foreach ($rows_pend as $inv) {
                $base_pendiente += (float)$inv->total;
                $k = $bot_name . '|' . $inv->idInvoice;
                if (!isset($seen_pend[$k])) {
                    $inv->bot_name = $bot_name;
                    $inv->percentage = $cfg->percentage;
                    $inv->commission = round((float)$inv->total * ($cfg->percentage / 100));
                    $pendientes[] = $inv;
                    $seen_pend[$k] = true;
                }
            }

            $com_pagada = round($base_pagada * ($cfg->percentage / 100));
            $com_pendiente = round($base_pendiente * ($cfg->percentage / 100));

            $breakdown[] = (object)[
                'bot_name' => $bot_name,
                'bot_config_id' => $bot_id_label,
                'commission_type' => $cfg->commission_type,
                'percentage' => $cfg->percentage,
                'base_pagada' => $base_pagada,
                'base_pendiente' => $base_pendiente,
                'com_pagada' => $com_pagada,
                'com_pendiente' => $com_pendiente,
            ];
            $total_cobrado += $base_pagada;
            $total_pendiente += $base_pendiente;
            $total_com_cobrada += $com_pagada;
            $total_com_pendiente += $com_pendiente;
        }

        usort($cobradas, function($a, $b){ return strcmp($b->date, $a->date); });
        usort($pendientes, function($a, $b){ return strcmp($b->date, $a->date); });

        return [
            'configs' => $configs,
            'breakdown' => $breakdown,
            'cobradas' => $cobradas,
            'pendientes' => $pendientes,
            'total_cobrado' => $total_cobrado,
            'total_pendiente' => $total_pendiente,
            'total_com_cobrada' => $total_com_cobrada,
            'total_com_pendiente' => $total_com_pendiente,
        ];
    }

    private function _getInvoicesInScope($vendor_ids, $state, $from, $to)
    {
        return $this->db->select('i.idInvoice, NULL as invoice_number, i.date, i.vendorId, i.clientId, i.budgetId, i.state, i.total, u.name as vendor_name, c.name as client_name', false)
            ->from('invoices i')
            ->join('users u', 'u.idUser = i.vendorId', 'left')
            ->join('clients c', 'c.idClient = i.clientId', 'left')
            ->where('i.state', $state)
            ->where_in('i.vendorId', $vendor_ids)
            ->where('i.total >', 0)
            ->where('i.date >=', $from . ' 00:00:00')
            ->where('i.date <=', $to . ' 23:59:59')
            ->group_start()->where('i.deleted IS NULL', null, false)->or_where('i.deleted', 0)->group_end()
            ->order_by('i.date', 'DESC')
            ->get()->result();
    }

    /**
     * Editar presupuesto (vista móvil)
     */
    public function editar($id)
    {
        if (!$this->_checkAuth()) return;

        $budget = $this->budgets_model->getBudget($id);
        if (!$budget) show_404();

        $details = $this->budgets_model->getDetails($id);
        $client = $this->clients_model->getClient($budget->clientId);

        $data = array(
            'budget' => $budget,
            'details' => $details,
            'client' => $client,
            'vendor' => $this->vendor,
        );
        $this->load->view('ventas/editar', $data);
    }
    /**
     * AJAX: Guardar edición de presupuesto
     */
    public function guardar()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = $this->input->post('id');
        if (!$id) { echo json_encode(array('success' => false, 'error' => 'ID requerido')); return; }

        $budget = $this->budgets_model->getBudget($id);
        if (!$budget) { echo json_encode(array('success' => false, 'error' => 'No encontrado')); return; }

        date_default_timezone_set("America/Bogota");

        // Actualizar datos del cliente
        $client_name = trim($this->input->post('client_name'));
        $client_doc = trim($this->input->post('client_doc'));
        $client_phone = trim($this->input->post('client_phone'));
        $client_address = trim($this->input->post('client_address'));
        $client_city = trim($this->input->post('client_city'));

        if ($budget->clientId && ($client_name || $client_phone || $client_address)) {
            $client_update = array();
            if ($client_name) $client_update['name'] = $client_name;
            if ($client_doc) $client_update['idNum'] = $client_doc;
            if ($client_phone) $client_update['cellphone'] = $client_phone;
            if ($client_address) $client_update['address'] = $client_address;
            if ($client_city) $client_update['city'] = $client_city;
            if (!empty($client_update)) {
                $this->clients_model->update($budget->clientId, $client_update);
            }
        }

        // Actualizar presupuesto
        $total = (int) preg_replace('/[^0-9]/', '', $this->input->post('total') ?: '0');
        $comments = trim($this->input->post('comments'));

        $budget_update = array('updated_at' => date('Y-m-d H:i:s'));
        if ($total > 0) $budget_update['total'] = $total;
        if ($comments !== '') $budget_update['comments'] = $comments;

        $this->budgets_model->update($id, $budget_update);

        // Actualizar detalle de productos
        $product_ids = $this->input->post('product_ids');
        $quantities = $this->input->post('quantities');
        $units = $this->input->post('units');

        if ($product_ids && is_array($product_ids)) {
            // Validar que todos los códigos existan en BD ANTES de tocar el detalle.
            // Normalizamos a UPPERCASE: si vendedor escribió "6led-12v-i", queda "6LED-12V-I".
            $invalid_codes = array();
            $normalized = array();
            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = strtoupper(trim($product_ids[$i]));
                $qty = isset($quantities[$i]) ? (int)$quantities[$i] : 1;
                $normalized[$i] = $pid;
                if (!empty($pid) && $qty > 0) {
                    $exists = $this->db->select('idProduct')
                        ->from('products')
                        ->where('idProduct', $pid)
                        ->where('deleted', 0)
                        ->limit(1)
                        ->get()->row();
                    if (!$exists) $invalid_codes[] = $pid;
                }
            }
            if (!empty($invalid_codes)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => 'Códigos no encontrados: ' . implode(', ', array_unique($invalid_codes))
                ));
                return;
            }

            // Todo OK: eliminar detalle anterior y reescribir
            $this->db->where('budgetId', $id)->delete('budget_detail');

            $new_total = 0;
            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = $normalized[$i];
                $qty = isset($quantities[$i]) ? (int)$quantities[$i] : 1;
                $unit = isset($units[$i]) ? (int)preg_replace('/[^0-9]/', '', $units[$i]) : 0;
                $line_total = $qty * $unit;

                if (!empty($pid) && $qty > 0) {
                    $this->budgets_model->save_detail(array(
                        'budgetId' => $id,
                        'productId' => $pid,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'base' => $unit,
                        'total' => $line_total,
                    ));
                    $new_total += $line_total;
                }
            }

            if ($new_total > 0) {
                $this->budgets_model->update($id, array('total' => $new_total));
            }
        }

        echo json_encode(array('success' => true, 'message' => 'Presupuesto #' . $id . ' actualizado'));
    }

    /**
     * AJAX: Buscar productos con ordenamiento por relevancia.
     *
     * Estrategia: el campo se llama "CÓDIGO" en la UI, así que matches en
     * idProduct pesan mucho más que matches en description. Los tiers son:
     *   1. idProduct = "3LED"               (exacto)
     *   2. idProduct empieza con "3LED"     (prefix)
     *   3. idProduct contiene "3LED"        (substring)
     *   4. description empieza con "3LED"   (prefix de descripcion)
     *   5. description contiene "3LED"      (substring de descripcion)
     *
     * Sin esta jerarquía, el optimizador devolvía cualquier producto cuyo SKU
     * O descripción contuviera la query, ordenado por inserción (caso real:
     * "3LED" devolvía ACS-F5-* primero porque aparecía la subcadena en algún
     * texto largo de descripción).
     */
    public function buscarProducto()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $q = trim($this->input->get('q'));
        if (strlen($q) < 2) { echo json_encode(array()); return; }

        $esc = $this->db->escape_like_str($q);
        $like_prefix   = $esc . '%';
        $like_contains = '%' . $esc . '%';

        $sql = "SELECT p.idProduct, p.description, p.price, p.price_base,
                       pf.name AS family_name,
                       CASE
                         WHEN p.idProduct = ?            THEN 1
                         WHEN p.idProduct LIKE ?         THEN 2
                         WHEN p.idProduct LIKE ?         THEN 3
                         WHEN p.description LIKE ?       THEN 4
                         WHEN p.description LIKE ?       THEN 5
                         ELSE 6
                       END AS relevance
                FROM products p
                LEFT JOIN product_families pf ON pf.idFamily = p.family
                WHERE (p.idProduct LIKE ? OR p.description LIKE ?)
                  AND (p.deleted IS NULL OR p.deleted = 0)
                ORDER BY relevance ASC, CHAR_LENGTH(p.idProduct) ASC, p.idProduct ASC
                LIMIT 20";

        $rows = $this->db->query($sql, [
            $q,              // tier 1 — exacto
            $like_prefix,    // tier 2 — idProduct prefix
            $like_contains,  // tier 3 — idProduct contains
            $like_prefix,    // tier 4 — description prefix
            $like_contains,  // tier 5 — description contains
            $like_contains,  // WHERE idProduct contains
            $like_contains   // WHERE description contains
        ])->result();
        echo json_encode($rows);
    }

    /**
     * AJAX: Buscar clientes
     */
    public function buscarCliente()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $q = trim($this->input->get('q'));
        if (strlen($q) < 2) { echo json_encode(array()); return; }

        $this->db->select('idClient, name, idNum, cellphone, address, city, state');
        $this->db->from('clients');
        $this->db->group_start();
        $this->db->like('name', $q);
        $this->db->or_like('idNum', $q);
        $this->db->or_like('cellphone', $q);
        $this->db->group_end();
        $this->db->where('deleted', 0);
        $this->db->limit(10);
        echo json_encode($this->db->get()->result());
    }

    /**
     * Crear presupuesto (vista móvil)
     */
    public function crear()
    {
        if (!$this->_checkAuth()) return;

        $role = $this->session->userdata('user_data')['role'];
        $is_admin = in_array($role, [1, 2, 10]);

        $data = array(
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
            'stores' => $this->stores_model->getStores(),
        );
        $this->load->view('ventas/crear', $data);
    }

    /**
     * AJAX: Crear cliente desde nuevo presupuesto.
     * Campos: name, idNum, cellphone, address, city, state (dept), email (opcional).
     * Devuelve idClient listo para usar en el presupuesto.
     */
    public function crearCliente()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        // Aceptar tanto el formato viejo (name) como el nuevo (nombres + apellidos)
        $nombres   = trim($this->input->post('nombres'));
        $apellidos = trim($this->input->post('apellidos'));
        $name      = trim($this->input->post('name'));
        if ($name === '' && ($nombres !== '' || $apellidos !== '')) {
            $name = trim($nombres . ' ' . $apellidos);
        }
        $idNum     = trim($this->input->post('idNum'));
        $cellphone = trim($this->input->post('cellphone'));
        $address   = trim($this->input->post('address'));
        $city      = trim($this->input->post('city'));
        $state     = trim($this->input->post('state'));
        $email     = trim($this->input->post('email'));

        // Reglas relajadas: nombre + celular + dirección. Documento opcional.
        if (!$name)      { echo json_encode(array('success' => false, 'error' => 'El nombre es obligatorio')); return; }
        if (!$cellphone) { echo json_encode(array('success' => false, 'error' => 'El celular es obligatorio')); return; }
        if (!$address)   { echo json_encode(array('success' => false, 'error' => 'La dirección es obligatoria')); return; }

        // Evitar duplicados: si ya existe por documento o celular, retornar ese cliente
        if ($idNum || $cellphone) {
            $this->db->from('clients')->where('deleted', 0);
            $this->db->group_start();
            if ($idNum) $this->db->where('idNum', $idNum);
            if ($cellphone) {
                if ($idNum) $this->db->or_where('cellphone', $cellphone);
                else $this->db->where('cellphone', $cellphone);
            }
            $this->db->group_end();
            $existing = $this->db->limit(1)->get()->row();
            if ($existing) {
                echo json_encode(array(
                    'success' => true,
                    'duplicate' => true,
                    'idClient' => $existing->idClient,
                    'name' => $existing->name,
                    'idNum' => $existing->idNum,
                    'cellphone' => $existing->cellphone,
                    'message' => 'Cliente ya existente, se usará ese registro',
                ));
                return;
            }
        }

        $data = array(
            'name' => $name,
            'idNum' => $idNum ?: null,
            'cellphone' => $cellphone ?: null,
            'address' => $address ?: null,
            'city' => $city ?: null,
            'state' => $state ?: null,
            'email' => $email ?: null,
            'deleted' => 0,
        );
        $ok = $this->clients_model->save($data);
        if (!$ok) { echo json_encode(array('success' => false, 'error' => 'No se pudo crear el cliente')); return; }
        $idClient = $this->db->insert_id();

        echo json_encode(array(
            'success' => true,
            'duplicate' => false,
            'idClient' => $idClient,
            'name' => $name,
            'idNum' => $idNum,
            'cellphone' => $cellphone,
        ));
    }

    /**
     * AJAX: Archivar presupuesto (soft delete)
     */
    public function archivar()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = $this->input->post('id');
        if (!$id) { echo json_encode(array('success' => false, 'error' => 'ID requerido')); return; }

        date_default_timezone_set("America/Bogota");
        $this->budgets_model->update($id, array('state' => 4, 'updated_at' => date('Y-m-d H:i:s')));
        echo json_encode(array('success' => true, 'message' => 'Presupuesto archivado'));
    }

    /**
     * AJAX: Eliminar presupuesto
     */
    public function eliminar()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $id = $this->input->post('id');
        if (!$id) { echo json_encode(array('success' => false, 'error' => 'ID requerido')); return; }

        date_default_timezone_set("America/Bogota");
        $this->budgets_model->update($id, array('deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')));
        echo json_encode(array('success' => true, 'message' => 'Presupuesto eliminado'));
    }

    /**
     * Chat interno (vista móvil)
     */
    public function chat()
    {
        if (!$this->_checkAuth()) return;
        $data = array(
            'user' => $this->vendor,
        );
        $this->load->view('ventas/chat', $data);
    }

    /**
     * AJAX: Lista de usuarios para chat
     */
    public function chatUsers()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $this->load->model('message_model');
        $users = $this->users_model->getUsersButMe($this->vendor_id);
        $result = array();

        foreach ($users as $u) {
            $unread = $this->message_model->getUnreadMessagesCount($this->vendor_id, $u->idUser);
            $lastMsg = $this->message_model->getLastMessage($u->idUser);
            $lastText = '';
            $lastTime = '';
            if (!empty($lastMsg)) {
                $lastText = isset($lastMsg[0]['message']) ? mb_substr($lastMsg[0]['message'], 0, 40) : '';
                if (isset($lastMsg[0]['time'])) {
                    $parts = explode(' ', $lastMsg[0]['time']);
                    $lastTime = isset($parts[1]) ? substr($parts[1], 0, 5) : '';
                }
            }

            $result[] = array(
                'id' => $u->idUser,
                'name' => $u->name,
                'online' => ($u->user_status === 'active'),
                'lastMsg' => $lastText,
                'time' => $lastTime,
                'unread' => (int)$unread,
            );
        }

        echo json_encode($result);
    }

    /**
     * Logout
     */
    public function logout()
    {
        // Log logout activity before destroying session
        if ($this->session->has_userdata('user_data')) {
            date_default_timezone_set("America/Bogota");
            $uid = $this->session->userdata('user_data')['uname'];
            $this->db->insert('user_activity_log', array(
                'user_id' => $uid,
                'action' => 'logout_mobile',
                'ip_address' => $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }
        
        $this->session->unset_userdata('user_data');
        redirect(base_url() . 'ventas/login');
    }

    private function _getCobrosPerBot($from, $to)
    {
        $sql = "SELECT bc.id as bot_config_id, bc.name as bot_name, bc.default_vendor_id,
                       COALESCE(SUM(i.total), 0) as total, COUNT(DISTINCT i.idInvoice) as facturas
                FROM builderbot_configs bc
                LEFT JOIN invoices i ON i.vendorId = bc.default_vendor_id
                    AND i.state = 2
                    AND i.total > 0
                    AND i.date >= ?
                    AND i.date <= ?
                    AND (i.deleted IS NULL OR i.deleted = 0)
                WHERE bc.is_active = 1
                GROUP BY bc.id";

        $result = $this->db->query($sql, array($from . ' 00:00:00', $to . ' 23:59:59'))->result();

        $cobros = array();
        foreach ($result as $r) {
            $cobros[$r->bot_config_id] = array(
                'bot_name' => $r->bot_name,
                'vendor_id' => $r->default_vendor_id,
                'total' => (float)$r->total,
                'guias' => (int)$r->facturas,
            );
        }
        return $cobros;
    }
}
