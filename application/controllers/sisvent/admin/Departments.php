<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Departments extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('departamentos');
        $this->load->model('departments_model');
        $this->load->model('users_model');
        $this->load->model('stores_model');
        $this->load->model('invoices_model');
        $this->load->model('inventory_model');
        $this->load->model('vendors_model');
    }

    // ========================================================================
    // LISTADO
    // ========================================================================

    public function index()
    {
        $storeFilter = $this->input->get('store');
        $departments = $this->departments_model->getDepartments($storeFilter);

        // Calcular KPIs promedio por departamento
        foreach ($departments as &$dept) {
            $kpis = $this->departments_model->getKpisByDepartment($dept->id);
            $dept->kpi_count = count($kpis);
            $dept->avg_compliance = 0;

            if ($dept->kpi_count > 0) {
                $totalCompliance = 0;
                $totalWeight = 0;
                foreach ($kpis as $kpi) {
                    $compliance = $this->_calculateCompliance($kpi);
                    $totalCompliance += $compliance * $kpi->weight;
                    $totalWeight += $kpi->weight;
                }
                $dept->avg_compliance = $totalWeight > 0 ? round($totalCompliance / $totalWeight, 1) : 0;
            }
        }

        $data = array(
            'departments' => $departments,
            'stores'      => $this->stores_model->getStores(),
            'storeFilter' => $storeFilter
        );

        $this->load->view('sisvent/admin/departments/index', $data);
    }

    // ========================================================================
    // CREAR
    // ========================================================================

    public function add()
    {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->form_validation->set_rules('name', 'Nombre', 'required');
            $this->form_validation->set_rules('budget', 'Presupuesto', 'required|numeric');

            if ($this->form_validation->run()) {
                $data = array(
                    'name'           => $this->input->post('name'),
                    'description'    => $this->input->post('description'),
                    'leader_user_id' => $this->input->post('leader_user_id') ?: null,
                    'store_id'       => $this->input->post('store_id') ?: null,
                    'budget'         => (float)$this->input->post('budget'),
                    'sort_order'     => (int)$this->input->post('sort_order'),
                    'active'         => 1
                );

                $this->departments_model->save($data);
                $this->session->set_flashdata('success', 'Departamento creado exitosamente');
                redirect(base_url() . 'sisvent/admin/departments');
                return;
            }
        }

        $data = array(
            'users'  => $this->users_model->getUsers(false),
            'stores' => $this->stores_model->getStores()
        );

        $this->load->view('sisvent/admin/departments/add', $data);
    }

    // ========================================================================
    // EDITAR
    // ========================================================================

    public function edit($id)
    {
        $department = $this->departments_model->getDepartment($id);
        if (!$department) {
            $this->session->set_flashdata('error', 'Departamento no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->form_validation->set_rules('name', 'Nombre', 'required');
            $this->form_validation->set_rules('budget', 'Presupuesto', 'required|numeric');

            if ($this->form_validation->run()) {
                $data = array(
                    'name'           => $this->input->post('name'),
                    'description'    => $this->input->post('description'),
                    'leader_user_id' => $this->input->post('leader_user_id') ?: null,
                    'store_id'       => $this->input->post('store_id') ?: null,
                    'budget'         => (float)$this->input->post('budget'),
                    'sort_order'     => (int)$this->input->post('sort_order')
                );

                $this->departments_model->update($id, $data);
                $this->session->set_flashdata('success', 'Departamento actualizado exitosamente');
                redirect(base_url() . 'sisvent/admin/departments');
                return;
            }
        }

        $data = array(
            'department' => $department,
            'users'      => $this->users_model->getUsers(false),
            'stores'     => $this->stores_model->getStores()
        );

        $this->load->view('sisvent/admin/departments/edit', $data);
    }

    // ========================================================================
    // ELIMINAR (soft delete)
    // ========================================================================

    public function remove($id)
    {
        $department = $this->departments_model->getDepartment($id);
        if (!$department) {
            $this->session->set_flashdata('error', 'Departamento no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        $this->departments_model->remove($id);
        $this->session->set_flashdata('success', 'Departamento eliminado exitosamente');
        redirect(base_url() . 'sisvent/admin/departments');
    }

    // ========================================================================
    // DETALLE
    // ========================================================================

    public function view($id)
    {
        $department = $this->departments_model->getDepartment($id);
        if (!$department) {
            $this->session->set_flashdata('error', 'Departamento no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        $kpis = $this->departments_model->getKpisByDepartment($id);

        // Calcular cumplimiento para cada KPI
        foreach ($kpis as &$kpi) {
            $kpi->compliance = $this->_calculateCompliance($kpi);
        }

        // Obtener ultimas bonificaciones
        $bonuses = $this->departments_model->getBonusesByDepartment($id);

        // Obtener vendedores con metas y ventas reales
        $goalYear = $this->input->get('goal_year') ? (int)$this->input->get('goal_year') : (int)date('Y');
        $activeVendors = $this->vendors_model->getVendors();
        $vendors = array();

        foreach ($activeVendors as $vendor) {
            $goalData = $this->invoices_model->getVendorSalesYearGoal($vendor->idUser, $goalYear);
            $salesData = $this->departments_model->getVendorMonthlySales($vendor->idUser, $goalYear);

            $goals = array();
            $sales = array();
            for ($m = 1; $m <= 12; $m++) {
                $key = 'm' . $m;
                $goals[$key] = isset($goalData[$key]) ? (float)$goalData[$key] : 0;
                $sales[$key] = isset($salesData[$m]) ? (float)$salesData[$m] : 0;
            }

            $vendorObj = new stdClass();
            $vendorObj->idUser = $vendor->idUser;
            $vendorObj->name = $vendor->name;
            $vendorObj->store = isset($vendor->store_name) ? $vendor->store_name : '-';
            $vendorObj->goals = $goals;
            $vendorObj->sales = $sales;
            $vendors[] = $vendorObj;
        }

        $data = array(
            'department' => $department,
            'kpis'       => $kpis,
            'bonuses'    => $bonuses,
            'vendors'    => $vendors,
            'goalYear'   => $goalYear
        );

        $this->load->view('sisvent/admin/departments/view', $data);
    }

    // ========================================================================
    // KPIs - CREAR
    // ========================================================================

    public function addKpi($deptId)
    {
        $department = $this->departments_model->getDepartment($deptId);
        if (!$department) {
            $this->session->set_flashdata('error', 'Departamento no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->form_validation->set_rules('name', 'Nombre', 'required');
            $this->form_validation->set_rules('target_value', 'Meta', 'required|numeric');
            $this->form_validation->set_rules('weight', 'Peso', 'required|numeric');

            if ($this->form_validation->run()) {
                $data = array(
                    'department_id' => $deptId,
                    'name'          => $this->input->post('name'),
                    'description'   => $this->input->post('description'),
                    'target_value'  => (float)$this->input->post('target_value'),
                    'current_value' => 0,
                    'unit'          => $this->input->post('unit'),
                    'direction'     => $this->input->post('direction'),
                    'weight'        => (float)$this->input->post('weight'),
                    'sort_order'    => (int)$this->input->post('sort_order'),
                    'active'        => 1
                );

                $this->departments_model->saveKpi($data);
                $this->session->set_flashdata('success', 'KPI creado exitosamente');
                redirect(base_url() . 'sisvent/admin/departments/view/' . $deptId);
                return;
            }
        }

        $data = array(
            'department' => $department
        );

        $this->load->view('sisvent/admin/departments/addkpi', $data);
    }

    // ========================================================================
    // KPIs - EDITAR
    // ========================================================================

    public function editKpi($id)
    {
        $kpi = $this->departments_model->getKpi($id);
        if (!$kpi) {
            $this->session->set_flashdata('error', 'KPI no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        $department = $this->departments_model->getDepartment($kpi->department_id);

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->form_validation->set_rules('name', 'Nombre', 'required');
            $this->form_validation->set_rules('target_value', 'Meta', 'required|numeric');
            $this->form_validation->set_rules('weight', 'Peso', 'required|numeric');

            if ($this->form_validation->run()) {
                $data = array(
                    'name'         => $this->input->post('name'),
                    'description'  => $this->input->post('description'),
                    'target_value' => (float)$this->input->post('target_value'),
                    'unit'         => $this->input->post('unit'),
                    'direction'    => $this->input->post('direction'),
                    'weight'       => (float)$this->input->post('weight'),
                    'sort_order'   => (int)$this->input->post('sort_order')
                );

                $this->departments_model->updateKpi($id, $data);
                $this->session->set_flashdata('success', 'KPI actualizado exitosamente');
                redirect(base_url() . 'sisvent/admin/departments/view/' . $kpi->department_id);
                return;
            }
        }

        $data = array(
            'department' => $department,
            'kpi'        => $kpi
        );

        $this->load->view('sisvent/admin/departments/editkpi', $data);
    }

    // ========================================================================
    // KPIs - ELIMINAR
    // ========================================================================

    public function removeKpi($id)
    {
        $kpi = $this->departments_model->getKpi($id);
        if (!$kpi) {
            $this->session->set_flashdata('error', 'KPI no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        $deptId = $kpi->department_id;
        $this->departments_model->removeKpi($id);
        $this->session->set_flashdata('success', 'KPI eliminado exitosamente');
        redirect(base_url() . 'sisvent/admin/departments/view/' . $deptId);
    }

    // ========================================================================
    // CALCULAR KPIs (auto-calculo desde datos reales)
    // ========================================================================

    public function calculateKpis($deptId)
    {
        $department = $this->departments_model->getDepartment($deptId);
        if (!$department) {
            $this->session->set_flashdata('error', 'Departamento no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        $kpis = $this->departments_model->getKpisByDepartment($deptId);
        $storeId = $department->store_id;
        $updated = 0;

        foreach ($kpis as $kpi) {
            $nameLower = mb_strtolower($kpi->name);
            $newValue = null;

            // --- Descuento promedio / ventas ---
            if (strpos($nameLower, 'descuento') !== false) {
                $newValue = $this->departments_model->getDiscountRateCurrentMonth($storeId);
            }
            // --- Reactivacion clientes dormidos ---
            elseif (strpos($nameLower, 'dormido') !== false || strpos($nameLower, 'reactivacion') !== false) {
                $newValue = $this->departments_model->getReactivatedClientsCurrentMonth($storeId);
            }
            // --- Nuevos clientes activos ---
            elseif (strpos($nameLower, 'nuevos cliente') !== false) {
                $newValue = $this->departments_model->getNewActiveClientsCurrentMonth($storeId);
            }
            // --- DSO consolidado ---
            elseif (strpos($nameLower, 'dso') !== false) {
                $newValue = $this->departments_model->getDSOConsolidado($storeId);
            }
            // --- Recuperacion cartera >180d ---
            elseif (strpos($nameLower, 'recuperacion') !== false && strpos($nameLower, '180') !== false) {
                $newValue = $this->departments_model->getRecoveryOver180Days($storeId);
            }
            // --- Cartera > 90 dias ---
            elseif (strpos($nameLower, 'cartera') !== false && strpos($nameLower, '90') !== false) {
                $newValue = $this->departments_model->getReceivablesOver90Days($storeId);
            }
            // --- Margen neto ---
            elseif (strpos($nameLower, 'margen neto') !== false) {
                $newValue = $this->departments_model->getNetMarginCurrentMonth($storeId);
            }
            // --- Margen bruto % ---
            elseif (strpos($nameLower, 'margen bruto') !== false || (strpos($nameLower, 'margen') !== false && strpos($nameLower, 'neto') === false)) {
                $newValue = $this->departments_model->getGrossMarginCurrentMonth($storeId);
            }
            // --- Utilidad neta ---
            elseif (strpos($nameLower, 'utilidad') !== false) {
                $newValue = $this->departments_model->getNetProfitCurrentQuarter($storeId);
            }
            // --- Dias inventario ---
            elseif (strpos($nameLower, 'dias inventario') !== false) {
                $newValue = $this->departments_model->getInventoryDays($storeId);
            }
            // --- Quiebres de stock / agotados ---
            elseif (strpos($nameLower, 'quiebre') !== false || strpos($nameLower, 'agotado') !== false) {
                $newValue = $this->departments_model->getStockBreakageRate($storeId);
            }
            // --- Faltante inventario / ventas ---
            elseif (strpos($nameLower, 'faltante') !== false) {
                $newValue = $this->departments_model->getInventoryShortageRate($storeId);
            }
            // --- Gastos admin / ventas ---
            elseif (strpos($nameLower, 'gasto') !== false && strpos($nameLower, 'venta') !== false) {
                $newValue = $this->departments_model->getAdminExpenseRateCurrentMonth($storeId);
            }
            // --- Ventas consolidadas (trimestral para gerencia) ---
            elseif (strpos($nameLower, 'ventas consolidada') !== false) {
                $newValue = $this->departments_model->getSalesCurrentQuarter($storeId);
            }
            // --- Crecimiento vs ano anterior ---
            elseif (strpos($nameLower, 'crecimiento') !== false) {
                $newValue = $this->departments_model->getGrowthVsPreviousYear($storeId);
            }
            // --- Ventas mensuales (generico) ---
            elseif (strpos($nameLower, 'venta') !== false || strpos($nameLower, 'factur') !== false) {
                $newValue = $this->departments_model->getSalesCurrentMonth($storeId);
            }
            // --- Cobro / recaudo ---
            elseif (strpos($nameLower, 'cobro') !== false || strpos($nameLower, 'recaudo') !== false) {
                $newValue = $this->departments_model->getCollectionRateCurrentMonth($storeId);
            }
            // --- Inventario / stock (valor) ---
            elseif (strpos($nameLower, 'inventario') !== false || strpos($nameLower, 'stock') !== false) {
                $newValue = $this->departments_model->getInventoryValue($storeId);
            }
            // --- Gastos genericos ---
            elseif (strpos($nameLower, 'gasto') !== false) {
                $newValue = $this->departments_model->getExpensesCurrentMonth($storeId);
            }
            // --- Clientes activos genericos ---
            elseif (strpos($nameLower, 'cliente') !== false) {
                $newValue = $this->departments_model->getActiveClientsCurrentMonth($storeId);
            }

            // KPIs manuales: exactitud inventario, despachos, transferencias,
            // garantias, satisfaccion, nomina, etc. → se dejan como estan

            // Actualizar solo si se calculo un valor
            if ($newValue !== null) {
                $this->departments_model->updateKpi($kpi->id, array('current_value' => $newValue));
                $updated++;
            }
        }

        $this->session->set_flashdata('success', "KPIs calculados exitosamente. Se actualizaron $updated de " . count($kpis) . " indicadores.");
        redirect(base_url() . 'sisvent/admin/departments/view/' . $deptId);
    }

    // ========================================================================
    // BONIFICACIONES
    // ========================================================================

    public function bonuses($id)
    {
        $department = $this->departments_model->getDepartment($id);
        if (!$department) {
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        $data = array(
            'department' => $department,
            'bonuses'    => $this->departments_model->getBonusesByDepartment($id)
        );

        $this->load->view('sisvent/admin/departments/bonuses', $data);
    }

    // ========================================================================
    // CALCULAR BONIFICACION
    // ========================================================================

    public function calculateBonus($deptId)
    {
        $department = $this->departments_model->getDepartment($deptId);
        if (!$department) {
            $this->session->set_flashdata('error', 'Departamento no encontrado');
            redirect(base_url() . 'sisvent/admin/departments');
            return;
        }

        $kpis = $this->departments_model->getKpisByDepartment($deptId);

        if (count($kpis) == 0) {
            $this->session->set_flashdata('error', 'No hay KPIs configurados para calcular bonificacion');
            redirect(base_url() . 'sisvent/admin/departments/view/' . $deptId);
            return;
        }

        // 1. Calcular puntaje ponderado y contar KPIs con cumplimiento >= 70%
        $totalWeightedCompliance = 0;
        $totalWeight = 0;
        $kpisAbove70 = 0;

        foreach ($kpis as $kpi) {
            $compliance = $this->_calculateCompliance($kpi);
            $totalWeightedCompliance += $compliance * $kpi->weight;
            $totalWeight += $kpi->weight;

            if ($compliance >= 70) {
                $kpisAbove70++;
            }
        }

        $weightedScore = $totalWeight > 0 ? round($totalWeightedCompliance / $totalWeight, 2) : 0;

        // 2. Obtener umbrales y montos de bono del departamento
        $minScore  = isset($department->min_score) ? (float)$department->min_score : 60;
        $bonusBase  = isset($department->bonus_base)  ? (float)$department->bonus_base  : 0;
        $bonusCumpl = isset($department->bonus_cumpl) ? (float)$department->bonus_cumpl : 0;
        $bonusElite = isset($department->bonus_elite) ? (float)$department->bonus_elite : 0;

        // 3. Determinar nivel de bono
        $bonusAmount = 0;
        $bonusTier = 'sin_bono';

        if ($weightedScore < $minScore || $kpisAbove70 < 3) {
            // No cumple requisitos minimos
            $bonusAmount = 0;
            $bonusTier = 'sin_bono';
        } elseif ($weightedScore >= 100) {
            $bonusAmount = $bonusElite;
            $bonusTier = 'elite';
        } elseif ($weightedScore >= 80) {
            $bonusAmount = $bonusCumpl;
            $bonusTier = 'cumplimiento';
        } elseif ($weightedScore >= $minScore) {
            $bonusAmount = $bonusBase;
            $bonusTier = 'base';
        }

        $currentQuarter = ceil(date('n') / 3);
        $currentYear = date('Y');
        $userId = $this->session->userdata('user_data')['uname'];

        // Construir notas descriptivas
        $tierLabels = array('sin_bono' => 'Sin bono', 'base' => 'Base', 'cumplimiento' => 'Cumplimiento', 'elite' => 'Elite');
        $tierLabel = isset($tierLabels[$bonusTier]) ? $tierLabels[$bonusTier] : $bonusTier;
        $notes = 'Calculo automatico - ' . count($kpis) . ' KPIs evaluados, ' . $kpisAbove70 . ' con >=70%. Nivel: ' . $tierLabel;

        if ($weightedScore < $minScore) {
            $notes .= '. Puntaje menor al minimo (' . $minScore . '%)';
        } elseif ($kpisAbove70 < 3) {
            $notes .= '. Menos de 3 KPIs con cumplimiento >=70%';
        }

        $bonusData = array(
            'department_id'    => $deptId,
            'year'             => $currentYear,
            'quarter'          => $currentQuarter,
            'compliance_score' => $weightedScore,
            'bonus_amount'     => $bonusAmount,
            'bonus_tier'       => $bonusTier,
            'kpis_above_70'    => $kpisAbove70,
            'calculated_by'    => $userId,
            'notes'            => $notes
        );

        $this->departments_model->saveBonus($bonusData);

        $msg = 'Bonificacion calculada: $' . number_format($bonusAmount, 0, ',', '.') . ' (Cumplimiento: ' . number_format($weightedScore, 1) . '%, Nivel: ' . $tierLabel . ')';
        $this->session->set_flashdata('success', $msg);
        redirect(base_url() . 'sisvent/admin/departments/view/' . $deptId);
    }

    // ========================================================================
    // METAS DE VENDEDORES
    // ========================================================================

    public function vendorGoals($deptId)
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $department = $this->departments_model->getDepartment($deptId);
        if (!$department) {
            echo json_encode(array('success' => false, 'message' => 'Departamento no encontrado'));
            return;
        }

        $vendorId = $this->input->post('vendor_id');
        $year = (int)$this->input->post('year');

        if (!$vendorId || !$year) {
            echo json_encode(array('success' => false, 'message' => 'Datos incompletos'));
            return;
        }

        $data = array(
            'userId' => $vendorId,
            'year'   => $year
        );

        for ($m = 1; $m <= 12; $m++) {
            $key = 'm' . $m;
            $val = $this->input->post($key);
            $data[$key] = $val !== null && $val !== '' ? (float)str_replace(array('.', ','), array('', '.'), $val) : 0;
        }

        $result = $this->invoices_model->saveVendorSalesGoal($data);

        if ($result) {
            echo json_encode(array('success' => true, 'message' => 'Metas guardadas exitosamente'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error al guardar las metas'));
        }
    }

    // ========================================================================
    // METODOS PRIVADOS
    // ========================================================================

    /**
     * Calcula el porcentaje de cumplimiento de un KPI
     */
    private function _calculateCompliance($kpi)
    {
        if (!$kpi->target_value || $kpi->target_value == 0) {
            return 0;
        }

        if ($kpi->direction == 'lower_better') {
            // Para KPIs donde menos es mejor (ej: gastos, cartera morosa)
            if ($kpi->current_value == 0) return 150; // Si no hay valor, cumplimiento maximo
            $compliance = ($kpi->target_value / $kpi->current_value) * 100;
        } else {
            // Para KPIs donde mas es mejor (ej: ventas, recaudo)
            $compliance = ($kpi->current_value / $kpi->target_value) * 100;
        }

        // Tope maximo de 150%
        return min(round($compliance, 1), 150);
    }
}
