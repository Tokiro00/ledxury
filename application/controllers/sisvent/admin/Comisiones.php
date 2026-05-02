<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Comisiones extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->control([1, 2]);
        $this->backend_lib->controlBotsAccess();
        $this->load->model('builderbot_model');
    }

    /**
     * Panel admin de comisiones
     */
    public function index()
    {
        date_default_timezone_set("America/Bogota");

        $month = $this->input->get('month') ?: date('Y-m');
        $parts = explode('-', $month);
        $year = (int)$parts[0];
        $m = (int)$parts[1];

        // Período: del 21 del mes anterior al 20 del mes actual
        $period_start = date('Y-m-d', mktime(0, 0, 0, $m - 1, 21, $year));
        $period_end = date('Y-m-d', mktime(0, 0, 0, $m, 20, $year));
        $period_label = date('F Y', mktime(0, 0, 0, $m, 1, $year));

        // Obtener cobros de contrapago en el período por vendedor
        $cobros = $this->_getCobrosPerBot($period_start, $period_end);

        // Obtener configuración de comisiones
        $configs = $this->db->where('is_active', 1)->get('bot_commission_config')->result();

        // Calcular comisiones
        $comisiones = array();
        $total_comisiones = 0;
        $total_cobrado = 0;

        foreach ($cobros as $bot_id => $info) {
            $total_cobrado += $info['total'];
        }

        foreach ($configs as $cfg) {
            $user = $this->db->where('idUser', $cfg->user_id)->get('users')->row();
            $user_name = $user ? $user->name : $cfg->user_id;

            if ($cfg->applies_to === 'all') {
                // Se aplica sobre todas las ventas de todos los bots
                $base = $total_cobrado;
                $bot_name = 'Todos los bots';
            } else {
                // Se aplica sobre ventas del bot específico
                $bot_id = (int)$cfg->applies_to;
                $base = isset($cobros[$bot_id]) ? $cobros[$bot_id]['total'] : 0;
                $bot_name = isset($cobros[$bot_id]) ? $cobros[$bot_id]['bot_name'] : 'Bot #' . $bot_id;
            }

            $amount = round($base * ($cfg->percentage / 100));

            $comisiones[] = array(
                'user_id' => $cfg->user_id,
                'user_name' => $user_name,
                'type' => $cfg->commission_type,
                'type_label' => $this->_typeLabel($cfg->commission_type),
                'percentage' => $cfg->percentage,
                'base' => $base,
                'amount' => $amount,
                'bot_name' => $bot_name,
            );
            $total_comisiones += $amount;
        }

        // Verificar si ya está liquidado
        $period = $this->db->where('period_start', $period_start)->where('period_end', $period_end)->get('bot_commission_periods')->row();

        $data = array(
            'comisiones' => $comisiones,
            'cobros' => $cobros,
            'total_cobrado' => $total_cobrado,
            'total_comisiones' => $total_comisiones,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'period_label' => $period_label,
            'month' => $month,
            'period' => $period,
            'role' => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/comisiones/index', $data);
    }

    /**
     * AJAX: Liquidar período
     */
    public function liquidar()
    {
        header('Content-Type: application/json');
        date_default_timezone_set("America/Bogota");

        $period_start = $this->input->post('period_start');
        $period_end = $this->input->post('period_end');
        $uid = $this->session->userdata('user_data')['uname'];

        // Verificar si ya existe
        $existing = $this->db->where('period_start', $period_start)->where('period_end', $period_end)->get('bot_commission_periods')->row();
        if ($existing && $existing->status === 'liquidado') {
            echo json_encode(array('success' => false, 'message' => 'Este período ya fue liquidado'));
            return;
        }

        $cobros = $this->_getCobrosPerBot($period_start, $period_end);
        $configs = $this->db->where('is_active', 1)->get('bot_commission_config')->result();

        $total_cobrado = 0;
        foreach ($cobros as $info) $total_cobrado += $info['total'];

        $month_parts = explode('-', $period_end);
        $period_label = date('F Y', mktime(0, 0, 0, (int)$month_parts[1], 1, (int)$month_parts[0]));

        // Crear o actualizar período
        if ($existing) {
            $period_id = $existing->id;
            $this->db->where('id', $period_id)->update('bot_commission_periods', array(
                'total_cobrado' => $total_cobrado,
                'status' => 'liquidado',
                'liquidated_by' => $uid,
                'liquidated_at' => date('Y-m-d H:i:s'),
            ));
            $this->db->where('period_id', $period_id)->delete('bot_commission_details');
            $this->db->where('period_id', $period_id)->delete('bot_commission_invoice_items');
        } else {
            $this->db->insert('bot_commission_periods', array(
                'period_start' => $period_start,
                'period_end' => $period_end,
                'period_label' => $period_label,
                'total_cobrado' => $total_cobrado,
                'status' => 'liquidado',
                'liquidated_by' => $uid,
                'liquidated_at' => date('Y-m-d H:i:s'),
            ));
            $period_id = $this->db->insert_id();
        }

        // Mapa bot_config_id -> default_vendor_id para snapshot de facturas
        $bot_vendor_map = [];
        foreach ($cobros as $bot_id => $info) {
            if (!empty($info['vendor_id'])) $bot_vendor_map[$bot_id] = $info['vendor_id'];
        }
        $all_vendor_ids = array_values(array_unique(array_filter($bot_vendor_map)));

        // Guardar detalle y snapshot de facturas que lo componen
        $total_comisiones = 0;
        foreach ($configs as $cfg) {
            $user = $this->db->where('idUser', $cfg->user_id)->get('users')->row();
            $user_name = $user ? $user->name : $cfg->user_id;

            if ($cfg->applies_to === 'all') {
                $base = $total_cobrado;
                $bot_name = 'Todos';
                $bot_cfg_id = null;
                $scope_vendor_ids = $all_vendor_ids;
            } else {
                $bot_id = (int)$cfg->applies_to;
                $base = isset($cobros[$bot_id]) ? $cobros[$bot_id]['total'] : 0;
                $bot_name = isset($cobros[$bot_id]) ? $cobros[$bot_id]['bot_name'] : '';
                $bot_cfg_id = $bot_id;
                $scope_vendor_ids = isset($bot_vendor_map[$bot_id]) ? [$bot_vendor_map[$bot_id]] : [];
            }
            $amount = round($base * ($cfg->percentage / 100));
            $total_comisiones += $amount;

            $this->db->insert('bot_commission_details', array(
                'period_id' => $period_id,
                'user_id' => $cfg->user_id,
                'user_name' => $user_name,
                'commission_type' => $cfg->commission_type,
                'percentage' => $cfg->percentage,
                'base_amount' => $base,
                'commission_amount' => $amount,
                'bot_config_id' => $bot_cfg_id,
                'bot_name' => $bot_name,
            ));
            $detail_id = $this->db->insert_id();

            // Snapshot de facturas cobradas (state=2) en el rango que dieron origen a la base
            if (!empty($scope_vendor_ids)) {
                $invs = $this->db->select('i.idInvoice, i.invoice_number, i.date, i.vendorId, i.clientId, i.budgetId, i.state, i.total, u.name as vendor_name, c.name as client_name')
                    ->from('invoices i')
                    ->join('users u', 'u.idUser = i.vendorId', 'left')
                    ->join('clients c', 'c.idClient = i.clientId', 'left')
                    ->where('i.state', 2)
                    ->where_in('i.vendorId', $scope_vendor_ids)
                    ->where('i.total >', 0)
                    ->where('i.date >=', $period_start . ' 00:00:00')
                    ->where('i.date <=', $period_end . ' 23:59:59')
                    ->group_start()->where('i.deleted IS NULL', null, false)->or_where('i.deleted', 0)->group_end()
                    ->get()->result();

                foreach ($invs as $inv) {
                    $inv_total = (float)($inv->total ?: 0);
                    if ($inv_total <= 0) continue;
                    $this->db->insert('bot_commission_invoice_items', array(
                        'period_id' => $period_id,
                        'detail_id' => $detail_id,
                        'user_id' => $cfg->user_id,
                        'invoice_id' => $inv->idInvoice,
                        'budget_id' => $inv->budgetId,
                        'client_id' => $inv->clientId,
                        'client_name' => $inv->client_name,
                        'vendor_id' => $inv->vendorId,
                        'vendor_name' => $inv->vendor_name,
                        'bot_config_id' => $bot_cfg_id,
                        'invoice_date' => $inv->date,
                        'invoice_total' => $inv_total,
                        'percentage' => $cfg->percentage,
                        'commission_amount' => round($inv_total * ($cfg->percentage / 100)),
                        'invoice_state' => $inv->state,
                    ));
                }
            }
        }

        $this->db->where('id', $period_id)->update('bot_commission_periods', array('total_comisiones' => $total_comisiones));

        echo json_encode(array('success' => true, 'message' => 'Período liquidado. Total comisiones: $' . number_format($total_comisiones, 0, ',', '.')));
    }

    /**
     * Obtener ventas pagadas por bot en un período
     * Se calcula desde facturas pagadas (state=2) vinculadas a presupuestos con total
     */
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

    private function _typeLabel($type) {
        $labels = array('admin_bots' => 'Admin Bots', 'operator' => 'Operador Bot', 'ads_manager' => 'Admin Publicidad');
        return isset($labels[$type]) ? $labels[$type] : $type;
    }
}
