<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('gastos');
        $this->load->model('expenserecords_model');
        $this->load->model('expensecategories_model');
        $this->load->model('cashboxes_model');
        $this->load->model('bankaccounts_model');
        $this->load->model('cashmovements_model');
        $this->load->model('stores_model');
        $this->load->model('providers_model');
        $this->load->library('accounting_lib');
    }

    // ========================================================================
    // LISTADO
    // ========================================================================

    public function index()
    {
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $filters = array();
        if ($this->input->get('category_id')) $filters['category_id'] = $this->input->get('category_id');
        if ($this->input->get('provider_id')) $filters['provider_id'] = $this->input->get('provider_id');
        if ($this->input->get('status')) $filters['status'] = $this->input->get('status');
        if ($this->input->get('store_id')) $filters['store_id'] = $this->input->get('store_id');
        if ($this->input->get('from')) $filters['from'] = $this->input->get('from');
        if ($this->input->get('to')) $filters['to'] = $this->input->get('to');

        $limit = 20;
        $total = $this->expenserecords_model->getTotal($filters);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'expenses' => $this->expenserecords_model->getExpenseRecords($page, $limit, $filters),
            'categories' => $this->expensecategories_model->getActiveCategories(),
            'stores' => $this->stores_model->getStores(),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'filters' => $filters
        );

        $this->load->view('sisvent/admin/expenses/list', $data);
    }

    public function search($term)
    {
        $term = str_replace("%20", " ", $term);
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->expenserecords_model->getTotalSearch($term);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'expenses' => $this->expenserecords_model->searchByWord($term, $page, $limit),
            'categories' => $this->expensecategories_model->getActiveCategories(),
            'stores' => $this->stores_model->getStores(),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search_term' => $term,
            'filters' => array()
        );

        $this->load->view('sisvent/admin/expenses/list', $data);
    }

    // ========================================================================
    // CREAR
    // ========================================================================

    public function add()
    {
        $storeId = $this->session->userdata('user_data')['store'];

        $data = array(
            'categories' => $this->expensecategories_model->getActiveCategories(),
            'providers' => $this->providers_model->getProviders(),
            'cashboxes' => $this->cashboxes_model->getCashboxesByStore($storeId),
            'bankaccounts' => $this->bankaccounts_model->getBankAccountsByStore($storeId),
            'stores' => $this->stores_model->getStores(),
            'nextCode' => $this->expenserecords_model->getNextCode()
        );

        $this->load->view('sisvent/admin/expenses/add', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $code = $this->expenserecords_model->getNextCode();
        $description = $this->input->post('description');
        $providerId = $this->input->post('provider_id');
        $categoryId = $this->input->post('expense_category_id');
        $amount = (float)$this->input->post('amount');
        $expenseDate = $this->input->post('expense_date');
        $status = $this->input->post('status');
        $storeId = $this->input->post('store_id');
        $sourceType = $this->input->post('source_type');
        // Resolver source_id en PHP (no depender del hidden field JS)
        $sourceId = $this->input->post('source_id');
        if (!$sourceId) {
            $sourceId = ($sourceType == 'banco')
                ? $this->input->post('source_id_banco')
                : $this->input->post('source_id_caja');
        }
        $paymentMethod = $this->input->post('payment_method');
        $voucherReference = $this->input->post('voucher_reference');
        $observations = $this->input->post('observations');
        $userId = $this->session->userdata('user_data')['uname'];

        $this->form_validation->set_rules('description', 'Descripción', 'required');
        $this->form_validation->set_rules('provider_id', 'Proveedor', 'required');
        $this->form_validation->set_rules('expense_category_id', 'Categoría', 'required');
        $this->form_validation->set_rules('amount', 'Monto', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('expense_date', 'Fecha', 'required');
        $this->form_validation->set_rules('store_id', 'Bodega', 'required');

        if ($this->form_validation->run()) {

            $this->db->trans_start();

            $data = array(
                'code' => $code,
                'description' => $description,
                'provider_id' => $providerId,
                'expense_category_id' => $categoryId,
                'amount' => $amount,
                'expense_date' => $expenseDate,
                'status' => $status,
                'store_id' => $storeId,
                'source_type' => ($status == 'pagado') ? $sourceType : null,
                'source_id' => ($status == 'pagado') ? $sourceId : null,
                'payment_method' => $paymentMethod,
                'voucher_reference' => $voucherReference,
                'observations' => $observations,
                'created_by' => $userId
            );

            $this->expenserecords_model->save($data);
            $expenseId = $this->expenserecords_model->lastID();

            // Si el gasto se marca como pagado, procesar el pago
            if ($status == 'pagado' && $sourceType && $sourceId && $amount > 0) {
                $this->_processExpensePayment($expenseId, $amount, $sourceType, $sourceId, $categoryId, $storeId, $userId, $description, $expenseDate);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status()) {
                redirect(base_url() . 'sisvent/admin/expenses');
            } else {
                $this->session->set_flashdata('error', 'Error al procesar el gasto');
                redirect(base_url() . 'sisvent/admin/expenses/add');
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
        $expense = $this->expenserecords_model->getExpenseRecord($id);
        if (!$expense) {
            redirect(base_url() . 'sisvent/admin/expenses');
        }

        $storeId = $this->session->userdata('user_data')['store'];

        $data = array(
            'expense' => $expense,
            'categories' => $this->expensecategories_model->getActiveCategories(),
            'providers' => $this->providers_model->getProviders(),
            'cashboxes' => $this->cashboxes_model->getCashboxesByStore($storeId),
            'bankaccounts' => $this->bankaccounts_model->getBankAccountsByStore($storeId),
            'stores' => $this->stores_model->getStores()
        );

        $this->load->view('sisvent/admin/expenses/edit', $data);
    }

    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $id = $this->input->post('id');
        $expense = $this->expenserecords_model->getExpenseRecord($id);
        if (!$expense) {
            redirect(base_url() . 'sisvent/admin/expenses');
        }

        // No se puede editar un gasto ya pagado
        if ($expense->status == 'pagado') {
            $this->session->set_flashdata('error', 'No se puede editar un gasto ya pagado');
            redirect(base_url() . 'sisvent/admin/expenses/edit/' . $id);
            return;
        }

        $description = $this->input->post('description');
        $providerId = $this->input->post('provider_id');
        $categoryId = $this->input->post('expense_category_id');
        $amount = (float)$this->input->post('amount');
        $expenseDate = $this->input->post('expense_date');
        $status = $this->input->post('status');
        $storeId = $this->input->post('store_id');
        $sourceType = $this->input->post('source_type');
        // Resolver source_id en PHP (no depender del hidden field JS)
        $sourceId = $this->input->post('source_id');
        if (!$sourceId) {
            $sourceId = ($sourceType == 'banco')
                ? $this->input->post('source_id_banco')
                : $this->input->post('source_id_caja');
        }
        $paymentMethod = $this->input->post('payment_method');
        $voucherReference = $this->input->post('voucher_reference');
        $observations = $this->input->post('observations');
        $userId = $this->session->userdata('user_data')['uname'];

        $this->form_validation->set_rules('description', 'Descripción', 'required');
        $this->form_validation->set_rules('provider_id', 'Proveedor', 'required');
        $this->form_validation->set_rules('expense_category_id', 'Categoría', 'required');
        $this->form_validation->set_rules('amount', 'Monto', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('expense_date', 'Fecha', 'required');
        $this->form_validation->set_rules('store_id', 'Bodega', 'required');

        if ($this->form_validation->run()) {

            $this->db->trans_start();

            $data = array(
                'description' => $description,
                'provider_id' => $providerId,
                'expense_category_id' => $categoryId,
                'amount' => $amount,
                'expense_date' => $expenseDate,
                'status' => $status,
                'store_id' => $storeId,
                'source_type' => ($status == 'pagado') ? $sourceType : null,
                'source_id' => ($status == 'pagado') ? $sourceId : null,
                'payment_method' => $paymentMethod,
                'voucher_reference' => $voucherReference,
                'observations' => $observations
            );

            $this->expenserecords_model->update($id, $data);

            // Si se cambió a pagado, procesar pago
            if ($status == 'pagado' && $sourceType && $sourceId && $amount > 0) {
                $this->_processExpensePayment($id, $amount, $sourceType, $sourceId, $categoryId, $storeId, $userId, $description, $expenseDate);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status()) {
                redirect(base_url() . 'sisvent/admin/expenses');
            } else {
                $this->session->set_flashdata('error', 'Error al actualizar el gasto');
                redirect(base_url() . 'sisvent/admin/expenses/edit/' . $id);
            }
        } else {
            $this->edit($id);
        }
    }

    // ========================================================================
    // DETALLE
    // ========================================================================

    public function view($id)
    {
        $expense = $this->expenserecords_model->getExpenseRecord($id);
        if (!$expense) {
            redirect(base_url() . 'sisvent/admin/expenses');
        }

        $data = array(
            'expense' => $expense
        );

        $this->load->view('sisvent/admin/expenses/view', $data);
    }

    // ========================================================================
    // ELIMINAR (ANULAR)
    // ========================================================================

    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $expense = $this->expenserecords_model->getExpenseRecord($id);
        if (!$expense) {
            echo 'error:Gasto no encontrado';
            return;
        }

        if ($expense->status == 'pagado') {
            echo 'error:No se puede anular un gasto ya pagado. Debe hacer un reverso contable.';
            return;
        }

        $this->expenserecords_model->remove($id);
        echo base_url() . 'sisvent/admin/expenses';
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Procesa el pago de un gasto:
     * 1. Crea movimiento de caja/banco (egreso)
     * 2. Actualiza saldo
     * 3. Crea asiento contable
     */
    private function _processExpensePayment($expenseId, $amount, $sourceType, $sourceId, $categoryId, $storeId, $userId, $description, $expenseDate)
    {
        // 1. Crear movimiento de caja/banco
        $movementData = array(
            'movementType' => 'egreso',
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
            'amount' => $amount,
            'concept' => 'Gasto: ' . $description,
            'category' => 'gasto',
            'referenceType' => 'expense',
            'referenceId' => $expenseId,
            'executedBy' => $userId,
            'movementDate' => $expenseDate . ' ' . date('H:i:s'),
            'status' => 'ejecutado'
        );
        $this->cashmovements_model->save($movementData);
        $movementId = $this->cashmovements_model->lastID();

        // 2. Actualizar saldo de caja/banco
        if ($sourceType == 'caja') {
            $this->cashboxes_model->updateBalance($sourceId, $amount, 'subtract');
        } else {
            $this->bankaccounts_model->updateBalance($sourceId, $amount, 'subtract');
        }

        // 3. Crear asiento contable
        // DÉBITO: Subcuenta de gasto (de la categoría)
        // CRÉDITO: Caja o Banco
        $category = $this->expensecategories_model->getCategory($categoryId);
        $debitAccountId = null;

        if ($category && $category->accounting_subaccount_id) {
            $debitAccountId = $category->accounting_subaccount_id;
        }

        $entryId = null;

        if ($debitAccountId) {
            // Obtener cuenta de caja/banco
            $creditAccountId = ($sourceType == 'caja')
                ? $this->accounting_lib->getCashAccount($storeId)
                : $this->accounting_lib->getBankAccount($storeId);

            if ($creditAccountId) {
                $entryId = $this->accounting_lib->recordExpense(
                    $expenseId, $amount, $debitAccountId, $storeId, $userId,
                    'Gasto: ' . $description, $creditAccountId, $expenseDate
                );
            }
        }

        // Vincular IDs al gasto
        $updateData = array('cash_movement_id' => $movementId);
        if ($entryId) {
            $updateData['entry_id'] = $entryId;
        }
        $this->expenserecords_model->update($expenseId, $updateData);
    }
}
