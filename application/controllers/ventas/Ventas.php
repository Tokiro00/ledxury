<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ventas extends CI_Controller {

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

        // Presupuestos pendientes
        $this->db->select('COUNT(*) as count');
        $this->db->from('budgets');
        $this->db->where('state', 0);
        $this->db->where('deleted', 0);
        if (!$is_admin) $this->db->where('vendorId', $this->vendor_id);
        $pending = $this->db->get()->row();

        $data = array(
            'vendor' => $this->vendor,
            'is_admin' => $is_admin,
            'sales_today' => $sales_today,
            'sales_week' => $sales_week,
            'sales_month' => $sales_month,
            'pending_count' => $pending->count,
        );
        $this->load->view('ventas/dashboard', $data);
    }

    /**
     * Lista de presupuestos pendientes
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
        if (!$is_admin) $this->db->where('b.vendorId', $this->vendor_id);
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

        if ($period) {
            $mis_comisiones = $this->db->where('period_id', $period->id)->where('user_id', $this->vendor_id)->get('bot_commission_details')->result();
            foreach ($mis_comisiones as $c) $total_comision += $c->commission_amount;
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
}
