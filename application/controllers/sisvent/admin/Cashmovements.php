<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cashmovements extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // Solo Admin
        $this->load->model('cashmovements_model');
        $this->load->model('cashboxes_model');
        $this->load->model('bankaccounts_model');
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

        // Filtros
        $sourceType = $this->input->get('st');
        $sourceId = $this->input->get('si');
        $movementType = $this->input->get('mt');
        $from = $this->input->get('from');
        $to = $this->input->get('to');

        $filters = array();
        if ($sourceType && $sourceId) {
            $filters['sourceType'] = $sourceType;
            $filters['sourceId'] = $sourceId;
        }
        if ($movementType) $filters['movementType'] = $movementType;
        if ($from) $filters['from'] = $from . ' 00:00:00';
        if ($to) $filters['to'] = $to . ' 23:59:59';

        $limit = 50;
        $total = $this->cashmovements_model->getTotal($filters);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'movements' => $this->cashmovements_model->getMovements($filters, $page, $limit),
            'cashboxes' => $this->cashboxes_model->getCashboxesByStore($storeId),
            'bankAccounts' => $this->bankaccounts_model->getBankAccountsByStore($storeId),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'filters' => $filters
        );

        $this->load->view('sisvent/admin/cashmovements/list', $data);
    }

    public function search($term)
    {
        $term = str_replace("%20", " ", $term);
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 50;
        $total = $this->cashmovements_model->getTotalSearch($term);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $storeId = $this->session->userdata('user_data')['store'];

        $data = array(
            'movements' => $this->cashmovements_model->searchByWord($term, array(), $page, $limit),
            'cashboxes' => $this->cashboxes_model->getCashboxesByStore($storeId),
            'bankAccounts' => $this->bankaccounts_model->getBankAccountsByStore($storeId),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search_term' => $term
        );

        $this->load->view('sisvent/admin/cashmovements/list', $data);
    }

    // ========================================================================
    // CREAR MOVIMIENTO
    // ========================================================================

    public function add()
    {
        $storeId = $this->session->userdata('user_data')['store'];

        $data = array(
            'cashboxes' => $this->cashboxes_model->getActiveCashboxes($storeId),
            'bankAccounts' => $this->bankaccounts_model->getActiveBankAccounts($storeId)
        );

        $this->load->view('sisvent/admin/cashmovements/add', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $movementType = $this->input->post('movementType');
        $sourceType = $this->input->post('sourceType');
        $sourceId = $this->input->post('sourceId');
        $amount = (float)$this->input->post('amount');
        $concept = $this->input->post('concept');
        $category = $this->input->post('category');
        $notes = $this->input->post('notes');
        $userId = $this->session->userdata('user_data')['uname'];

        // Validaciones básicas
        $this->form_validation->set_rules('movementType', 'Tipo', 'required');
        $this->form_validation->set_rules('sourceType', 'Origen', 'required');
        $this->form_validation->set_rules('sourceId', 'Cuenta', 'required');
        $this->form_validation->set_rules('amount', 'Monto', 'required|is_numeric|greater_than[0]');
        $this->form_validation->set_rules('concept', 'Concepto', 'required|max_length[255]');

        if (!$this->form_validation->run()) {
            $this->add();
            return;
        }

        // Validar saldo suficiente para egresos
        if ($movementType == 'egreso') {
            if ($sourceType == 'caja') {
                $currentBalance = $this->cashboxes_model->getCurrentBalance($sourceId);
            } else {
                $currentBalance = $this->bankaccounts_model->getCurrentBalance($sourceId);
            }

            if ($amount > $currentBalance) {
                $this->session->set_flashdata('error', 'Saldo insuficiente. Saldo actual: $' . number_format($currentBalance, 2));
                redirect(base_url() . 'sisvent/admin/cashmovements/add');
            }
        }

        date_default_timezone_set("America/Bogota");

        // Crear movimiento
        $movementData = array(
            'movementType' => $movementType,
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
            'amount' => $amount,
            'concept' => $concept,
            'category' => $category,
            'executedBy' => $userId,
            'movementDate' => date('Y-m-d H:i:s'),
            'notes' => $notes,
            'status' => 'ejecutado'
        );

        $this->cashmovements_model->save($movementData);
        $movementId = $this->cashmovements_model->lastID();

        // Actualizar saldo
        if ($sourceType == 'caja') {
            $operation = ($movementType == 'ingreso') ? 'add' : 'subtract';
            $this->cashboxes_model->updateBalance($sourceId, $amount, $operation);
        } else {
            $operation = ($movementType == 'ingreso') ? 'add' : 'subtract';
            $this->bankaccounts_model->updateBalance($sourceId, $amount, $operation);
        }

        // Generar asiento contable via Accounting_lib
        $this->Accounting_lib->recordCashMovement(
            $movementId,
            $movementType,
            $sourceId,
            $amount,
            $this->session->userdata('user_data')['store'],
            $concept,
            $userId
        );

        redirect(base_url() . 'sisvent/admin/cashmovements');
    }

    // ========================================================================
    // VER DETALLE
    // ========================================================================

    public function view($id)
    {
        $movement = $this->cashmovements_model->getMovement($id);
        if (!$movement) {
            redirect(base_url() . 'sisvent/admin/cashmovements');
        }

        $data = array(
            'movement' => $movement
        );

        $this->load->view('sisvent/admin/cashmovements/view', $data);
    }

    // ========================================================================
    // ANULAR MOVIMIENTO
    // ========================================================================

    public function cancel($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $movement = $this->cashmovements_model->getMovement($id);
        if (!$movement) {
            echo 'error:Movimiento no encontrado';
            return;
        }

        if ($movement->status == 'anulado') {
            echo 'error:El movimiento ya está anulado';
            return;
        }

        // No anular apertura o cierre
        if (in_array($movement->movementType, ['apertura', 'cierre'])) {
            echo 'error:No se puede anular un movimiento de apertura o cierre';
            return;
        }

        // Revertir saldo
        if ($movement->sourceType == 'caja') {
            $reverseOp = in_array($movement->movementType, ['ingreso']) ? 'subtract' : 'add';
            $this->cashboxes_model->updateBalance($movement->sourceId, $movement->amount, $reverseOp);
        } else {
            $reverseOp = in_array($movement->movementType, ['ingreso']) ? 'subtract' : 'add';
            $this->bankaccounts_model->updateBalance($movement->sourceId, $movement->amount, $reverseOp);
        }

        // Anular movimiento
        $this->cashmovements_model->remove($id);

        echo 'success:Movimiento anulado correctamente';
    }

    // ========================================================================
    // TRANSFERENCIA
    // ========================================================================

    public function transfer()
    {
        $storeId = $this->session->userdata('user_data')['store'];

        $data = array(
            'cashboxes' => $this->cashboxes_model->getActiveCashboxes($storeId),
            'bankAccounts' => $this->bankaccounts_model->getActiveBankAccounts($storeId)
        );

        $this->load->view('sisvent/admin/cashmovements/transfer', $data);
    }

    public function processTransfer()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $sourceType = $this->input->post('sourceType');
        $sourceId = $this->input->post('sourceId');
        $destinationType = $this->input->post('destinationType');
        $destinationId = $this->input->post('destinationId');
        $amount = (float)$this->input->post('amount');
        $concept = $this->input->post('concept');
        $userId = $this->session->userdata('user_data')['uname'];
        $storeId = $this->session->userdata('user_data')['store'];

        // Validar origen != destino
        if ($sourceType == $destinationType && $sourceId == $destinationId) {
            $this->session->set_flashdata('error', 'El origen y destino no pueden ser iguales');
            redirect(base_url() . 'sisvent/admin/cashmovements/transfer');
        }

        // Validar saldo suficiente en origen
        if ($sourceType == 'caja') {
            $currentBalance = $this->cashboxes_model->getCurrentBalance($sourceId);
        } else {
            $currentBalance = $this->bankaccounts_model->getCurrentBalance($sourceId);
        }

        if ($amount > $currentBalance) {
            $this->session->set_flashdata('error', 'Saldo insuficiente en origen. Saldo actual: $' . number_format($currentBalance, 2));
            redirect(base_url() . 'sisvent/admin/cashmovements/transfer');
        }

        date_default_timezone_set("America/Bogota");
        $now = date('Y-m-d H:i:s');

        // Crear movimiento de transferencia (egreso en origen)
        $movementData = array(
            'movementType' => 'transferencia',
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
            'destinationType' => $destinationType,
            'destinationId' => $destinationId,
            'amount' => $amount,
            'concept' => $concept ? $concept : 'Transferencia',
            'category' => 'otro',
            'executedBy' => $userId,
            'movementDate' => $now,
            'status' => 'ejecutado'
        );

        $this->cashmovements_model->save($movementData);
        $movementId = $this->cashmovements_model->lastID();

        // Restar del origen
        if ($sourceType == 'caja') {
            $this->cashboxes_model->updateBalance($sourceId, $amount, 'subtract');
        } else {
            $this->bankaccounts_model->updateBalance($sourceId, $amount, 'subtract');
        }

        // Sumar al destino
        if ($destinationType == 'caja') {
            $this->cashboxes_model->updateBalance($destinationId, $amount, 'add');
        } else {
            $this->bankaccounts_model->updateBalance($destinationId, $amount, 'add');
        }

        // Generar asiento contable de transferencia
        $this->Accounting_lib->recordCashMovement(
            $movementId,
            'transfer',
            $sourceId,
            $amount,
            $storeId,
            $concept ? $concept : 'Transferencia',
            $userId,
            $destinationId
        );

        redirect(base_url() . 'sisvent/admin/cashmovements');
    }
}
