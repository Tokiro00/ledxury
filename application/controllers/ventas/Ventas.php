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

        // Ventas hoy
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('budgets');
        $this->db->where('date >=', $today . ' 00:00:00');
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $sales_today = $this->db->get()->row();

        // Ventas semana
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('budgets');
        $this->db->where('date >=', $weekStart . ' 00:00:00');
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $sales_week = $this->db->get()->row();

        // Ventas mes
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('budgets');
        $this->db->where('date >=', $monthStart . ' 00:00:00');
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $sales_month = $this->db->get()->row();

        // Presupuestos pendientes (excluye pruebas anteriores a PENDING_CUTOFF_DATE)
        $this->db->select('COUNT(*) as count');
        $this->db->from('budgets');
        $this->db->where('state', 0);
        $this->db->where('deleted', 0);
        $this->db->where('date >=', self::PENDING_CUTOFF_DATE);
        $bot_scope = $this->_resolveBotVendorScope($this->vendor_id);
        if (is_array($bot_scope) && !empty($bot_scope)) {
            $this->db->where_in('vendorId', $bot_scope);
        } elseif ($bot_scope === 'own' && !$is_admin) {
            $this->db->where('vendorId', $this->vendor_id);
        }
        $pending = $this->db->get()->row();

        // Ventas fallidas del bot
        $this->db->select('COUNT(*) as count')->from('bot_sales_queue')->where('status', 'failed');
        if (!$is_admin) $this->db->where('vendor_id', $this->vendor_id);
        $failed = $this->db->get()->row();

        $data = array(
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
            'sales_today' => $sales_today,
            'sales_week' => $sales_week,
            'sales_month' => $sales_month,
            'pending_count' => $pending->count,
            'failed_count' => (int)$failed->count,
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
        $this->db->where('b.date >=', self::PENDING_CUTOFF_DATE);

        $bot_scope = $this->_resolveBotVendorScope($this->vendor_id);
        if (is_array($bot_scope) && !empty($bot_scope)) {
            $this->db->where_in('b.vendorId', $bot_scope);
        } elseif ($bot_scope === 'own' && !$is_admin) {
            $this->db->where('b.vendorId', $this->vendor_id);
        }
        // 'all' o (admin sin config) => sin filtro de vendor

        $this->db->order_by('b.date', 'DESC');
        $this->db->limit(100);
        $budgets = $this->db->get()->result();

        $data = array(
            'budgets' => $budgets,
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

        $data = array(
            'budget' => $budget,
            'details' => $details,
            'client' => $client,
            'vendor' => $this->vendor,
        );
        $this->load->view('ventas/ver', $data);
    }

    /**
     * AJAX: Aprobar presupuesto
     */
    public function aprobar()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

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
     * Ver mis comisiones
     */
    public function comisiones()
    {
        if (!$this->_checkAuth()) return;
        date_default_timezone_set("America/Bogota");

        $month = $this->input->get('month') ?: date('Y-m');
        $parts = explode('-', $month);
        $year = (int)$parts[0];
        $m = (int)$parts[1];

        $period_start = date('Y-m-d', mktime(0, 0, 0, $m - 1, 21, $year));
        $period_end = date('Y-m-d', mktime(0, 0, 0, $m, 20, $year));
        $period_label = date('F Y', mktime(0, 0, 0, $m, 1, $year));

        // Buscar comisiones de este usuario en el período
        $period = $this->db->where('period_start', $period_start)->where('period_end', $period_end)->get('bot_commission_periods')->row();

        $mis_comisiones = array();
        $total_comision = 0;

        if ($period && $period->status === 'liquidado') {
            // Período liquidado: usar detalle histórico
            $mis_comisiones = $this->db->where('period_id', $period->id)->where('user_id', $this->vendor_id)->get('bot_commission_details')->result();
            foreach ($mis_comisiones as $c) $total_comision += $c->commission_amount;
        } else {
            // Período abierto: calcular al vuelo igual que el admin panel
            $cobros = $this->_getCobrosPerBot($period_start, $period_end);
            $total_cobrado = 0;
            foreach ($cobros as $info) $total_cobrado += $info['total'];

            $configs = $this->db->where('is_active', 1)->where('user_id', $this->vendor_id)->get('bot_commission_config')->result();

            foreach ($configs as $cfg) {
                if ($cfg->applies_to === 'all') {
                    $base = $total_cobrado;
                    $bot_name = 'Todos los bots';
                } else {
                    $bot_id = (int)$cfg->applies_to;
                    $base = isset($cobros[$bot_id]) ? $cobros[$bot_id]['total'] : 0;
                    $bot_name = isset($cobros[$bot_id]) ? $cobros[$bot_id]['bot_name'] : 'Bot #' . $bot_id;
                }
                $amount = round($base * ($cfg->percentage / 100));

                $item = new stdClass();
                $item->bot_name = $bot_name;
                $item->percentage = $cfg->percentage;
                $item->base_amount = $base;
                $item->commission_amount = $amount;
                $mis_comisiones[] = $item;
                $total_comision += $amount;
            }
        }

        // Historial de últimos 6 meses
        $historial = $this->db->select('p.period_label, p.period_start, p.period_end, d.commission_amount, d.base_amount, d.percentage, d.status, d.bot_name')
            ->from('bot_commission_details d')
            ->join('bot_commission_periods p', 'p.id = d.period_id')
            ->where('d.user_id', $this->vendor_id)
            ->order_by('p.period_start', 'DESC')
            ->limit(12)
            ->get()->result();

        $data = array(
            'vendor' => $this->vendor,
            'period_label' => $period_label,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'month' => $month,
            'period' => $period,
            'mis_comisiones' => $mis_comisiones,
            'total_comision' => $total_comision,
            'historial' => $historial,
        );
        $this->load->view('ventas/comisiones', $data);
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
            // Eliminar detalle anterior
            $this->db->where('budgetId', $id)->delete('budget_detail');

            $new_total = 0;
            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = trim($product_ids[$i]);
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
     * AJAX: Buscar productos
     */
    public function buscarProducto()
    {
        if (!$this->_checkAuth()) return;
        header('Content-Type: application/json');

        $q = trim($this->input->get('q'));
        if (strlen($q) < 2) { echo json_encode(array()); return; }

        $this->db->select('idProduct, description, price');
        $this->db->from('products');
        $this->db->group_start();
        $this->db->like('idProduct', $q);
        $this->db->or_like('description', $q);
        $this->db->group_end();
        $this->db->where('deleted IS NULL OR deleted = 0');
        $this->db->limit(10);
        echo json_encode($this->db->get()->result());
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
        $this->session->unset_userdata('user_data');
        redirect(base_url() . 'ventas/login');
    }

    private function _getCobrosPerBot($from, $to)
    {
        $sql = "SELECT bc.id as bot_config_id, bc.name as bot_name, bc.default_vendor_id,
                       COALESCE(SUM(b.total), 0) as total, COUNT(DISTINCT i.idInvoice) as facturas
                FROM builderbot_configs bc
                LEFT JOIN invoices i ON i.vendorId = bc.default_vendor_id
                    AND i.state = 2
                    AND i.date >= ?
                    AND i.date <= ?
                    AND (i.deleted IS NULL OR i.deleted = 0)
                LEFT JOIN budgets b ON b.idBudget = i.budgetId
                    AND b.total > 0
                    AND (b.deleted IS NULL OR b.deleted = 0)
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
