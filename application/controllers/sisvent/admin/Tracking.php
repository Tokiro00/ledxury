<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tracking extends CI_Controller {

    // Meta ventas y recaudo MDE mensual
    const META_VENTAS  = 500000000;
    const META_RECAUDO = 500000000;
    // Margen bruto fijo
    const MARGEN_BRUTO = 0.527;
    // Tiendas MDE para ventas
    const STORES_MDE = [1, 3, 5];
    // Tiendas para inventario
    const STORES_INV = [1, 8];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('tracking_model');
        $this->load->model('invoices_model');
        $this->load->model('vendors_model');
    }

    // ================================================================
    // HELPERS
    // ================================================================

    /**
     * Obtener el lunes de la semana N del mes
     */
    private function getMondayOfWeek($year, $month, $week)
    {
        // Primer dia del mes
        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $firstDayOfWeek = date('N', strtotime($firstDay)); // 1=lun, 7=dom

        // Primer lunes del mes
        if ($firstDayOfWeek == 1) {
            $firstMonday = $firstDay;
        } else {
            $firstMonday = date('Y-m-d', strtotime($firstDay . ' +' . (8 - $firstDayOfWeek) . ' days'));
        }

        // Si la semana 1 empieza antes del primer lunes, usar el dia 1
        if ($week == 1) {
            return $firstDay;
        }

        // Lunes de la semana N
        $monday = date('Y-m-d', strtotime($firstMonday . ' +' . (($week - 2) * 7) . ' days'));

        // Si el lunes calculado esta antes del primer lunes, ajustar
        if ($week == 2) {
            $monday = $firstMonday;
        } elseif ($week > 2) {
            $monday = date('Y-m-d', strtotime($firstMonday . ' +' . (($week - 2) * 7) . ' days'));
        }

        // No pasar del ultimo dia del mes
        $lastDay = date('Y-m-t', strtotime($firstDay));
        if ($monday > $lastDay) {
            $monday = $lastDay;
        }

        return $monday;
    }

    /**
     * Calcular numero de semanas en el mes
     */
    private function getWeeksInMonth($year, $month)
    {
        $lastDay = date('j', strtotime(sprintf('%04d-%02d-01', $year, $month) . ' last day of this month'));
        return (int) ceil($lastDay / 7);
    }

    /**
     * Semana actual del mes
     */
    private function getCurrentWeek()
    {
        return (int) ceil(date('j') / 7);
    }

    /**
     * Obtener metas de vendedores del sales_goal o hardcoded
     */
    private function getVendorGoals($year)
    {
        $goals = $this->invoices_model->getAllVendorsGoals($year);
        $map = array();
        foreach ($goals as $g) {
            $map[$g->userId] = isset($g->goal) ? (int) $g->goal : 0;
        }
        return $map;
    }

    /**
     * Nombres de meses en espanol
     */
    private function getMonthName($m)
    {
        $names = array(
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        );
        return isset($names[$m]) ? $names[$m] : '';
    }

    // ================================================================
    // 1. SEGUIMIENTO SEMANAL
    // ================================================================

    public function semanal()
    {
        $this->backend_lib->control([1, 2]);

        $year  = $this->input->get('year')  ? (int) $this->input->get('year')  : (int) date('Y');
        $month = $this->input->get('month') ? (int) $this->input->get('month') : (int) date('n');
        $storeFilter = $this->input->get('store') ? $this->input->get('store') : '-1';

        // Determinar store IDs
        if ($storeFilter == '-1') {
            $storeIds = null;
        } else {
            $storeIds = [(int)$storeFilter];
        }

        // Vendedores activos (filtrar por tienda si es específica)
        if ($storeIds && count($storeIds) == 1) {
            $vendors = $this->vendors_model->getVendors([$storeIds[0]]);
        } else {
            $vendors = $this->vendors_model->getVendors();
        }

        // Stores para el filtro
        $this->load->model('stores_model');
        $stores = $this->stores_model->getStores();

        // Metas por vendedor
        $goals = $this->getVendorGoals($year);

        // Ventas del mes completo
        $ventasMes = $this->tracking_model->getLiveVentasMes($year, $month, $storeIds);
        $ventasMesMap = array();
        foreach ($ventasMes as $v) {
            $ventasMesMap[$v->vendorId] = (int) $v->total_ventas;
        }

        // Cobros del mes completo
        $cobrosMes = $this->tracking_model->getLiveCobrosMes($year, $month);
        $cobrosMesMap = array();
        foreach ($cobrosMes as $c) {
            $cobrosMesMap[$c->vendorId] = (int) $c->total_cobros;
        }

        // KPIs globales
        $totalVentasAcum = array_sum(array_values($ventasMesMap));
        $totalCobrosAcum = array_sum(array_values($cobrosMesMap));
        $totalMeta = 0;
        $cartera = $this->tracking_model->getCarteraPendiente();
        $inventario = $this->tracking_model->getInventarioValorizado(self::STORES_INV);

        // Dias del mes transcurridos para proyeccion
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $daysPassed = ($year == date('Y') && $month == date('n')) ? (int) date('j') : $daysInMonth;
        $proyeccion = ($daysPassed > 0) ? round(($totalVentasAcum / $daysPassed) * $daysInMonth) : 0;

        // % cobro
        $pctCobro = ($totalVentasAcum > 0) ? round(($totalCobrosAcum / $totalVentasAcum) * 100, 1) : 0;

        // Construir array de vendedores - solo los que tienen ventas o meta
        $vendorRows = array();
        foreach ($vendors as $v) {
            $vid = $v->idUser;
            $metaIndiv = isset($goals[$vid]) ? (int) $goals[$vid] : 0;
            $ventasAcum = isset($ventasMesMap[$vid]) ? $ventasMesMap[$vid] : 0;
            $cobrosAcum = isset($cobrosMesMap[$vid]) ? $cobrosMesMap[$vid] : 0;

            // Solo mostrar vendedores con ventas o con meta asignada
            if ($ventasAcum == 0 && $cobrosAcum == 0 && $metaIndiv == 0) continue;

            $pctVenta = ($metaIndiv > 0) ? round(($ventasAcum / $metaIndiv) * 100, 1) : 0;
            $pctCobroInd = ($ventasAcum > 0) ? round(($cobrosAcum / $ventasAcum) * 100, 1) : 0;
            $proj = ($daysPassed > 0) ? round(($ventasAcum / $daysPassed) * $daysInMonth) : 0;
            $totalMeta += $metaIndiv;

            $vendorRows[] = array(
                'vendorId'    => $vid,
                'name'        => $v->name,
                'ventas'      => $ventasAcum,
                'cobros'      => $cobrosAcum,
                'meta'        => $metaIndiv,
                'pctMeta'     => $pctVenta,
                'pctCobro'    => $pctCobroInd,
                'proyeccion'  => $proj,
                'semaforo'    => $pctVenta >= 100 ? 'green' : ($pctVenta >= 60 ? 'yellow' : 'red')
            );
        }

        // Ordenar por ventas DESC
        usort($vendorRows, function ($a, $b) {
            return $b['ventas'] - $a['ventas'];
        });

        $pctMetaGlobal = ($totalMeta > 0) ? round(($totalVentasAcum / $totalMeta) * 100, 1) : 0;

        $data = array(
            'year'            => $year,
            'month'           => $month,
            'vendorRows'      => $vendorRows,
            'totalVentas'     => $totalVentasAcum,
            'totalCobros'     => $totalCobrosAcum,
            'totalMeta'       => $totalMeta,
            'pctMeta'         => $pctMetaGlobal,
            'pctCobro'        => $pctCobro,
            'proyeccion'      => $proyeccion,
            'cartera'         => $cartera,
            'inventario'      => $inventario,
            'daysPassed'      => $daysPassed,
            'daysInMonth'     => $daysInMonth,
            'monthName'       => $this->getMonthName($month),
            'stores'          => $stores,
            'storeFilter'     => $storeFilter
        );

        $this->load->view('sisvent/admin/tracking/semanal', $data);
    }

    // ================================================================
    // 2. GUARDAR SNAPSHOT (AJAX)
    // ================================================================

    public function guardarSnapshot()
    {
        $this->backend_lib->control([1, 2]);

        if (!$this->input->is_ajax_request()) {
            show_error('Acceso no permitido', 403);
            return;
        }

        $year  = (int) $this->input->post('year');
        $month = (int) $this->input->post('month');
        $week  = (int) $this->input->post('week');

        $vendorIds = $this->input->post('vendorId');
        $ventas    = $this->input->post('ventas');
        $cobros    = $this->input->post('cobros');

        $vendorData = array();
        if (is_array($vendorIds)) {
            for ($i = 0; $i < count($vendorIds); $i++) {
                $vendorData[] = array(
                    'vendorId' => $vendorIds[$i],
                    'ventas'   => isset($ventas[$i]) ? (int) $ventas[$i] : 0,
                    'cobros'   => isset($cobros[$i]) ? (int) $cobros[$i] : 0
                );
            }
        }

        $extras = array(
            'cartera_total' => (int) $this->input->post('cartera_total'),
            'inventario'    => (int) $this->input->post('inventario'),
            'gastos_semana' => (int) $this->input->post('gastos_semana'),
            'notas'         => $this->input->post('notas')
        );

        $result = $this->tracking_model->saveWeeklySnapshot($year, $month, $week, $vendorData, $extras);

        header('Content-Type: application/json');
        echo json_encode(array('success' => $result, 'message' => 'Snapshot guardado correctamente'));
    }

    // ================================================================
    // 3. CIERRE MENSUAL
    // ================================================================

    public function cierre()
    {
        $this->backend_lib->control([1, 2]);

        $year  = $this->input->get('year')  ? (int) $this->input->get('year')  : (int) date('Y');
        $month = $this->input->get('month') ? (int) $this->input->get('month') : (int) date('n');

        // Cargar cierre existente
        $cierre = $this->tracking_model->getCierre($year, $month);

        // Datos live
        $ventasBrutas  = $this->tracking_model->getLiveVentasBrutasMes($year, $month, self::STORES_MDE);
        $cobrosClientes = $this->tracking_model->getLiveCobrosBrutosMes($year, $month);
        $cartera       = $this->tracking_model->getCarteraPendiente();
        $inventario    = $this->tracking_model->getInventarioValorizado(self::STORES_INV);

        $data = array(
            'year'           => $year,
            'month'          => $month,
            'monthName'      => $this->getMonthName($month),
            'cierre'         => $cierre,
            'ventasBrutas'   => $ventasBrutas,
            'cobrosClientes' => $cobrosClientes,
            'cartera'        => $cartera,
            'inventario'     => $inventario,
            'metaVentas'     => self::META_VENTAS,
            'metaRecaudo'    => self::META_RECAUDO,
            'margenBruto'    => self::MARGEN_BRUTO
        );

        $this->load->view('sisvent/admin/tracking/cierre', $data);
    }

    // ================================================================
    // 4. GUARDAR CIERRE
    // ================================================================

    public function guardarCierre()
    {
        $this->backend_lib->control([1, 2]);

        $year  = (int) $this->input->post('year');
        $month = (int) $this->input->post('month');

        $fields = array(
            'ventas_brutas', 'desc_pp', 'sueldos_adm', 'sueldo_vend', 'seg_social',
            'beneficios', 'comisiones', 'arriendo', 'reparacion', 'viaticos',
            'equipos', 'fletes', 'legales', 'impuestos', 'intereses', 'castigo',
            'otros_gastos', 'cobros_clientes', 'antic_baq', 'pago_china',
            'prov_nacionales', 'prestamo_empl', 'retiro_accionistas', 'pago_baq',
            'mov_bancarios', 'cartera_total', 'inventario', 'caja_bancos'
        );

        $data = array('year' => $year, 'month' => $month);
        foreach ($fields as $f) {
            $data[$f] = (int) $this->input->post($f);
        }

        // Calculos automaticos
        $ventasNetas   = $data['ventas_brutas'] - $data['desc_pp'];
        $utilidadBruta = round($ventasNetas * self::MARGEN_BRUTO);

        $totalGastos = $data['sueldos_adm'] + $data['sueldo_vend'] + $data['seg_social']
                     + $data['beneficios'] + $data['comisiones'] + $data['arriendo']
                     + $data['reparacion'] + $data['viaticos'] + $data['equipos']
                     + $data['fletes'] + $data['legales'] + $data['impuestos']
                     + $data['intereses'] + $data['castigo'] + $data['otros_gastos'];

        $utilidadOp = $utilidadBruta - $totalGastos;
        $data['utilidad_operativa'] = $utilidadOp;

        // Bonos
        $bonoVentas  = ($data['ventas_brutas'] >= self::META_VENTAS && $utilidadOp > 0)
                     ? round($data['ventas_brutas'] * 0.02) : 0;
        $bonoRecaudo = ($data['cobros_clientes'] >= self::META_RECAUDO && $utilidadOp > 0)
                     ? round($data['cobros_clientes'] * 0.02) : 0;

        $data['bono_ventas']  = $bonoVentas;
        $data['bono_recaudo'] = $bonoRecaudo;

        $this->tracking_model->saveCierre($data);

        $this->session->set_flashdata('tracking_success', 'Cierre mensual guardado correctamente');
        redirect('sisvent/admin/tracking/cierre?year=' . $year . '&month=' . $month);
    }

    // ================================================================
    // 5. ACUMULADO ANUAL
    // ================================================================

    public function acumulado()
    {
        $this->backend_lib->control([1, 2]);

        $year = $this->input->get('year') ? (int) $this->input->get('year') : (int) date('Y');

        $cierres = $this->tracking_model->getCierresYear($year);

        // Indexar por mes
        $cierresPorMes = array();
        foreach ($cierres as $c) {
            $cierresPorMes[$c->month] = $c;
        }

        $data = array(
            'year'          => $year,
            'cierresPorMes' => $cierresPorMes,
            'metaVentas'    => self::META_VENTAS,
            'metaRecaudo'   => self::META_RECAUDO,
            'margenBruto'   => self::MARGEN_BRUTO
        );

        $this->load->view('sisvent/admin/tracking/acumulado', $data);
    }

    // ================================================================
    // 6. MI DESEMPENO (accesible por todos los roles)
    // ================================================================

    public function miDesempeno()
    {
        $this->backend_lib->control();

        $userData = $this->session->userdata('user_data');
        $vendorId = $userData['uname'];
        $role     = $userData['role'];

        $year  = (int) date('Y');
        $month = (int) date('n');
        $week  = $this->getCurrentWeek();

        // Ventas acumuladas del mes
        $ventasAcum = $this->tracking_model->getLiveVentasVendorMes($vendorId, $year, $month, self::STORES_MDE);

        // Meta del vendedor
        $goals = $this->getVendorGoals($year);
        $metaIndiv = isset($goals[$vendorId]) ? (int) $goals[$vendorId] : 0;

        $pctMeta = ($metaIndiv > 0) ? round(($ventasAcum / $metaIndiv) * 100, 1) : 0;
        $proyeccion = ($week > 0) ? round(($ventasAcum / $week) * 4.33) : 0;

        // Ranking
        $ranking = $this->tracking_model->getVendorRanking($year, $month, self::STORES_MDE);
        $position = 0;
        $totalVendors = count($ranking);
        foreach ($ranking as $idx => $r) {
            if ($r->vendorId == $vendorId) {
                $position = $idx + 1;
                break;
            }
        }
        if ($position == 0 && $totalVendors > 0) {
            $position = $totalVendors + 1;
            $totalVendors = $totalVendors + 1;
        }

        // Ultimos 3 meses
        $history = array();
        for ($i = 2; $i >= 0; $i--) {
            $hMonth = $month - $i;
            $hYear  = $year;
            if ($hMonth <= 0) {
                $hMonth += 12;
                $hYear--;
            }
            $hVentas = $this->tracking_model->getLiveVentasVendorMes($vendorId, $hYear, $hMonth, self::STORES_MDE);
            $history[] = array(
                'year'      => $hYear,
                'month'     => $hMonth,
                'monthName' => $this->getMonthName($hMonth),
                'ventas'    => $hVentas,
                'meta'      => $metaIndiv
            );
        }

        // Nombre del vendedor
        $vendorInfo = $this->db->select('name')->from('users')->where('idUser', $vendorId)->get()->row();

        $data = array(
            'vendorName'   => $vendorInfo ? $vendorInfo->name : 'Vendedor',
            'ventasAcum'   => $ventasAcum,
            'metaIndiv'    => $metaIndiv,
            'pctMeta'      => $pctMeta,
            'proyeccion'   => $proyeccion,
            'position'     => $position,
            'totalVendors' => $totalVendors,
            'history'      => $history,
            'monthName'    => $this->getMonthName($month),
            'year'         => $year,
            'month'        => $month
        );

        $this->load->view('sisvent/admin/tracking/mi_desempeno', $data);
    }
}
