<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Salesboard — Panel de control de vendedores
 *
 * Tablero gerencial para seguimiento de vendedores:
 * meta diaria, ranking, tasa de conversión, clientes inactivos
 */
class Salesboard extends CI_Controller {

    const STORES_MDE = [1, 3, 5];

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('reporte_vendedores');
        $this->load->model('invoices_model');
        $this->load->model('budgets_model');
        $this->load->model('clients_model');
        $this->load->model('tracking_model');
        $this->load->model('users_model');
    }

    /**
     * Panel principal — todos los vendedores
     */
    public function index()
    {
        $year = (int) ($this->input->get('year') ?: date('Y'));
        $month = (int) ($this->input->get('month') ?: date('n'));
        $storeFilter = $this->input->get('store') ?: 'all';
        $isCurrentMonth = ($year == date('Y') && $month == date('n'));
        $today = date('Y-m-d');
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $dayOfMonth = $isCurrentMonth ? (int) date('j') : $daysInMonth;
        $workDaysLeft = $isCurrentMonth ? $this->_workDaysLeft() : 0;

        $storeIds = ($storeFilter !== 'all') ? array((int)$storeFilter) : self::STORES_MDE;

        // Tiendas para filtro
        $this->db->select('idStore, name')->from('stores')->where('deleted', 0)->order_by('name');
        $tiendas = $this->db->get()->result();

        // Metas de todos los vendedores
        $goalsRaw = $this->invoices_model->getAllVendorsGoals($year);
        $goals = array();
        $monthField = 'm' . $month;
        if ($goalsRaw) {
            foreach ($goalsRaw as $g) {
                $goals[$g->userId] = isset($g->$monthField) ? (int)$g->$monthField : 0;
            }
        }

        // Ranking de ventas del mes (con nombre)
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-d', strtotime(date('Y-m-t', strtotime($from)) . ' +1 day'));
        $this->db->select('invoices.vendorId, users.name as vendor_name, SUM(invoices.total) as total_ventas')
            ->from('invoices')
            ->join('users', 'users.idUser = invoices.vendorId')
            ->where_in('invoices.state', [1, 2, 3])
            ->where('invoices.deleted', 0)
            ->where('invoices.date >=', $from)
            ->where('invoices.date <', $to)
            ->where_in('invoices.storeId', $storeIds)
            ->group_by('invoices.vendorId')
            ->order_by('total_ventas', 'DESC');
        $ranking = $this->db->get()->result();

        // Ventas de hoy por vendedor
        $this->db->select('vendorId, SUM(total) as ventas_hoy')
            ->from('invoices')
            ->where('DATE(date)', $today)
            ->where('deleted', 0)
            ->where_in('storeId', $storeIds)
            ->group_by('vendorId');
        $ventasHoyRaw = $this->db->get()->result();
        $ventasHoy = array();
        foreach ($ventasHoyRaw as $v) $ventasHoy[$v->vendorId] = (float)$v->ventas_hoy;

        // Presupuestos del mes por vendedor (para conversión)
        $this->db->select('vendorId, COUNT(*) as total_budgets')
            ->from('budgets')
            ->where('MONTH(date)', $month)->where('YEAR(date)', $year)
            ->where('deleted', 0)
            ->group_by('vendorId');
        $budgetsRaw = $this->db->get()->result();
        $budgetsByVendor = array();
        foreach ($budgetsRaw as $b) $budgetsByVendor[$b->vendorId] = (int)$b->total_budgets;

        // Facturas del mes por vendedor (para conversión)
        $this->db->select('vendorId, COUNT(*) as total_invoices')
            ->from('invoices')
            ->where('MONTH(date)', $month)->where('YEAR(date)', $year)
            ->where('deleted', 0)
            ->group_by('vendorId');
        $invoicesRaw = $this->db->get()->result();
        $invoicesByVendor = array();
        foreach ($invoicesRaw as $i) $invoicesByVendor[$i->vendorId] = (int)$i->total_invoices;

        // Cobros del mes por vendedor
        $from = sprintf('%04d-%02d-01', $year, $month);
        $toDate = date('Y-m-t', strtotime($from));
        $this->db->select("payments.vendorId, SUM(payments.payment) as total_cobros")
            ->from('payments')
            ->where('payments.date >=', $from)
            ->where('payments.date <=', $toDate . ' 23:59:59')
            ->group_by('payments.vendorId');
        $cobrosRaw = $this->db->get()->result();
        $cobrosByVendor = array();
        foreach ($cobrosRaw as $c) $cobrosByVendor[$c->vendorId] = (float)$c->total_cobros;

        // Último presupuesto por vendedor (actividad)
        $this->db->select('vendorId, MAX(created_at) as last_budget')
            ->from('budgets')
            ->where('deleted', 0)
            ->group_by('vendorId');
        $lastBudgetRaw = $this->db->get()->result();
        $lastBudget = array();
        foreach ($lastBudgetRaw as $l) $lastBudget[$l->vendorId] = $l->last_budget;

        // Armar tabla de vendedores
        $vendors = array();
        foreach ($ranking as $r) {
            $meta = isset($goals[$r->vendorId]) ? $goals[$r->vendorId] : 0;
            $ventas = (float)$r->total_ventas;
            $pct = $meta > 0 ? round(($ventas / $meta) * 100, 1) : 0;
            $metaDiaria = $workDaysLeft > 0 ? max(0, $meta - $ventas) / $workDaysLeft : 0;
            $hoy = isset($ventasHoy[$r->vendorId]) ? $ventasHoy[$r->vendorId] : 0;
            $budgets = isset($budgetsByVendor[$r->vendorId]) ? $budgetsByVendor[$r->vendorId] : 0;
            $invoices = isset($invoicesByVendor[$r->vendorId]) ? $invoicesByVendor[$r->vendorId] : 0;
            $conversion = $budgets > 0 ? round(($invoices / $budgets) * 100) : 0;
            $lastAct = isset($lastBudget[$r->vendorId]) ? $lastBudget[$r->vendorId] : null;
            $diasSinActividad = $lastAct ? (int)((time() - strtotime($lastAct)) / 86400) : 999;

            $vendors[] = (object) array(
                'vendorId' => $r->vendorId,
                'name' => $r->vendor_name,
                'ventasMes' => $ventas,
                'meta' => $meta,
                'pctMeta' => $pct,
                'ventasHoy' => $hoy,
                'metaDiaria' => $metaDiaria,
                'budgets' => $budgets,
                'invoices' => $invoices,
                'conversion' => $conversion,
                'cobros' => isset($cobrosByVendor[$r->vendorId]) ? $cobrosByVendor[$r->vendorId] : 0,
                'lastActivity' => $lastAct,
                'diasSinActividad' => $diasSinActividad
            );
        }

        // Totales
        $totalVentas = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'ventasMes'));
        $totalMeta = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'meta'));
        $totalHoy = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'ventasHoy'));
        $cumplieron = count(array_filter($vendors, function($v){ return $v->pctMeta >= 100; }));
        $totalCobros = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'cobros'));

        $data = array(
            'vendors' => $vendors,
            'totalVentas' => $totalVentas,
            'totalMeta' => $totalMeta,
            'totalHoy' => $totalHoy,
            'cumplieron' => $cumplieron,
            'totalVendors' => count($vendors),
            'monthName' => $this->_getMonthName($month),
            'year' => $year,
            'month' => $month,
            'dayOfMonth' => $dayOfMonth,
            'daysInMonth' => $daysInMonth,
            'workDaysLeft' => $workDaysLeft,
            'isCurrentMonth' => $isCurrentMonth,
            'totalCobros' => $totalCobros,
            'tiendas' => $tiendas,
            'storeFilter' => $storeFilter
        );

        $this->load->view('sisvent/admin/salesboard/index', $data);
    }

    /**
     * Clientes inactivos por vendedor
     */
    public function inactivos()
    {
        $vendorId = $this->input->get('vendor') ?: 'all';
        $days = (int) $this->input->get('days') ?: 30;
        $date = date('Y-m-d', strtotime("-{$days} days"));

        if ($vendorId !== 'all') {
            $inactivos = $this->clients_model->getUnattendedClients($vendorId, $date);
        } else {
            $inactivos = $this->clients_model->getAllUnattendedClients($date);
        }

        // Vendedores para filtro
        $this->db->select('idUser, name')->from('users')->where('role', 3)->where('deleted', 0)->order_by('name');
        $vendedores = $this->db->get()->result();

        $data = array(
            'inactivos' => $inactivos,
            'vendedores' => $vendedores,
            'vendorId' => $vendorId,
            'days' => $days
        );

        $this->load->view('sisvent/admin/salesboard/inactivos', $data);
    }

    /**
     * Panel de metas — ver y editar metas de todos los vendedores
     */
    public function metas()
    {
        $year = (int) ($this->input->get('year') ?: date('Y'));
        $storeFilter = $this->input->get('store') ?: 'all';
        $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        $storeIds = ($storeFilter !== 'all') ? array((int)$storeFilter) : self::STORES_MDE;

        // Tiendas para filtro
        $this->db->select('idStore, name')->from('stores')->where('deleted', 0)->order_by('name');
        $tiendas = $this->db->get()->result();

        // Vendedores activos: role 3 + que tengan facturas en el año o meta definida
        $vendedores = $this->db->query("
            SELECT DISTINCT u.idUser, u.name FROM users u
            LEFT JOIN invoices i ON i.vendorId = u.idUser AND YEAR(i.date) = ? AND i.deleted = 0
            LEFT JOIN sales_goal sg ON sg.userId = u.idUser AND sg.year = ?
            WHERE u.role = 3 AND u.deleted = 0
            AND (i.idInvoice IS NOT NULL OR sg.userId IS NOT NULL)
            ORDER BY u.name
        ", array($year, $year))->result();

        // Metas existentes
        $goalsRaw = $this->invoices_model->getAllVendorsGoals($year);
        $goals = array();
        if ($goalsRaw) {
            foreach ($goalsRaw as $g) {
                $goals[$g->userId] = $g;
            }
        }

        // Ventas reales por vendedor (acumulado por mes)
        $ventasReales = array();
        foreach ($vendedores as $v) {
            $ventasReales[$v->idUser] = array();
            for ($m = 1; $m <= 12; $m++) {
                $this->db->select('COALESCE(SUM(total),0) as t')
                    ->where('vendorId', $v->idUser)
                    ->where('MONTH(date)', $m)->where('YEAR(date)', $year)
                    ->where('deleted', 0)->where_in('storeId', $storeIds);
                $ventasReales[$v->idUser][$m] = (float)$this->db->get('invoices')->row()->t;
            }
        }

        $data = array(
            'vendedores' => $vendedores,
            'goals' => $goals,
            'ventasReales' => $ventasReales,
            'months' => $months,
            'tiendas' => $tiendas,
            'storeFilter' => $storeFilter,
            'year' => $year
        );

        $this->load->view('sisvent/admin/salesboard/metas', $data);
    }

    /**
     * AJAX: Guardar meta de un vendedor
     */
    public function saveMeta()
    {
        $userId = $this->input->post('userId');
        $year = (int) $this->input->post('year');
        $data = array('userId' => $userId, 'year' => $year);
        for ($m = 1; $m <= 12; $m++) {
            $data['m'.$m] = (int) $this->input->post('m'.$m);
        }
        $this->invoices_model->saveVendorSalesGoal($data);

        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
    }

    /**
     * AJAX: Copiar metas de un año a otro o aplicar valor a todos
     */
    public function bulkMeta()
    {
        $year = (int) $this->input->post('year');
        $value = (int) $this->input->post('value');
        $fromMonth = (int) $this->input->post('fromMonth') ?: (int)date('n');

        $this->db->select('idUser')->from('users')->where('role', 3)->where('deleted', 0);
        $vendedores = $this->db->get()->result();

        foreach ($vendedores as $v) {
            // Obtener metas existentes para no borrar meses pasados
            $existing = $this->invoices_model->getVendorSalesYearGoal($v->idUser, $year);
            $data = array('userId' => $v->idUser, 'year' => $year);
            for ($m = 1; $m <= 12; $m++) {
                if ($m >= $fromMonth) {
                    $data['m'.$m] = $value;
                } else {
                    $data['m'.$m] = ($existing && isset($existing->{'m'.$m})) ? (int)$existing->{'m'.$m} : 0;
                }
            }
            $this->invoices_model->saveVendorSalesGoal($data);
        }

        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'count' => count($vendedores)));
    }

    private function _workDaysLeft()
    {
        $today = new DateTime();
        $endMonth = new DateTime(date('Y-m-t'));
        $days = 0;
        $current = clone $today;
        while ($current <= $endMonth) {
            if ($current->format('N') < 6) $days++; // Lun-Vie
            $current->modify('+1 day');
        }
        return max($days, 1);
    }

    private function _getMonthName($m)
    {
        $names = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        return isset($names[$m]) ? $names[$m] : '';
    }
}
