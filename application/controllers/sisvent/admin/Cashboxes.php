<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cashboxes extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // Solo Admin
        $this->load->model('cashboxes_model');
        $this->load->model('cashmovements_model');
        $this->load->model('cashboxclosures_model');
        $this->load->model('stores_model');
        $this->load->library('accounting_lib');
    }

    // ========================================================================
    // LISTADO
    // ========================================================================

    public function index()
    {
        $storeId = $this->session->userdata('user_data')['store'];
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->cashboxes_model->getTotal($storeId);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'cashboxes' => $this->cashboxes_model->getCashboxes($storeId, $page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit
        );

        $this->load->view('sisvent/admin/cashboxes/list', $data);
    }

    public function search($term)
    {
        $term = str_replace("%20", " ", $term);
        $storeId = $this->session->userdata('user_data')['store'];
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->cashboxes_model->getTotalSearch($term, $storeId);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'cashboxes' => $this->cashboxes_model->searchByWord($term, $storeId, $page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search_term' => $term
        );

        $this->load->view('sisvent/admin/cashboxes/list', $data);
    }

    // ========================================================================
    // CREAR
    // ========================================================================

    public function add()
    {
        $data = array(
            'stores' => $this->stores_model->getStores()
        );
        $this->load->view('sisvent/admin/cashboxes/add', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $name = $this->input->post('name');
        $code = $this->input->post('code');
        $type = $this->input->post('type');
        $initialBalance = max(0, (float)$this->input->post('initialBalance'));
        $storeId = $this->input->post('storeId');
        if ($storeId === null || $storeId === '' || $storeId === false) {
            $storeId = $this->session->userdata('user_data')['store'];
        }

        $this->form_validation->set_rules('name', 'Nombre', 'required|max_length[100]');
        $this->form_validation->set_rules('code', 'Código', 'required|max_length[20]');
        $this->form_validation->set_rules('type', 'Tipo', 'required');

        if ($this->form_validation->run()) {

            // Verificar código único
            if ($this->cashboxes_model->codeExists($code)) {
                $this->session->set_flashdata('error', 'El código ya existe. Por favor use otro.');
                redirect(base_url() . 'sisvent/admin/cashboxes/add');
            }

            $data = array(
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'storeId' => $storeId,
                'status' => 'cerrada',
                'initialBalance' => $initialBalance,
                'currentBalance' => $initialBalance
            );

            if ($this->cashboxes_model->save($data)) {
                redirect(base_url() . 'sisvent/admin/cashboxes');
            } else {
                $this->session->set_flashdata('error', 'No se pudo crear la caja');
                redirect(base_url() . 'sisvent/admin/cashboxes/add');
            }
        } else {
            $this->add();
        }
    }

    // ========================================================================
    // EDITAR
    // ========================================================================

    public function edit($id)
    {
        $cashbox = $this->cashboxes_model->getCashbox($id);
        if (!$cashbox) {
            redirect(base_url() . 'sisvent/admin/cashboxes');
        }

        $data = array(
            'cashbox' => $cashbox,
            'stores' => $this->stores_model->getStores()
        );

        $this->load->view('sisvent/admin/cashboxes/edit', $data);
    }

    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $id = $this->input->post('idCashbox');
        $name = $this->input->post('name');
        $code = $this->input->post('code');
        $type = $this->input->post('type');

        $this->form_validation->set_rules('name', 'Nombre', 'required|max_length[100]');
        $this->form_validation->set_rules('code', 'Código', 'required|max_length[20]');
        $this->form_validation->set_rules('type', 'Tipo', 'required');

        if ($this->form_validation->run()) {

            // Verificar código único excluyendo el actual
            if ($this->cashboxes_model->codeExists($code, $id)) {
                $this->session->set_flashdata('error', 'El código ya existe. Por favor use otro.');
                redirect(base_url() . 'sisvent/admin/cashboxes/edit/' . $id);
            }

            $data = array(
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'storeId' => $this->input->post('storeId')
            );

            if ($this->cashboxes_model->update($id, $data)) {
                redirect(base_url() . 'sisvent/admin/cashboxes');
            } else {
                $this->session->set_flashdata('error', 'No se pudo actualizar la caja');
                redirect(base_url() . 'sisvent/admin/cashboxes/edit/' . $id);
            }
        } else {
            $this->edit($id);
        }
    }

    // ========================================================================
    // ELIMINAR
    // ========================================================================

    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $cashbox = $this->cashboxes_model->getCashbox($id);
        if ($cashbox && $cashbox->status == 'abierta') {
            echo 'error:No se puede eliminar una caja abierta';
            return;
        }

        $this->cashboxes_model->remove($id);
        echo base_url() . 'sisvent/admin/cashboxes';
    }

    // ========================================================================
    // DETALLE
    // ========================================================================

    public function view($id)
    {
        $cashbox = $this->cashboxes_model->getCashbox($id);
        if (!$cashbox) {
            redirect(base_url() . 'sisvent/admin/cashboxes');
        }

        // Movimientos recientes de esta caja
        $movements = $this->cashmovements_model->getMovementsBySource('caja', $id);

        // Último cierre
        $lastClosure = $this->cashboxclosures_model->getLastClosure($id);

        // Saldo esperado actual (si la caja está abierta)
        $expectedInfo = null;
        if ($cashbox->status == 'abierta') {
            $now = date('Y-m-d H:i:s');
            $expectedInfo = $this->cashboxclosures_model->calculateExpectedBalance(
                $id, $cashbox->initialBalance, $cashbox->openedAt, $now
            );
        }

        $data = array(
            'cashbox' => $cashbox,
            'movements' => $movements,
            'lastClosure' => $lastClosure,
            'expectedInfo' => $expectedInfo
        );

        $this->load->view('sisvent/admin/cashboxes/view', $data);
    }

    // ========================================================================
    // APERTURA DE CAJA
    // ========================================================================

    public function open($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $cashbox = $this->cashboxes_model->getCashbox($id);
        if (!$cashbox) {
            echo 'error:Caja no encontrada';
            return;
        }

        if ($cashbox->status != 'cerrada') {
            echo 'error:La caja no está cerrada';
            return;
        }

        $initialBalance = (float)$this->input->post('initialBalance');
        if ($initialBalance < 0) {
            echo 'error:El saldo inicial no puede ser negativo';
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];

        // Verificar si este usuario ya tiene una caja abierta
        $userCashbox = $this->cashboxes_model->getCashboxByUser($userId);
        if ($userCashbox) {
            echo 'error:Ya tiene una caja abierta (' . $userCashbox->name . ')';
            return;
        }

        // Abrir caja
        if (!$this->cashboxes_model->openCashbox($id, $userId, $initialBalance)) {
            echo 'error:No se pudo abrir la caja';
            return;
        }

        // Registrar movimiento de apertura
        $movementData = array(
            'movementType' => 'apertura',
            'sourceType' => 'caja',
            'sourceId' => $id,
            'amount' => $initialBalance,
            'concept' => 'Apertura de caja',
            'category' => 'otro',
            'executedBy' => $userId,
            'movementDate' => date('Y-m-d H:i:s'),
            'status' => 'ejecutado'
        );
        $this->cashmovements_model->save($movementData);

        echo 'success:Caja abierta correctamente';
    }

    // ========================================================================
    // CIERRE DE CAJA
    // ========================================================================

    public function close($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $cashbox = $this->cashboxes_model->getCashbox($id);
        if (!$cashbox) {
            echo 'error:Caja no encontrada';
            return;
        }

        if ($cashbox->status != 'abierta') {
            echo 'error:La caja no está abierta';
            return;
        }

        $actualBalance = (float)$this->input->post('actualBalance');
        $billCount = $this->input->post('billCount');
        $notes = $this->input->post('notes');
        $userId = $this->session->userdata('user_data')['uname'];

        // Calcular saldo esperado
        $openedAt = $cashbox->openedAt;
        $now = date('Y-m-d H:i:s');
        $calculated = $this->cashboxclosures_model->calculateExpectedBalance(
            $id,
            $cashbox->initialBalance,
            $openedAt,
            $now
        );

        $expectedBalance = $calculated['expectedBalance'];
        $difference = $actualBalance - $expectedBalance;

        // Si diferencia > 5% requiere autorización (para futuro)
        $percentageDiff = ($expectedBalance != 0) ? abs($difference / $expectedBalance) * 100 : 0;

        // Crear registro de cierre
        $closureData = array(
            'cashboxId' => $id,
            'closureDate' => $now,
            'openingBalance' => $cashbox->initialBalance,
            'totalIngress' => $calculated['totalIngress'],
            'totalEgress' => $calculated['totalEgress'],
            'expectedBalance' => $expectedBalance,
            'actualBalance' => $actualBalance,
            'difference' => $difference,
            'billCount' => $billCount,
            'notes' => $notes,
            'closedBy' => $userId,
            'status' => ($percentageDiff > 5) ? 'borrador' : 'cerrada'
        );

        if (!$this->cashboxclosures_model->save($closureData)) {
            echo 'error:No se pudo crear el registro de cierre';
            return;
        }

        // Registrar movimiento de cierre
        $movementData = array(
            'movementType' => 'cierre',
            'sourceType' => 'caja',
            'sourceId' => $id,
            'amount' => $actualBalance,
            'concept' => 'Cierre de caja',
            'category' => 'otro',
            'executedBy' => $userId,
            'movementDate' => $now,
            'status' => 'ejecutado'
        );
        $this->cashmovements_model->save($movementData);

        // Cerrar caja
        $this->cashboxes_model->closeCashbox($id, $userId);

        // Respuesta
        if ($percentageDiff > 5) {
            echo 'warning:Caja cerrada pero requiere autorización por diferencia de ' .
                 number_format($percentageDiff, 1) . '%';
        } else {
            echo 'success:Caja cerrada correctamente';
        }
    }

    // ========================================================================
    // REPORTE DIARIO DE CAJA
    // ========================================================================

    public function reporte_diario($id)
    {
        $cashbox = $this->cashboxes_model->getCashbox($id);
        if (!$cashbox) {
            redirect(base_url() . 'sisvent/admin/cashboxes');
        }

        $date = $this->input->get('date');
        if (!$date) $date = date('Y-m-d');

        $fromDt = $date . ' 00:00:00';
        $toDt   = $date . ' 23:59:59';

        // Traer todos los movimientos para calcular saldo corrido
        $allMovements = $this->cashmovements_model->getMovementsBySource('caja', $id);

        $runningBalance = (float)$cashbox->initialBalance;
        $openingBalance = $runningBalance;
        $dayMovements   = array();

        foreach ($allMovements as $mov) {
            $sign = in_array($mov->movementType, ['egreso', 'cierre']) ? -1 : 1;

            if ($mov->movementDate < $fromDt) {
                $runningBalance += $sign * (float)$mov->amount;
                $openingBalance = $runningBalance;
            } elseif ($mov->movementDate <= $toDt) {
                $runningBalance += $sign * (float)$mov->amount;
                $mov->runningBalance = $runningBalance;
                $mov->sign = $sign;
                $dayMovements[] = $mov;
            }
        }

        $data = array(
            'cashbox'       => $cashbox,
            'movements'     => $dayMovements,
            'openingBalance'=> $openingBalance,
            'closingBalance'=> $runningBalance,
            'date'          => $date
        );

        $this->load->view('sisvent/admin/cashboxes/reporte_diario', $data);
    }
}
