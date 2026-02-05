<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Libro Diario (Journal Book)
 * Lists all accounting entries with filters
 * Allows manual entry creation
 */
class Entries extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 4]); // Admin, Contador
        $this->load->model("entry_model");
        $this->load->model("stores_model");
        $this->load->model("subaccount_model");
        $this->load->model("auxsubaccount_model");
        $this->load->model("accountingperiods_model");
        $this->load->library("accounting_lib");
    }

    public function index()
    {
        $page = $this->input->get('p') ?: 1;
        $limit = 50;

        // Filtros
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to = $this->input->get('to') ?: date('Y-m-d');
        $storeId = $this->input->get('store');
        $type = $this->input->get('type');

        // Construir filtros para la consulta
        $filters = array(
            'from' => $from,
            'to' => $to,
            'store' => $storeId,
            'type' => $type
        );

        $total = $this->entry_model->getTotalEntriesFiltered($filters);
        $last = ceil($total / $limit);

        if ($page > $last) $page = max($last, 1);
        if ($page <= 0) $page = 1;

        $entries = $this->entry_model->getEntriesFiltered($filters, $page, $limit);

        // Calcular totales del período
        $totals = $this->entry_model->getTotalsByDateRange($from, $to, $storeId);

        $data = array(
            'entries' => $entries,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'stores' => $this->stores_model->getStores(),
            'filter_from' => $from,
            'filter_to' => $to,
            'filter_store' => $storeId,
            'filter_type' => $type,
            'totalDebit' => $totals->totalDebit ?: 0,
            'totalCredit' => $totals->totalCredit ?: 0
        );
        $this->load->view("sisvent/accounting/entries/list", $data);
    }

    /**
     * View single entry details
     */
    public function view($id)
    {
        $entry = $this->entry_model->getEntryWithDetails($id);

        if (!$entry) {
            redirect(base_url() . 'sisvent/accounting/entries');
        }

        $data = array(
            'entry' => $entry
        );
        $this->load->view("sisvent/accounting/entries/view", $data);
    }

    /**
     * Form to create manual entry
     */
    public function add()
    {
        $data = array(
            'stores' => $this->stores_model->getStores(),
            'subaccounts' => $this->subaccount_model->getSubaccounts(),
            'auxaccounts' => $this->auxsubaccount_model->getAuxsubaccounts()
        );
        $this->load->view("sisvent/accounting/entries/add", $data);
    }

    /**
     * Save manual entry
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $entryDate = $this->input->post('entryDate');
        $storeId = $this->input->post('storeId');
        $description = $this->input->post('description');
        $debitAccountId = $this->input->post('debitAccount');
        $debitAuxAccountId = $this->input->post('debitAuxAccount') ?: null;
        $creditAccountId = $this->input->post('creditAccount');
        $creditAuxAccountId = $this->input->post('creditAuxAccount') ?: null;
        $amount = floatval(str_replace(',', '', $this->input->post('amount')));
        $userId = $this->session->userdata('user_data')['uname'];

        // Validaciones básicas
        if (!$entryDate || !$description || !$debitAccountId || !$creditAccountId || $amount <= 0) {
            $this->session->set_flashdata('error', 'Todos los campos obligatorios deben ser completados.');
            redirect(base_url() . 'sisvent/accounting/entries/add');
            return;
        }

        // Validar que las cuentas sean diferentes
        if ($debitAccountId == $creditAccountId && $debitAuxAccountId == $creditAuxAccountId) {
            $this->session->set_flashdata('error', 'Las cuentas de débito y crédito no pueden ser iguales.');
            redirect(base_url() . 'sisvent/accounting/entries/add');
            return;
        }

        // Validar que el período no esté cerrado
        $year = date('Y', strtotime($entryDate));
        $month = date('n', strtotime($entryDate));
        if ($this->accountingperiods_model->isPeriodClosed($year, $month, $storeId)) {
            $this->session->set_flashdata('error', 'No se pueden crear asientos en un período cerrado (' . $month . '/' . $year . ').');
            redirect(base_url() . 'sisvent/accounting/entries/add');
            return;
        }

        // Crear asiento
        $this->db->trans_start();

        $entryData = array(
            'userID' => $userId,
            'entryDescription' => $description,
            'entryDate' => $entryDate,
            'entryStoreId' => $storeId,
            'entryType' => 'Manual',
            'entryTransactionType' => 'manual',
            'entryTransactionId' => null,
            'entryDebitAccount' => $debitAccountId,
            'entryDebitAuxaccount' => $debitAuxAccountId,
            'entryDebitBalance' => $amount,
            'entryCreditAccount' => $creditAccountId,
            'entryCreditAuxaccount' => $creditAuxAccountId,
            'entryCreditBalance' => $amount,
            'entryStatus' => 'activo',
            'created_by' => $userId,
            'entryCreateDate' => date('Y-m-d H:i:s'),
            'deleted' => 0
        );

        $this->entry_model->save($entryData);
        $entryId = $this->db->insert_id();

        // Actualizar saldos de subcuentas
        $this->updateAccountBalance($debitAccountId, $amount, 'debit');
        $this->updateAccountBalance($creditAccountId, $amount, 'credit');

        // Actualizar saldos de auxiliares si existen
        if ($debitAuxAccountId) {
            $this->updateAuxAccountBalance($debitAuxAccountId, $amount, 'debit');
        }
        if ($creditAuxAccountId) {
            $this->updateAuxAccountBalance($creditAuxAccountId, $amount, 'credit');
        }

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->logs_model->logMessage("info", "Usuario $userId creó asiento manual #$entryId");
            $this->session->set_flashdata('success', 'Asiento contable creado exitosamente.');
            redirect(base_url() . 'sisvent/accounting/entries/view/' . $entryId);
        } else {
            $this->session->set_flashdata('error', 'Error al crear el asiento contable.');
            redirect(base_url() . 'sisvent/accounting/entries/add');
        }
    }

    /**
     * Update subaccount balance
     */
    private function updateAccountBalance($accountId, $amount, $type)
    {
        $query = $this->db->select('accountSide, accountBalance, accountDebit, accountCredit')
            ->from('subaccounts')
            ->where('id', $accountId)
            ->get();

        if ($query->num_rows() == 0) return false;

        $account = $query->row();
        $accountSide = $account->accountSide;
        $currentBalance = $account->accountBalance;
        $currentDebit = $account->accountDebit;
        $currentCredit = $account->accountCredit;

        if ($accountSide == '1') {
            // Cuenta de naturaleza DÉBITO
            if ($type == 'debit') {
                $newBalance = $currentBalance + $amount;
                $newDebit = $currentDebit + $amount;
                $newCredit = $currentCredit;
            } else {
                $newBalance = $currentBalance - $amount;
                $newCredit = $currentCredit + $amount;
                $newDebit = $currentDebit;
            }
        } else {
            // Cuenta de naturaleza CRÉDITO
            if ($type == 'credit') {
                $newBalance = $currentBalance + $amount;
                $newCredit = $currentCredit + $amount;
                $newDebit = $currentDebit;
            } else {
                $newBalance = $currentBalance - $amount;
                $newDebit = $currentDebit + $amount;
                $newCredit = $currentCredit;
            }
        }

        $this->db->where('id', $accountId);
        $this->db->update('subaccounts', array(
            'accountBalance' => $newBalance,
            'accountDebit' => $newDebit,
            'accountCredit' => $newCredit,
            'updated_at' => date('Y-m-d H:i:s')
        ));

        return true;
    }

    /**
     * Update auxiliary account balance
     */
    private function updateAuxAccountBalance($auxAccountId, $amount, $type)
    {
        $query = $this->db->select('accountSide, accountBalance, accountDebit, accountCredit')
            ->from('auxiliary_subaccounts')
            ->where('id', $auxAccountId)
            ->get();

        if ($query->num_rows() == 0) return false;

        $account = $query->row();
        $accountSide = $account->accountSide;
        $currentBalance = $account->accountBalance;
        $currentDebit = $account->accountDebit;
        $currentCredit = $account->accountCredit;

        if ($accountSide == '1') {
            if ($type == 'debit') {
                $newBalance = $currentBalance + $amount;
                $newDebit = $currentDebit + $amount;
                $newCredit = $currentCredit;
            } else {
                $newBalance = $currentBalance - $amount;
                $newCredit = $currentCredit + $amount;
                $newDebit = $currentDebit;
            }
        } else {
            if ($type == 'credit') {
                $newBalance = $currentBalance + $amount;
                $newCredit = $currentCredit + $amount;
                $newDebit = $currentDebit;
            } else {
                $newBalance = $currentBalance - $amount;
                $newDebit = $currentDebit + $amount;
                $newCredit = $currentCredit;
            }
        }

        $this->db->where('id', $auxAccountId);
        $this->db->update('auxiliary_subaccounts', array(
            'accountBalance' => $newBalance,
            'accountDebit' => $newDebit,
            'accountCredit' => $newCredit,
            'updated_at' => date('Y-m-d H:i:s')
        ));

        return true;
    }

    /**
     * Get auxiliary accounts for a subaccount (AJAX)
     */
    public function getAuxAccounts($subaccountId)
    {
        $auxAccounts = $this->auxsubaccount_model->getAuxsubaccountsBySubaccount($subaccountId);
        header('Content-Type: application/json');
        echo json_encode($auxAccounts);
    }
}
