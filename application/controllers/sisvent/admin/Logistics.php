<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logistics extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('reporte_logistica');
        $this->load->model('invoices_model');
        $this->load->model('budgets_model');
    }

    /**
     * Reporte de logística — Bitácora MAM
     */
    public function index()
    {
        $fecha_desde = $this->input->get('desde') ?: date('Y-m-d');
        $fecha_hasta = $this->input->get('hasta') ?: date('Y-m-d');
        $transportadora = $this->input->get('transportadora') ?: 'all';
        $vendedor = $this->input->get('vendedor') ?: 'all';
        $store = $this->input->get('store') ?: 'all';
        $tab = $this->input->get('tab') ?: 'pedidos';

        // ---- KPIs acumulado año ----
        $year = date('Y');
        $kpis = $this->_getKPIs($year);

        // ---- Query principal: facturas del rango ----
        $facturas = $this->_getFacturas($fecha_desde, $fecha_hasta, $transportadora, $vendedor, $store);

        // ---- Presupuestos pendientes (no facturados) ----
        $pendientes = $this->_getPendientes($fecha_desde, $fecha_hasta);

        // ---- Totales del rango ----
        $totals = new stdClass();
        $totals->facturas_count = count($facturas);
        $totals->facturas_total = array_sum(array_map(function($f){ return (float)$f->invoice_total; }, $facturas));
        $totals->envios_total = array_sum(array_map(function($f){ return (float)$f->shipping_cost; }, $facturas));
        $totals->pendientes_count = count($pendientes);
        $totals->embalados = count(array_filter($facturas, function($f){ return !empty($f->embalado_at); }));
        $totals->despachados = count(array_filter($facturas, function($f){ return !empty($f->despachado_at) || !empty($f->numeroPreenvio); }));

        // ---- Vendedores y tiendas para filtros ----
        $this->db->select('idUser, name')->from('users')->where('deleted', 0)->where('role', 3)->order_by('name');
        $vendedores = $this->db->get()->result();

        $this->db->select('idStore, name')->from('stores')->where('deleted', 0)->order_by('name');
        $tiendas = $this->db->get()->result();

        $data = array(
            'facturas' => $facturas,
            'pendientes' => $pendientes,
            'totals' => $totals,
            'kpis' => $kpis,
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'transportadora' => $transportadora,
            'vendedor' => $vendedor,
            'store' => $store,
            'tab' => $tab,
            'vendedores' => $vendedores,
            'tiendas' => $tiendas,
            'year' => $year
        );

        $this->load->view('sisvent/admin/logistics/report', $data);
    }

    /**
     * KPIs acumulado año
     */
    private function _getKPIs($year)
    {
        $kpis = new stdClass();

        // Total pedidos (presupuestos) del año
        $this->db->where('YEAR(created_at)', $year)->where('deleted', 0);
        $kpis->total_pedidos = $this->db->count_all_results('budgets');

        // Total facturado
        $this->db->select('COALESCE(SUM(total),0) as total')->where('YEAR(date)', $year)->where('deleted', 0);
        $r = $this->db->get('invoices')->row();
        $kpis->total_facturado = (float)$r->total;

        // Entregados (facturas con shipping_guide estado 11 = entregado)
        $this->db->select('COUNT(DISTINCT i.idInvoice) as c')
            ->from('invoices i')
            ->join('shipping_guides sg', 'sg.invoiceId = i.idInvoice')
            ->where('YEAR(i.date)', $year)
            ->where('i.deleted', 0)
            ->where('sg.estadoGuia', 11);
        $r = $this->db->get()->row();
        $kpis->entregados = (int)$r->c;

        // Facturas del año
        $this->db->where('YEAR(date)', $year)->where('deleted', 0);
        $kpis->total_facturas = $this->db->count_all_results('invoices');

        $kpis->pendientes = $kpis->total_pedidos - $kpis->entregados;
        $kpis->pct_entregados = $kpis->total_pedidos > 0 ? round(($kpis->entregados / $kpis->total_pedidos) * 100) : 0;
        $kpis->ticket_promedio = $kpis->total_facturas > 0 ? $kpis->total_facturado / $kpis->total_facturas : 0;

        return $kpis;
    }

    /**
     * Facturas con datos completos para el reporte
     */
    private function _getFacturas($desde, $hasta, $transportadora, $vendedor, $store)
    {
        $this->db->select('
            i.idInvoice, i.budgetId, i.total as invoice_total,
            i.date as invoice_date, i.created_at as invoice_created,
            i.transportadora, i.despacho_destino, i.despachado_at, i.despachado_by,
            i.storeId,
            b.created_at as budget_created, b.embalado, b.embalado_at, b.embalado_by,
            c.name as client_name, c.city as client_city, c.address as client_address,
            u.name as vendor_name, u.idUser as vendorId,
            ue.name as embalador_name,
            sg.numeroPreenvio, sg.ciudadDestinoNombre as shipping_destination,
            sg.carrierName as shipping_carrier, sg.status as shipping_status,
            sg.valorTotal as shipping_cost, sg.estadoGuia, sg.estadoNombre,
            s.name as store_name
        ');
        $this->db->from('invoices i');
        $this->db->join('budgets b', 'b.idBudget = i.budgetId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');
        $this->db->join('users u', 'u.idUser = i.vendorId', 'left');
        $this->db->join('users ue', 'ue.idUser = b.embalado_by', 'left');
        $this->db->join('shipping_guides sg', 'sg.invoiceId = i.idInvoice', 'left');
        $this->db->join('stores s', 's.idStore = i.storeId', 'left');
        $this->db->where('DATE(i.date) >=', $desde);
        $this->db->where('DATE(i.date) <=', $hasta);
        $this->db->where('i.deleted', 0);
        if ($transportadora !== 'all') $this->db->where('i.transportadora', $transportadora);
        if ($vendedor !== 'all') $this->db->where('i.vendorId', $vendedor);
        if ($store !== 'all') $this->db->where('i.storeId', $store);
        $this->db->order_by('i.date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Presupuestos pendientes (state=0, no facturados)
     */
    private function _getPendientes($desde, $hasta)
    {
        $this->db->select('
            b.idBudget, b.total, b.created_at, b.embalado, b.embalado_at, b.embalado_by,
            c.name as client_name, c.city as client_city,
            u.name as vendor_name,
            ue.name as embalador_name,
            s.name as store_name
        ');
        $this->db->from('budgets b');
        $this->db->join('clients c', 'c.idClient = b.clientId', 'left');
        $this->db->join('users u', 'u.idUser = b.vendorId', 'left');
        $this->db->join('users ue', 'ue.idUser = b.embalado_by', 'left');
        $this->db->join('stores s', 's.idStore = b.storeId', 'left');
        $this->db->where('b.state', 0);
        $this->db->where('b.deleted', 0);
        $this->db->where('b.archived', 0);
        $this->db->where('DATE(b.created_at) >=', $desde);
        $this->db->where('DATE(b.created_at) <=', $hasta);
        $this->db->order_by('b.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Asignar transportadora a una factura (AJAX)
     */
    public function assignTransport()
    {
        $invoiceId = $this->input->post('invoiceId');
        $transportadora = $this->input->post('transportadora');
        $destino = $this->input->post('destino');
        $user = $this->session->userdata('user_data')['uname'];

        $this->db->update('invoices', array(
            'transportadora' => $transportadora,
            'despacho_destino' => $destino ?: '',
            'despachado_at' => date('Y-m-d H:i:s'),
            'despachado_by' => $user
        ), array('idInvoice' => $invoiceId));

        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
    }
}
