<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledxury v2 — Presupuestos (read-only)
 *
 * Pantalla espejo de /sisvent/commercial/budgets pero con el rediseño v2.
 * Solo LECTURA en esta fase: cualquier acción de mutación (crear, editar,
 * aprobar, eliminar) se delega a las URLs de v1 vía link.
 *
 * Consume Budgets_model y Clients_model existentes — NO los modifica.
 *
 * Respeta backend_lib->controlModule('presupuestos') igual que v1.
 */
class Presupuestos extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('presupuestos');
        $this->load->model('budgets_model');
        $this->load->model('clients_model');
        $this->load->model('users_model');
        $this->load->model('stores_model');
        $this->load->model('vendors_model');
    }

    /**
     * Lista de presupuestos con KPIs + filtros por estado.
     *
     * Query params soportados (compatibles con v1):
     *   - estado: nuevo | preparando | guia | transito | entregado | incidencia | todos
     *   - p:      número de página
     */
    public function index()
    {
        $estado = $this->input->get('estado') ?: 'todos';
        $page   = max(1, (int) ($this->input->get('p') ?: 1));
        $limit  = 25;

        // Parámetros idénticos a /sisvent/commercial/budgets/index (v1).
        // $admin_store debe ser ARRAY (no string), $store/$vendor/etc strings 'all'.
        $user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']);
        if (!empty($user->admin_store)) {
            $user->admin_store_arr = explode(',', $user->admin_store);
        } else {
            $user->admin_store_arr = array();
        }

        $role   = (int) $this->session->userdata('user_data')['role'];
        $store  = 'all';
        $vendor = 'all';
        // Vendedores (role=3) ven solo lo suyo — replica lógica de v1
        if ($role === 3) {
            $vendor = $user->idUser ?? 'all';
        }

        // $getOthers: bool. v1 lo hace !in_array(role, [3,4]).
        $getOthers = !in_array($role, [3, 4]);

        $stateFilter = $this->_mapEstadoToState($estado);

        $budgets = $this->budgets_model->getBudgets(
            $getOthers, $store, $vendor, $stateFilter, 'all', 'all',
            $user->admin_store_arr, $page, $limit, 'all'
        );

        $total = $this->budgets_model->getTotal(
            $getOthers, $store, $vendor, $stateFilter, 'all', 'all',
            $user->admin_store_arr, 'all'
        );
        $lastPage = max(1, (int) ceil($total / $limit));

        // KPIs — conteo por estado (solo del store/vendor del usuario)
        $counts = [
            'todos'      => $this->budgets_model->getTotal($getOthers, $store, $vendor, 'all', 'all', 'all', $user->admin_store_arr, 'all'),
            'nuevo'      => $this->budgets_model->getTotal($getOthers, $store, $vendor, '0',   'all', 'all', $user->admin_store_arr, 'all'),
            'preparando' => $this->budgets_model->getTotal($getOthers, $store, $vendor, '1',   'all', 'all', $user->admin_store_arr, 'all'),
            'guia'       => $this->budgets_model->getTotal($getOthers, $store, $vendor, '2',   'all', 'all', $user->admin_store_arr, 'all'),
            'transito'   => $this->budgets_model->getTotal($getOthers, $store, $vendor, '3',   'all', 'all', $user->admin_store_arr, 'all'),
            'entregado'  => $this->budgets_model->getTotal($getOthers, $store, $vendor, '4',   'all', 'all', $user->admin_store_arr, 'all'),
        ];

        // Series temporales: últimos 14 días por estado, para sparklines.
        // Mostramos los últimos 14 días pero el sparkline grafica esos 14 puntos.
        $series = $this->_getSeries(14, $user->admin_store_arr, $getOthers, $vendor);

        // Comparativos (delta vs 14 días anteriores)
        $deltas = $this->_getDeltas(14, $user->admin_store_arr, $getOthers, $vendor);

        // Valor monetario total agregado (no solo conteo)
        $valorTotal = $this->_getValorTotal($user->admin_store_arr, $getOthers, $vendor);

        $data = [
            'pageTitle'   => 'Presupuestos',
            'activeRoute' => 'presupuestos',
            'breadcrumbs' => ['Comercial', 'Presupuestos'],
            'v1Url'       => base_url('sisvent/commercial/budgets'),

            'budgets'    => $budgets,
            'estado'     => $estado,
            'page'       => $page,
            'lastPage'   => $lastPage,
            'total'      => $total,
            'counts'     => $counts,
            'series'     => $series,
            'deltas'     => $deltas,
            'valorTotal' => $valorTotal,
        ];
        $this->load->view('sisvent/v2/presupuestos/index', $data);
    }

    /**
     * Genera series temporales de los últimos $days días para sparklines.
     * Retorna asoc: ['todos'=>[v1..vN], 'nuevo'=>[...], 'valor'=>[...]]
     */
    private function _getSeries($days, $adminStoreArr, $getOthers, $vendor)
    {
        $sql = "
            SELECT DATE(date) AS d,
                   COUNT(*) AS total,
                   SUM(CASE WHEN state = 0 THEN 1 ELSE 0 END) AS nuevo,
                   SUM(CASE WHEN state = 1 THEN 1 ELSE 0 END) AS preparando,
                   SUM(CASE WHEN state = 4 THEN 1 ELSE 0 END) AS entregado,
                   COALESCE(SUM(total), 0) AS valor
            FROM budgets
            WHERE archived = 0 AND deleted = 0
              AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        $args = [$days];
        if (!empty($adminStoreArr)) {
            $sql .= ' AND storeId IN (' . implode(',', array_map('intval', $adminStoreArr)) . ')';
        }
        if (!$getOthers) {
            $sql .= ' AND vendorId = ?';
            $args[] = $this->session->userdata('user_data')['uname'];
        }
        if ($vendor !== 'all') {
            $sql .= ' AND vendorId = ?';
            $args[] = $vendor;
        }
        $sql .= ' GROUP BY DATE(date) ORDER BY d ASC';

        $rows = $this->db->query($sql, $args)->result();

        // Indexar por fecha
        $byDate = [];
        foreach ($rows as $r) $byDate[$r->d] = $r;

        // Generar arreglos densos: 1 valor por día (rellenar 0 en días sin data)
        $series = ['todos' => [], 'nuevo' => [], 'preparando' => [], 'entregado' => [], 'valor' => []];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            if (isset($byDate[$d])) {
                $series['todos'][]      = (int) $byDate[$d]->total;
                $series['nuevo'][]      = (int) $byDate[$d]->nuevo;
                $series['preparando'][] = (int) $byDate[$d]->preparando;
                $series['entregado'][]  = (int) $byDate[$d]->entregado;
                $series['valor'][]      = (float) $byDate[$d]->valor;
            } else {
                $series['todos'][]      = 0;
                $series['nuevo'][]      = 0;
                $series['preparando'][] = 0;
                $series['entregado'][]  = 0;
                $series['valor'][]      = 0;
            }
        }
        return $series;
    }

    /**
     * Calcula deltas: período actual (últimos $days días) vs período anterior
     * (mismo tamaño de días, terminando ayer del período actual).
     */
    private function _getDeltas($days, $adminStoreArr, $getOthers, $vendor)
    {
        $sql = "
            SELECT
                SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) THEN 1 ELSE 0 END) AS curr_total,
                SUM(CASE WHEN date < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                          AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) THEN 1 ELSE 0 END) AS prev_total,
                SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) THEN total ELSE 0 END) AS curr_valor,
                SUM(CASE WHEN date < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                          AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) THEN total ELSE 0 END) AS prev_valor
            FROM budgets
            WHERE archived = 0 AND deleted = 0
        ";
        $args = [$days, $days, $days * 2, $days, $days, $days * 2];

        if (!empty($adminStoreArr)) {
            $sql .= ' AND storeId IN (' . implode(',', array_map('intval', $adminStoreArr)) . ')';
        }
        if (!$getOthers) {
            $sql .= ' AND vendorId = ?';
            $args[] = $this->session->userdata('user_data')['uname'];
        }
        if ($vendor !== 'all') {
            $sql .= ' AND vendorId = ?';
            $args[] = $vendor;
        }

        $row = $this->db->query($sql, $args)->row();
        if (!$row) return ['total_pct' => 0, 'valor_pct' => 0];

        $totalPct = $row->prev_total > 0
            ? round((($row->curr_total - $row->prev_total) / $row->prev_total) * 100, 1)
            : 0;
        $valorPct = $row->prev_valor > 0
            ? round((($row->curr_valor - $row->prev_valor) / $row->prev_valor) * 100, 1)
            : 0;

        return [
            'total_pct'  => $totalPct,
            'valor_pct'  => $valorPct,
            'curr_total' => (int) $row->curr_total,
            'curr_valor' => (float) $row->curr_valor,
            'prev_total' => (int) $row->prev_total,
            'prev_valor' => (float) $row->prev_valor,
        ];
    }

    /**
     * Valor monetario total de los presupuestos activos.
     */
    private function _getValorTotal($adminStoreArr, $getOthers, $vendor)
    {
        $sql = "SELECT COALESCE(SUM(total), 0) AS valor FROM budgets WHERE archived = 0 AND deleted = 0";
        $args = [];
        if (!empty($adminStoreArr)) {
            $sql .= ' AND storeId IN (' . implode(',', array_map('intval', $adminStoreArr)) . ')';
        }
        if (!$getOthers) {
            $sql .= ' AND vendorId = ?';
            $args[] = $this->session->userdata('user_data')['uname'];
        }
        if ($vendor !== 'all') {
            $sql .= ' AND vendorId = ?';
            $args[] = $vendor;
        }
        $row = $this->db->query($sql, $args)->row();
        return $row ? (float) $row->valor : 0;
    }

    /**
     * Detalle de un presupuesto.
     */
    public function view($id)
    {
        $id = (int) $id;
        if ($id <= 0) show_404();

        $budget = $this->budgets_model->getBudget($id);
        if (!$budget) show_404();

        $details = $this->budgets_model->getDetails($id);
        $client  = $this->clients_model->getClient($budget->clientId);

        $data = [
            'pageTitle'   => 'Presupuesto #' . $budget->idBudget,
            'activeRoute' => 'presupuestos',
            'breadcrumbs' => ['Comercial', 'Presupuestos', '#' . $budget->idBudget],
            'v1Url'       => base_url('sisvent/commercial/budgets/edit/' . $budget->idBudget),

            'budget'  => $budget,
            'details' => $details,
            'client'  => $client,
        ];
        $this->load->view('sisvent/v2/presupuestos/show', $data);
    }

    /** Mapea filtro de UI v2 al state numérico de la BD. */
    private function _mapEstadoToState($estado)
    {
        $map = [
            'todos'      => 'all',
            'nuevo'      => '0',
            'preparando' => '1',
            'guia'       => '2',
            'transito'   => '3',
            'entregado'  => '4',
            'incidencia' => '5',
        ];
        return $map[$estado] ?? 'all';
    }
}
