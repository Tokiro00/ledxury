<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Salesboard — Panel de control de vendedores
 *
 * Tablero gerencial para seguimiento de vendedores:
 * meta diaria, ranking, tasa de conversión, clientes inactivos
 */
class Salesboard extends CI_Controller {

    // Fallback default — editable sin deploy via company_settings
    // (key 'stores_mde'). Ver /sisvent/admin/settings.
    const STORES_MDE = [1, 3, 5];

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('reporte_vendedores');
        $this->load->library('settings_lib');
        $this->load->model('invoices_model');
        $this->load->model('budgets_model');
        $this->load->model('clients_model');
        $this->load->model('tracking_model');
        $this->load->model('users_model');
        $this->load->library('invoice_reports_service');
        $this->load->library('invoice_vendor_service');
    }

    /**
     * Panel principal — todos los vendedores
     */
    public function index()
    {
        $year = (int) ($this->input->get('year') ?: date('Y'));
        $month = (int) ($this->input->get('month') ?: date('n'));
        $storeFilter = $this->input->get('store') ?: 'all';
        $view = $this->input->get('view') === 'ytd' ? 'ytd' : 'month';
        $isYtd = ($view === 'ytd');
        $isCurrentMonth = ($year == date('Y') && $month == date('n'));
        $today = date('Y-m-d');
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $dayOfMonth = $isCurrentMonth ? (int) date('j') : $daysInMonth;
        $workDaysLeft = $isCurrentMonth ? $this->_workDaysLeft() : 0;

        $storeIds = ($storeFilter !== 'all') ? array((int)$storeFilter) : $this->settings_lib->get('stores_mde', self::STORES_MDE);

        // Tiendas para filtro
        $this->db->select('idStore, name')->from('stores')->where('deleted', 0)->order_by('name');
        $tiendas = $this->db->get()->result();

        // Metas de todos los vendedores (acumuladas si YTD)
        $goalsRaw = $this->invoice_reports_service->getAllVendorsGoals($year);
        $goals = array();
        if ($goalsRaw) {
            foreach ($goalsRaw as $g) {
                if ($isYtd) {
                    $sum = 0;
                    for ($mi = 1; $mi <= $month; $mi++) {
                        $f = 'm' . $mi;
                        $sum += isset($g->$f) ? (int)$g->$f : 0;
                    }
                    $goals[$g->userId] = $sum;
                } else {
                    $f = 'm' . $month;
                    $goals[$g->userId] = isset($g->$f) ? (int)$g->$f : 0;
                }
            }
        }

        // Rango de fechas del periodo
        $from = $isYtd ? sprintf('%04d-01-01', $year) : sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-d', strtotime(date('Y-m-t', mktime(0, 0, 0, $month, 1, $year)) . ' +1 day'));
        $this->db->select('invoices.vendorId, users.name as vendor_name, SUM(invoices.total - invoices.discount) as total_ventas, SUM(invoices.payment) as total_collected, COUNT(invoices.idInvoice) as invoice_count')
            ->from('invoices')
            ->join('users', 'users.idUser = invoices.vendorId')
            ->where('invoices.deleted', 0)
            ->where('invoices.date >=', $from)
            ->where('invoices.date <', $to);
        if ($storeFilter !== 'all') {
            $this->db->where_in('invoices.storeId', $storeIds);
        }
        $this->db->group_by('invoices.vendorId')
            ->order_by('total_ventas', 'DESC');
        $ranking = $this->db->get()->result();

        // Ventas de hoy por vendedor
        $this->db->select('vendorId, SUM(total - discount) as ventas_hoy')
            ->from('invoices')
            ->where('DATE(date)', $today)
            ->where('deleted', 0);
        if ($storeFilter !== 'all') {
            $this->db->where_in('storeId', $storeIds);
        }
        $this->db->group_by('vendorId');
        $ventasHoyRaw = $this->db->get()->result();
        $ventasHoy = array();
        foreach ($ventasHoyRaw as $v) $ventasHoy[$v->vendorId] = (float)$v->ventas_hoy;

        // Presupuestos del periodo por vendedor (para conversión)
        $this->db->select('vendorId, COUNT(*) as total_budgets')
            ->from('budgets')
            ->where('date >=', $from)->where('date <', $to)
            ->where('deleted', 0)
            ->group_by('vendorId');
        $budgetsRaw = $this->db->get()->result();
        $budgetsByVendor = array();
        foreach ($budgetsRaw as $b) $budgetsByVendor[$b->vendorId] = (int)$b->total_budgets;

        // Facturas del periodo por vendedor (para conversión)
        $this->db->select('vendorId, COUNT(*) as total_invoices')
            ->from('invoices')
            ->where('date >=', $from)->where('date <', $to)
            ->where('deleted', 0)
            ->group_by('vendorId');
        $invoicesRaw = $this->db->get()->result();
        $invoicesByVendor = array();
        foreach ($invoicesRaw as $i) $invoicesByVendor[$i->vendorId] = (int)$i->total_invoices;

        // Cobros del periodo por vendedor
        $this->db->select("payments.vendorId, SUM(payments.payment) as total_cobros")
            ->from('payments')
            ->where('payments.deleted', 0)
            ->where('payments.date >=', $from)
            ->where('payments.date <', $to)
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

        // Clientes asignados por vendedor
        $assignedRaw = $this->db->select('vendor, COUNT(*) as total')
            ->from('clients')->where('deleted', 0)->where('blacklisted', 0)
            ->where("vendor != ''")->group_by('vendor')->get()->result();
        $assignedMap = array();
        foreach ($assignedRaw as $a) $assignedMap[$a->vendor] = (int)$a->total;

        // Clientes activos (con factura en últimos 90 días) por vendedor
        $activeQ = $this->db->select('c.vendor, COUNT(DISTINCT c.idClient) as total')
            ->from('clients c')
            ->join('invoices i', 'i.clientId = c.idClient AND i.deleted = 0 AND i.date >= DATE_SUB(NOW(), INTERVAL 90 DAY)')
            ->where('c.deleted', 0)->group_by('c.vendor')->get()->result();
        $activeMap = array();
        foreach ($activeQ as $a) $activeMap[$a->vendor] = (int)$a->total;

        // Periodo anterior: mes previo en modo Mensual, YTD del año anterior en modo Acumulado
        if ($isYtd) {
            $prevFrom = sprintf('%04d-01-01', $year - 1);
            $prevTo = date('Y-m-d', strtotime(date('Y-m-t', mktime(0, 0, 0, $month, 1, $year - 1)) . ' +1 day'));
        } else {
            $prevMonthNum = $month > 1 ? $month - 1 : 12;
            $prevYearNum = $month > 1 ? $year : $year - 1;
            $prevFrom = sprintf('%04d-%02d-01', $prevYearNum, $prevMonthNum);
            $prevTo = date('Y-m-d', strtotime(date('Y-m-t', mktime(0, 0, 0, $prevMonthNum, 1, $prevYearNum)) . ' +1 day'));
        }
        $prevRaw = $this->db->select('vendorId, SUM(total - discount) as total_prev')
            ->from('invoices')
            ->where('deleted', 0)->where('date >=', $prevFrom)->where('date <', $prevTo);
        if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
        $prevRaw = $this->db->group_by('vendorId')->get()->result();
        $prevMap = array();
        foreach ($prevRaw as $p) $prevMap[$p->vendorId] = (float)$p->total_prev;

        // RFM health por vendedor (si el modelo CRM está disponible)
        $healthMap = array();
        $typesMap = array();
        if (file_exists(APPPATH . 'models/Crm_model.php')) {
            $this->load->model('crm_model');
            $rfmData = $this->crm_model->calculateRfmScores(
                $storeFilter !== 'all' ? array('store_id' => $storeFilter) : array()
            );
            foreach ($rfmData as $c) {
                $v = $c->vendor;
                if (empty($v)) continue;
                if (!isset($healthMap[$v])) { $healthMap[$v] = [0, 0]; $typesMap[$v] = ['A'=>0,'B'=>0,'C'=>0,'D'=>0]; }
                $healthMap[$v][0] += $c->health_score;
                $healthMap[$v][1]++;
                $t = $c->suggested_type;
                if (isset($typesMap[$v][$t])) $typesMap[$v][$t]++;
            }
        }

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

            // Nuevos campos
            $vid = $r->vendorId;
            $assigned = isset($assignedMap[$vid]) ? $assignedMap[$vid] : 0;
            $active = isset($activeMap[$vid]) ? $activeMap[$vid] : 0;
            $prevVentas = isset($prevMap[$vid]) ? $prevMap[$vid] : 0;
            $tendencia = $prevVentas > 0 ? round((($ventas - $prevVentas) / $prevVentas) * 100) : ($ventas > 0 ? 100 : 0);
            $avgHealth = (isset($healthMap[$vid]) && $healthMap[$vid][1] > 0) ? round($healthMap[$vid][0] / $healthMap[$vid][1]) : 0;
            $types = isset($typesMap[$vid]) ? $typesMap[$vid] : ['A'=>0,'B'=>0,'C'=>0,'D'=>0];

            // Proyección: en YTD extrapola a fin de año, en Mensual a fin de mes
            if ($isYtd) {
                $monthsElapsed = $isCurrentMonth ? max(1, ($month - 1) + ($dayOfMonth / $daysInMonth)) : $month;
                $proyeccion = $monthsElapsed > 0 ? round(($ventas / $monthsElapsed) * $month) : 0;
            } else {
                $diasTranscurridos = $dayOfMonth;
                $ritmo = $diasTranscurridos > 0 ? $ventas / $diasTranscurridos : 0;
                $proyeccion = round($ritmo * $daysInMonth);
            }
            $proyEstado = $meta > 0 ? ($proyeccion >= $meta ? 'alcanzable' : ($proyeccion >= $meta * 0.8 ? 'ajustado' : 'dificil')) : 'sin_meta';

            $vendors[] = (object) array(
                'vendorId' => $vid,
                'name' => $r->vendor_name,
                'ventasMes' => $ventas,
                'meta' => $meta,
                'pctMeta' => $pct,
                'ventasHoy' => $hoy,
                'metaDiaria' => $metaDiaria,
                'budgets' => $budgets,
                'invoices' => $invoices,
                'conversion' => $conversion,
                'cobros' => isset($cobrosByVendor[$vid]) ? $cobrosByVendor[$vid] : ((float)(isset($r->total_collected) ? $r->total_collected : 0)),
                'lastActivity' => $lastAct,
                'diasSinActividad' => $diasSinActividad,
                // Nuevos
                'clientsAssigned' => $assigned,
                'clientsActive' => $active,
                'tendencia' => $tendencia,
                'prevVentas' => $prevVentas,
                'proyeccion' => $proyeccion,
                'proyEstado' => $proyEstado,
                'avgHealth' => $avgHealth,
                'types' => $types,
            );
        }

        // Totales
        $totalVentas = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'ventasMes'));
        $totalMeta = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'meta'));
        $totalHoy = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'ventasHoy'));
        $cumplieron = count(array_filter($vendors, function($v){ return $v->pctMeta >= 100; }));
        $totalCobros = array_sum(array_column(array_map(function($v){ return (array)$v; }, $vendors), 'cobros'));

        // Metas colectivas por bodega
        // NOTA: company_goals.meta_ventas y meta_cobros son MENSUALES.
        // En modo YTD las multiplicamos por el numero de meses acumulados.
        $companyGoals = $this->db->get_where('company_goals', array('year' => $year))->result();
        $metaColectivaVentas = 0;
        $metaColectivaCobros = 0;
        foreach ($companyGoals as $cg) {
            if ($storeFilter === 'all' || $storeFilter == $cg->storeId) {
                $metaColectivaVentas += (float)$cg->meta_ventas;
                $metaColectivaCobros += (float)$cg->meta_cobros;
            }
        }
        $metaMultiplier = $isYtd ? $month : 1;
        $metaMensualVentas = $metaColectivaVentas * $metaMultiplier;
        $metaMensualCobros = $metaColectivaCobros * $metaMultiplier;
        $pctColectivoVentas = $metaMensualVentas > 0 ? round(($totalVentas / $metaMensualVentas) * 100, 1) : 0;
        $pctColectivoCobros = $metaMensualCobros > 0 ? round(($totalCobros / $metaMensualCobros) * 100, 1) : 0;

        // Clientes RFM por vendedor (para modal detalle)
        $vendorClientsJson = [];
        if (isset($rfmData) && !empty($rfmData)) {
            foreach ($rfmData as $c) {
                $v = $c->vendor;
                if (empty($v)) continue;
                if (!isset($vendorClientsJson[$v])) $vendorClientsJson[$v] = [];
                $vendorClientsJson[$v][] = [
                    'id' => $c->idClient, 'name' => $c->name, 'health' => $c->health_score,
                    'segment' => $c->rfm_segment, 'seg_color' => $c->segment_color,
                    'type' => $c->suggested_type, 'revenue' => (float)$c->total_revenue_12m,
                    'days' => (int)$c->days_since_last, 'r' => $c->r_score, 'f' => $c->f_score, 'm' => $c->m_score,
                ];
            }
            foreach ($vendorClientsJson as &$vc) usort($vc, function($a, $b) { return $b['revenue'] - $a['revenue']; });
            unset($vc);
        }

        // Ventas del periodo seleccionado por cliente y vendedor (para tab "Ventas" del modal)
        $this->db->select('invoices.vendorId, invoices.clientId, clients.name as client_name,
                           SUM(invoices.total - invoices.discount) as period_revenue,
                           SUM(invoices.payment) as period_cobros,
                           COUNT(invoices.idInvoice) as period_invoices,
                           MAX(invoices.date) as last_invoice_date')
            ->from('invoices')
            ->join('clients', 'clients.idClient = invoices.clientId', 'left')
            ->where('invoices.deleted', 0)
            ->where('invoices.date >=', $from)
            ->where('invoices.date <', $to);
        if ($storeFilter !== 'all') $this->db->where_in('invoices.storeId', $storeIds);
        $periodSalesRaw = $this->db->group_by('invoices.vendorId, invoices.clientId')
            ->order_by('period_revenue', 'DESC')
            ->get()->result();

        // Ventas del periodo anterior por cliente y vendedor (para comparativo)
        $this->db->select('invoices.vendorId, invoices.clientId, SUM(invoices.total - invoices.discount) as prev_revenue')
            ->from('invoices')
            ->where('invoices.deleted', 0)
            ->where('invoices.date >=', $prevFrom)
            ->where('invoices.date <', $prevTo);
        if ($storeFilter !== 'all') $this->db->where_in('invoices.storeId', $storeIds);
        $prevClientRaw = $this->db->group_by('invoices.vendorId, invoices.clientId')->get()->result();
        $prevClientMap = [];
        foreach ($prevClientRaw as $pc) {
            $prevClientMap[$pc->vendorId . '_' . $pc->clientId] = (float)$pc->prev_revenue;
        }

        $vendorPeriodClientsJson = [];
        foreach ($periodSalesRaw as $p) {
            $vid = $p->vendorId;
            if (!isset($vendorPeriodClientsJson[$vid])) $vendorPeriodClientsJson[$vid] = [];
            $prevRev = isset($prevClientMap[$vid . '_' . $p->clientId]) ? $prevClientMap[$vid . '_' . $p->clientId] : 0;
            $delta = $prevRev > 0 ? round((((float)$p->period_revenue - $prevRev) / $prevRev) * 100) : ((float)$p->period_revenue > 0 ? 100 : 0);
            $vendorPeriodClientsJson[$vid][] = [
                'id' => (int)$p->clientId,
                'name' => $p->client_name ?: '(sin nombre)',
                'revenue' => (float)$p->period_revenue,
                'cobros' => (float)$p->period_cobros,
                'invoices' => (int)$p->period_invoices,
                'last_date' => $p->last_invoice_date,
                'prev_revenue' => $prevRev,
                'delta' => $delta,
            ];
        }

        // Evolucion temporal: por DIA (mensual) o por MES (YTD)
        if ($isYtd) {
            $this->db->select('MONTH(date) as d, SUM(total - discount) as v')
                ->from('invoices')
                ->where('deleted', 0)
                ->where('YEAR(date)', $year);
            if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
            $dailyRaw = $this->db->group_by('MONTH(date)')->order_by('d', 'ASC')->get()->result();
            $dailySales = array_fill(1, 12, 0);
            foreach ($dailyRaw as $dr) $dailySales[(int)$dr->d] = (float)$dr->v;

            $this->db->select('MONTH(date) as d, SUM(payment) as v')
                ->from('payments')
                ->where('deleted', 0)
                ->where('YEAR(date)', $year);
            $dailyCobrosRaw = $this->db->group_by('MONTH(date)')->order_by('d', 'ASC')->get()->result();
            $dailyCobros = array_fill(1, 12, 0);
            foreach ($dailyCobrosRaw as $dc) $dailyCobros[(int)$dc->d] = (float)$dc->v;
        } else {
            $this->db->select('DAY(date) as d, SUM(total - discount) as v')
                ->from('invoices')
                ->where('deleted', 0)
                ->where('date >=', $from)
                ->where('date <', $to);
            if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
            $dailyRaw = $this->db->group_by('DAY(date)')->order_by('d', 'ASC')->get()->result();
            $dailySales = array_fill(1, $daysInMonth, 0);
            foreach ($dailyRaw as $dr) $dailySales[(int)$dr->d] = (float)$dr->v;

            $this->db->select('DAY(date) as d, SUM(payment) as v')
                ->from('payments')
                ->where('deleted', 0)
                ->where('date >=', $from)
                ->where('date <', $to);
            $dailyCobrosRaw = $this->db->group_by('DAY(date)')->order_by('d', 'ASC')->get()->result();
            $dailyCobros = array_fill(1, $daysInMonth, 0);
            foreach ($dailyCobrosRaw as $dc) $dailyCobros[(int)$dc->d] = (float)$dc->v;
        }

        // Ventas por tienda (para distribucion por bodega)
        $this->db->select('invoices.storeId, stores.name as store_name, SUM(invoices.total - invoices.discount) as v, COUNT(invoices.idInvoice) as cnt')
            ->from('invoices')
            ->join('stores', 'stores.idStore = invoices.storeId', 'left')
            ->where('invoices.deleted', 0)
            ->where('invoices.date >=', $from)
            ->where('invoices.date <', $to);
        if ($storeFilter !== 'all') $this->db->where_in('invoices.storeId', $storeIds);
        $storeBreakdown = $this->db->group_by('invoices.storeId')
            ->order_by('v', 'DESC')->get()->result();

        // Total ventas y cobros del periodo anterior para comparacion
        $this->db->select('SUM(total - discount) as v')
            ->from('invoices')
            ->where('deleted', 0)
            ->where('date >=', $prevFrom)
            ->where('date <', $prevTo);
        if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
        $prevTotalVentas = (float)($this->db->get()->row()->v ?? 0);

        $this->db->select('SUM(payment) as v')
            ->from('payments')
            ->where('deleted', 0)
            ->where('date >=', $prevFrom)
            ->where('date <', $prevTo);
        $prevTotalCobros = (float)($this->db->get()->row()->v ?? 0);

        // Pipeline: total presupuestos y conversion global
        $totalBudgets = array_sum($budgetsByVendor);
        $totalInvoices = array_sum($invoicesByVendor);
        $globalConv = $totalBudgets > 0 ? round(($totalInvoices / $totalBudgets) * 100) : 0;

        // Proyeccion global (ritmo diario actual x dias del mes)
        if ($isYtd) {
            $monthsElapsedG = $isCurrentMonth ? max(1, ($month - 1) + ($dayOfMonth / $daysInMonth)) : $month;
            $proyeccionGlobal = $monthsElapsedG > 0 ? round(($totalVentas / $monthsElapsedG) * $month) : 0;
        } else {
            $ritmoGlobal = $dayOfMonth > 0 ? $totalVentas / $dayOfMonth : 0;
            $proyeccionGlobal = round($ritmoGlobal * $daysInMonth);
        }

        // Ventas últimos 6 meses por vendedor (para sparkline)
        $vendorMonthly = [];
        for ($mi = 5; $mi >= 0; $mi--) {
            $mm = (int)date('n', strtotime("-{$mi} months"));
            $yy = (int)date('Y', strtotime("-{$mi} months"));
            $mFrom = sprintf('%04d-%02d-01', $yy, $mm);
            $mTo = date('Y-m-t', strtotime($mFrom)) . ' 23:59:59';
            $mRaw = $this->db->select('vendorId, SUM(total - discount) as t')
                ->from('invoices')->where('deleted', 0)->where('date >=', $mFrom)->where('date <=', $mTo);
            if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
            $mRaw = $this->db->group_by('vendorId')->get()->result();
            foreach ($mRaw as $mr) {
                if (!isset($vendorMonthly[$mr->vendorId])) $vendorMonthly[$mr->vendorId] = [];
                $vendorMonthly[$mr->vendorId][] = ['m' => sprintf('%02d/%d', $mm, $yy), 'v' => (float)$mr->t];
            }
        }

        $data = array(
            'vendors' => $vendors,
            'totalVentas' => $totalVentas,
            'totalMeta' => $totalMeta,
            'totalHoy' => $totalHoy,
            'cumplieron' => $cumplieron,
            'totalVendors' => count($vendors),
            'totalCobros' => $totalCobros,
            'metaMensualVentas' => $metaMensualVentas,
            'metaMensualCobros' => $metaMensualCobros,
            'pctColectivoVentas' => $pctColectivoVentas,
            'pctColectivoCobros' => $pctColectivoCobros,
            'companyGoals' => $companyGoals,
            'monthName' => $this->_getMonthName($month),
            'year' => $year,
            'month' => $month,
            'dayOfMonth' => $dayOfMonth,
            'daysInMonth' => $daysInMonth,
            'workDaysLeft' => $workDaysLeft,
            'isCurrentMonth' => $isCurrentMonth,
            'totalCobros' => $totalCobros,
            'tiendas' => $tiendas,
            'storeFilter' => $storeFilter,
            'vendorClientsJson' => json_encode($vendorClientsJson),
            'vendorMonthlyJson' => json_encode($vendorMonthly),
            'vendorPeriodClientsJson' => json_encode($vendorPeriodClientsJson),
            'dailySalesJson' => json_encode(array_values($dailySales)),
            'dailyCobrosJson' => json_encode(array_values($dailyCobros)),
            'storeBreakdown' => $storeBreakdown,
            'prevTotalVentas' => $prevTotalVentas,
            'prevTotalCobros' => $prevTotalCobros,
            'totalBudgets' => $totalBudgets,
            'totalInvoices' => $totalInvoices,
            'globalConv' => $globalConv,
            'proyeccionGlobal' => $proyeccionGlobal,
            'view' => $view,
            'isYtd' => $isYtd,
        );

        $this->load->view('sisvent/admin/salesboard/index', $data);
    }

    /**
     * AJAX: Analisis de brecha para un vendedor
     * Descompone el faltante a meta en oportunidades accionables:
     *  - Repetidores pendientes (compraron en 6m pero no este mes)
     *  - En riesgo RFM recuperables
     *  - Dormidos (90-365d)
     *  - Pipeline de presupuestos abiertos
     */
    public function gap()
    {
        $vendorId = (int) $this->input->get('vendor');
        $year = (int) ($this->input->get('year') ?: date('Y'));
        $month = (int) ($this->input->get('month') ?: date('n'));
        $storeFilter = $this->input->get('store') ?: 'all';
        $storeIds = ($storeFilter !== 'all') ? array((int)$storeFilter) : $this->settings_lib->get('stores_mde', self::STORES_MDE);

        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-d', strtotime(date('Y-m-t', strtotime($from)) . ' +1 day'));
        $sixMoAgo = date('Y-m-d', strtotime($from . ' -6 months'));

        // Meta y ventas actuales del vendedor (row_array -> acceso por clave)
        $goalRow = $this->invoice_vendor_service->getVendorSalesYearGoal($vendorId, $year);
        $meta = (!empty($goalRow) && isset($goalRow['m' . $month])) ? (int) $goalRow['m' . $month] : 0;

        $this->db->select('SUM(total - discount) as v')
            ->from('invoices')
            ->where('vendorId', $vendorId)->where('deleted', 0)
            ->where('date >=', $from)->where('date <', $to);
        if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
        $ventasMes = (float)($this->db->get()->row()->v ?? 0);
        $faltante = max(0, $meta - $ventasMes);

        // Historial 6 meses previos por cliente (excluye mes actual)
        $this->db->select("invoices.clientId, clients.name,
                           SUM(invoices.total - invoices.discount) as hist_total,
                           COUNT(DISTINCT DATE_FORMAT(invoices.date, '%Y-%m')) as months_bought,
                           COUNT(invoices.idInvoice) as invoice_count,
                           MAX(invoices.date) as last_date")
            ->from('invoices')
            ->join('clients', 'clients.idClient = invoices.clientId', 'left')
            ->where('invoices.vendorId', $vendorId)
            ->where('invoices.deleted', 0)
            ->where('invoices.date >=', $sixMoAgo)
            ->where('invoices.date <', $from);
        if ($storeFilter !== 'all') $this->db->where_in('invoices.storeId', $storeIds);
        $histRaw = $this->db->group_by('invoices.clientId')->get()->result();

        $histMap = [];
        foreach ($histRaw as $h) {
            $histMap[$h->clientId] = [
                'name' => $h->name ?: '(sin nombre)',
                'avg_monthly' => $h->months_bought > 0 ? ((float)$h->hist_total / (float)$h->months_bought) : 0,
                'total_6m' => (float)$h->hist_total,
                'months_bought' => (int)$h->months_bought,
                'invoice_count' => (int)$h->invoice_count,
                'last_date' => $h->last_date,
                'days_since' => $h->last_date ? (int)((time() - strtotime($h->last_date)) / 86400) : 999,
            ];
        }

        // Ventas del mes actual por cliente
        $this->db->select('clientId, SUM(total - discount) as v')
            ->from('invoices')
            ->where('vendorId', $vendorId)->where('deleted', 0)
            ->where('date >=', $from)->where('date <', $to);
        if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
        $monthRaw = $this->db->group_by('clientId')->get()->result();
        $monthMap = [];
        foreach ($monthRaw as $m) $monthMap[$m->clientId] = (float)$m->v;

        // Buckets
        $repetidores = [];     // compraron en 2+ meses previos, 0 este mes
        $hibernando = [];      // dias_desde > 90 (considerados dormidos)

        foreach ($histMap as $cid => $d) {
            $thisMonth = isset($monthMap[$cid]) ? $monthMap[$cid] : 0;
            if ($thisMonth > 0) continue; // Ya compro este mes, no va a brecha

            $base = [
                'id' => (int)$cid,
                'name' => $d['name'],
                'avg_monthly' => $d['avg_monthly'],
                'months_bought' => $d['months_bought'],
                'last_date' => $d['last_date'],
                'days_since' => $d['days_since'],
            ];

            if ($d['days_since'] > 90 && $d['days_since'] < 365) {
                $base['opportunity'] = round($d['avg_monthly'] * 0.5); // Conservador: 50% del promedio
                $hibernando[] = $base;
            } elseif ($d['months_bought'] >= 2) {
                $base['opportunity'] = round($d['avg_monthly']);
                $repetidores[] = $base;
            }
        }

        // Clientes en riesgo via RFM
        $enRiesgo = [];
        if (file_exists(APPPATH . 'models/Crm_model.php')) {
            $this->load->model('crm_model');
            $rfmData = $this->crm_model->calculateRfmScores(
                $storeFilter !== 'all' ? array('store_id' => $storeFilter) : array()
            );
            $riskySegments = ['En Riesgo', 'A Punto de Perder', 'No Perder', 'Necesita Atencion'];
            foreach ($rfmData as $c) {
                if ($c->vendor != $vendorId) continue;
                if (!in_array($c->rfm_segment, $riskySegments)) continue;
                if (isset($monthMap[$c->idClient]) && $monthMap[$c->idClient] > 0) continue;
                $avgM = isset($histMap[$c->idClient]) ? $histMap[$c->idClient]['avg_monthly'] : ((float)$c->total_revenue_12m / 12);
                $enRiesgo[] = [
                    'id' => (int)$c->idClient,
                    'name' => $c->name,
                    'segment' => $c->rfm_segment,
                    'seg_color' => $c->segment_color,
                    'health' => (int)$c->health_score,
                    'days_since' => (int)$c->days_since_last,
                    'avg_monthly' => $avgM,
                    'opportunity' => round($avgM * 0.7), // 70% - rescate parcial
                ];
            }
            // Quitar de repetidores/hibernando los que ya estan en enRiesgo
            $riskIds = array_column($enRiesgo, 'id');
            $repetidores = array_values(array_filter($repetidores, function($c) use ($riskIds) { return !in_array($c['id'], $riskIds); }));
            $hibernando = array_values(array_filter($hibernando, function($c) use ($riskIds) { return !in_array($c['id'], $riskIds); }));
        }

        // Ordenar por oportunidad
        usort($repetidores, function($a, $b) { return $b['opportunity'] - $a['opportunity']; });
        usort($enRiesgo, function($a, $b) { return $b['opportunity'] - $a['opportunity']; });
        usort($hibernando, function($a, $b) { return $b['opportunity'] - $a['opportunity']; });

        // Pipeline: presupuestos abiertos del vendedor (state=0, no facturados)
        $this->db->select('COUNT(*) as cnt, COALESCE(SUM(total),0) as val')
            ->from('budgets')
            ->where('vendorId', $vendorId)
            ->where('deleted', 0)
            ->where('state', 0)
            ->where('budget_type', 'venta');
        if ($storeFilter !== 'all') $this->db->where_in('storeId', $storeIds);
        $pipRow = $this->db->get()->row();
        $pipeline = [
            'count' => (int)($pipRow->cnt ?? 0),
            'value' => (float)($pipRow->val ?? 0),
        ];

        // Totales de oportunidad
        $oppRep = array_sum(array_column($repetidores, 'opportunity'));
        $oppRisk = array_sum(array_column($enRiesgo, 'opportunity'));
        $oppHib = array_sum(array_column($hibernando, 'opportunity'));
        $oppPip = $pipeline['value'] * 0.4; // Conversion tipica 40%
        $oppTotal = $oppRep + $oppRisk + $oppHib + $oppPip;
        $coverage = $faltante > 0 ? round(($oppTotal / $faltante) * 100) : 0;

        header('Content-Type: application/json');
        echo json_encode([
            'meta' => $meta,
            'ventasMes' => $ventasMes,
            'faltante' => $faltante,
            'coverage' => $coverage,
            'oppTotal' => $oppTotal,
            'buckets' => [
                'repetidores' => [
                    'count' => count($repetidores),
                    'opportunity' => $oppRep,
                    'clients' => array_slice($repetidores, 0, 20),
                ],
                'enRiesgo' => [
                    'count' => count($enRiesgo),
                    'opportunity' => $oppRisk,
                    'clients' => array_slice($enRiesgo, 0, 20),
                ],
                'hibernando' => [
                    'count' => count($hibernando),
                    'opportunity' => $oppHib,
                    'clients' => array_slice($hibernando, 0, 20),
                ],
                'pipeline' => [
                    'count' => $pipeline['count'],
                    'value' => $pipeline['value'],
                    'opportunity' => $oppPip,
                ],
            ],
        ]);
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

        $storeIds = ($storeFilter !== 'all') ? array((int)$storeFilter) : $this->settings_lib->get('stores_mde', self::STORES_MDE);

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
        $goalsRaw = $this->invoice_reports_service->getAllVendorsGoals($year);
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
        $this->invoice_vendor_service->saveVendorSalesGoal($data);

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
            $existing = $this->invoice_vendor_service->getVendorSalesYearGoal($v->idUser, $year);
            $data = array('userId' => $v->idUser, 'year' => $year);
            for ($m = 1; $m <= 12; $m++) {
                if ($m >= $fromMonth) {
                    $data['m'.$m] = $value;
                } else {
                    $data['m'.$m] = ($existing && isset($existing->{'m'.$m})) ? (int)$existing->{'m'.$m} : 0;
                }
            }
            $this->invoice_vendor_service->saveVendorSalesGoal($data);
        }

        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'count' => count($vendedores)));
    }

    /**
     * AJAX: Guardar meta colectiva
     */
    public function saveCompanyGoal()
    {
        $storeId = (int) $this->input->post('storeId');
        $year = (int) $this->input->post('year');
        $metaVentas = (float) $this->input->post('meta_ventas') * 1000000;
        $metaCobros = (float) $this->input->post('meta_cobros') * 1000000;
        $user = $this->session->userdata('user_data')['uname'];

        $exists = $this->db->get_where('company_goals', array('storeId' => $storeId, 'year' => $year))->row();
        if ($exists) {
            $this->db->update('company_goals', array(
                'meta_ventas' => $metaVentas,
                'meta_cobros' => $metaCobros,
                'updated_by' => $user,
                'updated_at' => date('Y-m-d H:i:s')
            ), array('id' => $exists->id));
        } else {
            $this->db->insert('company_goals', array(
                'storeId' => $storeId,
                'year' => $year,
                'meta_ventas' => $metaVentas,
                'meta_cobros' => $metaCobros,
                'updated_by' => $user,
                'updated_at' => date('Y-m-d H:i:s')
            ));
        }

        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
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
