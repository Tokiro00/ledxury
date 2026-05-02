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

            // E.0.1 — Bloquear posteo en período cerrado.
            if ($this->accounting_lib->isPeriodClosed($expenseDate, $storeId)) {
                $this->session->set_flashdata('error', 'No se puede registrar un gasto con fecha en un período ya cerrado: ' . $expenseDate);
                redirect(base_url() . 'sisvent/admin/expenses/add');
                return;
            }

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

            // E.0.2 — Causación: SIEMPRE postear DR gasto / CR Proveedor [aux],
            // aunque el gasto se cree directo como pagado. Esto deja trail
            // limpio de cuentas por pagar por proveedor.
            $this->_processExpenseAccrual($expenseId, $amount, $providerId, $categoryId, $storeId, $userId, $description, $expenseDate);

            // Si se crea ya pagado, además posteamos DR Proveedor / CR Caja|Banco.
            if ($status == 'pagado' && $sourceType && $sourceId && $amount > 0) {
                $this->_processExpensePaymentToProvider($expenseId, $amount, $providerId, $sourceType, $sourceId, $storeId, $userId, $description, $expenseDate);
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

            // E.0.1 — Bloquear si la fecha cae en un período cerrado.
            if ($this->accounting_lib->isPeriodClosed($expenseDate, $storeId)) {
                $this->session->set_flashdata('error', 'No se puede modificar un gasto con fecha en un período ya cerrado: ' . $expenseDate);
                redirect(base_url() . 'sisvent/admin/expenses/edit/' . $id);
                return;
            }

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

            // Si transiciona a pagado: postear DR Proveedor / CR Caja|Banco.
            // (la causación ya se hizo al crear el gasto)
            if ($status == 'pagado' && $sourceType && $sourceId && $amount > 0) {
                $this->_processExpensePaymentToProvider($id, $amount, $providerId, $sourceType, $sourceId, $storeId, $userId, $description, $expenseDate);
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

        $userId = $this->session->userdata('user_data')['uname'];
        $this->db->trans_start();

        // E.0.2 — Si el gasto tenía causación posteada, reversarla en GL.
        if (!empty($expense->entry_id)) {
            $this->_processExpenseReversal($expense, $userId);
        }
        $this->expenserecords_model->remove($id);

        $this->db->trans_complete();
        echo base_url() . 'sisvent/admin/expenses';
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Causación contable de un gasto: DR <subcuenta gasto> / CR 220505 Proveedores [aux=proveedor].
     * Se llama SIEMPRE al crear el gasto, sin importar el status — así la cuenta
     * por pagar al proveedor refleja la deuda desde el momento del registro.
     *
     * Si la categoría no tiene subcuenta PUC mapeada, no se postea (silencioso).
     */
    private function _processExpenseAccrual($expenseId, $amount, $providerId, $categoryId, $storeId, $userId, $description, $expenseDate)
    {
        $category = $this->expensecategories_model->getCategory($categoryId);
        if (!$category || !$category->accounting_subaccount_id) {
            return null;
        }

        $entryId = $this->accounting_lib->recordExpenseAccrual(
            $expenseId, $amount, $category->accounting_subaccount_id,
            $providerId, $storeId, $userId,
            'Causación gasto: ' . $description, $expenseDate
        );

        if ($entryId) {
            $this->expenserecords_model->update($expenseId, array('entry_id' => $entryId));
        }
        return $entryId;
    }

    /**
     * Pago de un gasto a su proveedor:
     *   1. Crea cash_movement egreso
     *   2. Resta el saldo de la caja/banco
     *   3. Postea asiento DR 220505 Proveedores [aux] / CR Caja|Banco
     */
    private function _processExpensePaymentToProvider($expenseId, $amount, $providerId, $sourceType, $sourceId, $storeId, $userId, $description, $expenseDate)
    {
        // 1. Movimiento de caja/banco
        $this->cashmovements_model->save(array(
            'movementType' => 'egreso',
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
            'amount' => $amount,
            'concept' => 'Pago gasto: ' . $description,
            'category' => 'gasto',
            'referenceType' => 'expense',
            'referenceId' => $expenseId,
            'executedBy' => $userId,
            'movementDate' => $expenseDate . ' ' . date('H:i:s'),
            'status' => 'ejecutado'
        ));
        $movementId = $this->cashmovements_model->lastID();

        // 2. Saldo
        if ($sourceType == 'caja') {
            $this->cashboxes_model->updateBalance($sourceId, $amount, 'subtract');
        } else {
            $this->bankaccounts_model->updateBalance($sourceId, $amount, 'subtract');
        }

        // 3. Asiento DR Proveedor / CR Caja|Banco
        $cashAccountId = ($sourceType == 'caja')
            ? $this->accounting_lib->getCashAccount($storeId)
            : $this->accounting_lib->getBankAccount($storeId);

        $paymentEntryId = null;
        if ($cashAccountId) {
            $paymentEntryId = $this->accounting_lib->recordExpensePaymentToProvider(
                $expenseId, $amount, $providerId, $cashAccountId,
                $storeId, $userId,
                'Pago gasto: ' . $description, $expenseDate
            );
        }

        $updateData = array('cash_movement_id' => $movementId);
        if ($paymentEntryId) $updateData['payment_entry_id'] = $paymentEntryId;
        $this->expenserecords_model->update($expenseId, $updateData);

        return $paymentEntryId;
    }

    /**
     * Reversa contable cuando se anula un gasto pendiente que ya tenía
     * causación posteada. Postea DR 220505 Proveedores [aux] / CR <subcuenta gasto>.
     */
    private function _processExpenseReversal($expense, $userId)
    {
        if (empty($expense->entry_id)) return null;

        $category = $this->expensecategories_model->getCategory($expense->expense_category_id);
        if (!$category || !$category->accounting_subaccount_id) return null;

        $reversalEntryId = $this->accounting_lib->reverseExpenseAccrual(
            $expense->id, $expense->amount, $category->accounting_subaccount_id,
            $expense->provider_id, $expense->store_id, $userId,
            'Reversa anulación gasto: ' . $expense->description, $expense->expense_date
        );

        if ($reversalEntryId) {
            $this->expenserecords_model->update($expense->id, array('reversal_entry_id' => $reversalEntryId));
        }
        return $reversalEntryId;
    }
}
