<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bankaccounts extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // Solo Admin
        $this->load->model('bankaccounts_model');
        $this->load->model('cashmovements_model');
        $this->load->model('bankreconciliations_model');
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
        $total = $this->bankaccounts_model->getTotal($storeId);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'bankAccounts' => $this->bankaccounts_model->getBankAccounts($storeId, $page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit
        );

        $this->load->view('sisvent/admin/bankaccounts/list', $data);
    }

    public function search($term)
    {
        $term = str_replace("%20", " ", $term);
        $storeId = $this->session->userdata('user_data')['store'];
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->bankaccounts_model->getTotalSearch($term, $storeId);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'bankAccounts' => $this->bankaccounts_model->searchByWord($term, $storeId, $page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search_term' => $term
        );

        $this->load->view('sisvent/admin/bankaccounts/list', $data);
    }

    // ========================================================================
    // CREAR
    // ========================================================================

    public function add()
    {
        $this->load->view('sisvent/admin/bankaccounts/add');
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $bankName = $this->input->post('bankName');
        $accountNumber = $this->input->post('accountNumber');
        $accountType = $this->input->post('accountType');
        $ownerName = $this->input->post('ownerName');
        $ownerIdNumber = $this->input->post('ownerIdNumber');
        $branchOffice = $this->input->post('branchOffice');
        $contactEmail = $this->input->post('contactEmail');
        $contactPhone = $this->input->post('contactPhone');
        $initialBalance = (float)$this->input->post('initialBalance');
        $storeId = $this->session->userdata('user_data')['store'];

        $this->form_validation->set_rules('bankName', 'Banco', 'required|max_length[100]');
        $this->form_validation->set_rules('accountNumber', 'Número de cuenta', 'required|max_length[50]');
        $this->form_validation->set_rules('accountType', 'Tipo de cuenta', 'required');

        if (!$this->form_validation->run()) {
            $this->add();
            return;
        }

        // Verificar número de cuenta único
        if ($this->bankaccounts_model->accountNumberExists($accountNumber)) {
            $this->session->set_flashdata('error', 'El número de cuenta ya existe');
            redirect(base_url() . 'sisvent/admin/bankaccounts/add');
        }

        $data = array(
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'accountType' => $accountType,
            'storeId' => $storeId,
            'initialBalance' => $initialBalance,
            'currentBalance' => $initialBalance,
            'ownerName' => $ownerName,
            'ownerIdNumber' => $ownerIdNumber,
            'branchOffice' => $branchOffice,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone,
            'status' => 'activa'
        );

        if ($this->bankaccounts_model->save($data)) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        } else {
            $this->session->set_flashdata('error', 'No se pudo crear la cuenta bancaria');
            redirect(base_url() . 'sisvent/admin/bankaccounts/add');
        }
    }

    // ========================================================================
    // EDITAR
    // ========================================================================

    public function edit($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $data = array(
            'bankAccount' => $bankAccount
        );

        $this->load->view('sisvent/admin/bankaccounts/edit', $data);
    }

    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $id = $this->input->post('idBankAccount');
        $bankName = $this->input->post('bankName');
        $accountNumber = $this->input->post('accountNumber');
        $accountType = $this->input->post('accountType');
        $ownerName = $this->input->post('ownerName');
        $ownerIdNumber = $this->input->post('ownerIdNumber');
        $branchOffice = $this->input->post('branchOffice');
        $contactEmail = $this->input->post('contactEmail');
        $contactPhone = $this->input->post('contactPhone');
        $status = $this->input->post('status');

        $this->form_validation->set_rules('bankName', 'Banco', 'required|max_length[100]');
        $this->form_validation->set_rules('accountNumber', 'Número de cuenta', 'required|max_length[50]');
        $this->form_validation->set_rules('accountType', 'Tipo de cuenta', 'required');

        if (!$this->form_validation->run()) {
            $this->edit($id);
            return;
        }

        // Verificar número de cuenta único excluyendo actual
        if ($this->bankaccounts_model->accountNumberExists($accountNumber, $id)) {
            $this->session->set_flashdata('error', 'El número de cuenta ya existe');
            redirect(base_url() . 'sisvent/admin/bankaccounts/edit/' . $id);
        }

        $data = array(
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'accountType' => $accountType,
            'ownerName' => $ownerName,
            'ownerIdNumber' => $ownerIdNumber,
            'branchOffice' => $branchOffice,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone,
            'status' => $status
        );

        if ($this->bankaccounts_model->update($id, $data)) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        } else {
            $this->session->set_flashdata('error', 'No se pudo actualizar la cuenta bancaria');
            redirect(base_url() . 'sisvent/admin/bankaccounts/edit/' . $id);
        }
    }

    // ========================================================================
    // ELIMINAR
    // ========================================================================

    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $this->bankaccounts_model->remove($id);
        echo base_url() . 'sisvent/admin/bankaccounts';
    }

    // ========================================================================
    // DETALLE + MOVIMIENTOS
    // ========================================================================

    public function view($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        // Movimientos de este banco
        $movements = $this->cashmovements_model->getMovementsBySource('banco', $id);

        // Última conciliación
        $lastReconciliation = $this->bankreconciliations_model->getLastReconciliation($id);

        $data = array(
            'bankAccount' => $bankAccount,
            'movements' => $movements,
            'lastReconciliation' => $lastReconciliation
        );

        $this->load->view('sisvent/admin/bankaccounts/view', $data);
    }

    // ========================================================================
    // LIBRO DE BANCOS
    // ========================================================================

    public function libro($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $from = $this->input->get('from');
        $to   = $this->input->get('to');

        if (!$from) $from = date('Y-m-d', strtotime('-30 days'));
        if (!$to)   $to   = date('Y-m-d');

        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';

        // Traer todos los movimientos para calcular saldo corrido
        $allMovements = $this->cashmovements_model->getMovementsBySource('banco', $id);

        $runningBalance = (float)$bankAccount->initialBalance;
        $openingBalance = $runningBalance;
        $filteredMovements = array();

        foreach ($allMovements as $mov) {
            $sign = in_array($mov->movementType, ['egreso', 'cierre']) ? -1 : 1;

            if ($mov->movementDate < $fromDt) {
                $runningBalance += $sign * (float)$mov->amount;
                $openingBalance = $runningBalance;
            } elseif ($mov->movementDate <= $toDt) {
                $runningBalance += $sign * (float)$mov->amount;
                $mov->runningBalance = $runningBalance;
                $mov->sign = $sign;
                $filteredMovements[] = $mov;
            }
        }

        $data = array(
            'bankAccount'    => $bankAccount,
            'movements'      => $filteredMovements,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
            'from'           => $from,
            'to'             => $to
        );

        $this->load->view('sisvent/admin/bankaccounts/libro', $data);
    }

    // ========================================================================
    // CONCILIACIÓN BANCARIA
    // ========================================================================

    public function reconciliation($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $lastReconciliation = $this->bankreconciliations_model->getLastReconciliation($id);

        // Saldo libro = saldo actual de la cuenta bancaria en nuestros registros
        $bookBalance = $bankAccount->currentBalance;

        $data = array(
            'bankAccount'        => $bankAccount,
            'lastReconciliation' => $lastReconciliation,
            'bookBalance'        => $bookBalance
        );

        $this->load->view('sisvent/admin/bankaccounts/reconciliation', $data);
    }

    public function saveReconciliation()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $bankAccountId   = $this->input->post('bankAccountId');
        $bankBalance     = (float)$this->input->post('bankBalance');
        $statementDate   = $this->input->post('statementDate');
        $notes           = $this->input->post('notes');
        $userId         = $this->session->userdata('user_data')['uname'];

        $bankAccount = $this->bankaccounts_model->getBankAccount($bankAccountId);
        if (!$bankAccount) {
            $this->session->set_flashdata('error', 'Cuenta bancaria no encontrada');
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $bookBalance     = $bankAccount->currentBalance;
        $difference      = $bankBalance - $bookBalance;
        $reconciledBalance = min($bookBalance, $bankBalance);

        date_default_timezone_set("America/Bogota");

        $data = array(
            'bankAccountId'    => $bankAccountId,
            'reconciliationDate' => date('Y-m-d H:i:s'),
            'statementDate'    => date('Y-m-d', strtotime($statementDate)),
            'bookBalance'      => $bookBalance,
            'bankBalance'      => $bankBalance,
            'reconciledBalance'=> $reconciledBalance,
            'difference'       => $difference,
            'notes'            => $notes,
            'reconciledBy'     => $userId,
            'status'           => ($difference == 0) ? 'conciliada' : 'borrador'
        );

        if ($this->bankreconciliations_model->save($data)) {
            $this->logs_model->logMessage("info", "Usuario " . $userId . " realizó conciliación del banco " . $bankAccountId);
            redirect(base_url() . 'sisvent/admin/bankaccounts/view/' . $bankAccountId);
        } else {
            $this->session->set_flashdata('error', 'No se pudo guardar la conciliación');
            redirect(base_url() . 'sisvent/admin/bankaccounts/reconciliation/' . $bankAccountId);
        }
    }
}
