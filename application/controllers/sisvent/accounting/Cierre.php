<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cierre Contable (Accounting Period Closing)
 *
 * Maneja el cierre de períodos contables mensuales y anuales.
 * Genera asientos de cierre para transferir resultados a patrimonio.
 */
class Cierre extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('contabilidad'); // Solo Admin y Contador
        $this->load->model('accountingperiods_model');
        $this->load->model('entry_model');
        $this->load->model('subaccount_model');
        $this->load->model('stores_model');
    }

    /**
     * List all accounting periods
     */
    public function index()
    {
        $storeId = $this->input->get('store');
        $page = $this->input->get('p') ?: 1;
        $limit = 20;

        $total = $this->accountingperiods_model->getTotalPeriods($storeId);
        $last = ceil($total / $limit);

        if ($page > $last && $last > 0) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'periods' => $this->accountingperiods_model->getPeriods($storeId, $page, $limit),
            'stores' => $this->stores_model->getStores(),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'filter_store' => $storeId
        );

        $this->load->view('sisvent/accounting/cierre/list', $data);
    }

    /**
     * Show form to initiate period closing
     */
    public function nuevo()
    {
        $data = array(
            'stores' => $this->stores_model->getStores(),
            'currentYear' => date('Y'),
            'currentMonth' => date('n')
        );

        $this->load->view('sisvent/accounting/cierre/nuevo', $data);
    }

    /**
     * Preview the closing entry before committing
     */
    public function preview()
    {
        $year = $this->input->get('year') ?: date('Y');
        $month = $this->input->get('month') ?: date('n');
        $storeId = $this->input->get('store') ?: null;
        $periodType = $this->input->get('type') ?: 'monthly';

        // Check if period already exists and is closed
        $existingPeriod = $this->accountingperiods_model->getPeriodByYearMonth($year, $month, $storeId, $periodType);
        if ($existingPeriod && $existingPeriod->status === 'closed') {
            $this->session->set_flashdata('error', 'Este período ya está cerrado.');
            redirect(base_url() . 'sisvent/accounting/cierre');
            return;
        }

        // Calculate date range
        if ($periodType === 'yearly') {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
        } else {
            $startDate = date('Y-m-01', strtotime("$year-$month-01"));
            $endDate = date('Y-m-t', strtotime("$year-$month-01"));
        }

        // Get balances for the period
        $balances = $this->entry_model->getBalancesByAccount($startDate, $endDate, $storeId);

        // Get accounts by type
        $incomeAccounts = $this->subaccount_model->getSubaccountsByStatement('2'); // P&L accounts

        $income = 0;
        $expenses = 0;
        $costs = 0;
        $incomeDetails = array();
        $expenseDetails = array();
        $costDetails = array();

        foreach ($incomeAccounts as $acc) {
            $debit = isset($balances[$acc->id]) ? $balances[$acc->id]['debit'] : 0;
            $credit = isset($balances[$acc->id]) ? $balances[$acc->id]['credit'] : 0;

            // Calculate balance based on account side
            if ($acc->accountSide == '1') {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            if ($balance == 0) continue;

            $acc->balance = $balance;
            $acc->totalDebit = $debit;
            $acc->totalCredit = $credit;

            // Group by class (4=Income, 5=Expenses, 6=Costs)
            if ($acc->classID == 4) {
                $income += $balance;
                $incomeDetails[] = $acc;
            } elseif ($acc->classID == 5) {
                $expenses += $balance;
                $expenseDetails[] = $acc;
            } elseif ($acc->classID == 6) {
                $costs += $balance;
                $costDetails[] = $acc;
            }
        }

        $netIncome = $income - $expenses - $costs;

        // Get the equity account for closing
        $utilityAccount = $this->subaccount_model->getSubaccountByPucAndStore('360505', $storeId);
        $lossAccount = $this->subaccount_model->getSubaccountByPucAndStore('360510', $storeId);

        $data = array(
            'year' => $year,
            'month' => $month,
            'periodType' => $periodType,
            'storeId' => $storeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'income' => $income,
            'expenses' => $expenses,
            'costs' => $costs,
            'netIncome' => $netIncome,
            'incomeDetails' => $incomeDetails,
            'expenseDetails' => $expenseDetails,
            'costDetails' => $costDetails,
            'utilityAccount' => $utilityAccount,
            'lossAccount' => $lossAccount,
            'existingPeriod' => $existingPeriod,
            'stores' => $this->stores_model->getStores()
        );

        $this->load->view('sisvent/accounting/cierre/preview', $data);
    }

    /**
     * Execute the period closing
     */
    public function ejecutar()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $year = $this->input->post('year');
        $month = $this->input->post('month');
        $storeId = $this->input->post('store') ?: null;
        $periodType = $this->input->post('periodType') ?: 'monthly';
        $notes = $this->input->post('notes');
        $userId = $this->session->userdata('user_data')['uname'];

        // Validate
        if (!$year || !$month) {
            $this->session->set_flashdata('error', 'Año y mes son requeridos.');
            redirect(base_url() . 'sisvent/accounting/cierre/nuevo');
            return;
        }

        // Check if period already closed
        $existingPeriod = $this->accountingperiods_model->getPeriodByYearMonth($year, $month, $storeId, $periodType);
        if ($existingPeriod && $existingPeriod->status === 'closed') {
            $this->session->set_flashdata('error', 'Este período ya está cerrado.');
            redirect(base_url() . 'sisvent/accounting/cierre');
            return;
        }

        // Calculate date range
        if ($periodType === 'yearly') {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
        } else {
            $startDate = date('Y-m-01', strtotime("$year-$month-01"));
            $endDate = date('Y-m-t', strtotime("$year-$month-01"));
        }

        // Get balances for the period
        $balances = $this->entry_model->getBalancesByAccount($startDate, $endDate, $storeId);

        // Calculate totals
        $incomeAccounts = $this->subaccount_model->getSubaccountsByStatement('2');
        $income = 0;
        $expenses = 0;
        $costs = 0;

        foreach ($incomeAccounts as $acc) {
            $debit = isset($balances[$acc->id]) ? $balances[$acc->id]['debit'] : 0;
            $credit = isset($balances[$acc->id]) ? $balances[$acc->id]['credit'] : 0;

            if ($acc->accountSide == '1') {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            if ($acc->classID == 4) {
                $income += $balance;
            } elseif ($acc->classID == 5) {
                $expenses += $balance;
            } elseif ($acc->classID == 6) {
                $costs += $balance;
            }
        }

        $netIncome = $income - $expenses - $costs;

        // Get the appropriate equity account
        if ($netIncome >= 0) {
            $equityAccount = $this->subaccount_model->getSubaccountByPucAndStore('360505', $storeId);
        } else {
            $equityAccount = $this->subaccount_model->getSubaccountByPucAndStore('360510', $storeId);
        }

        // Get income summary account (could be 5905 - Ganancias y Pérdidas if exists)
        // For simplicity, we'll just use the equity account directly
        $summaryAccount = $equityAccount;

        // Start transaction
        $this->db->trans_start();

        // Create or get the period
        $period = $this->accountingperiods_model->getOrCreatePeriod($year, $month, $storeId, $periodType);

        $closingEntryId = null;

        // Only create closing entry if there's a net income/loss
        if (abs($netIncome) > 0.01) {
            // Create closing entry
            $monthName = $this->getMonthName($month);
            $entryDescription = "Cierre contable - $monthName $year";
            if ($periodType === 'yearly') {
                $entryDescription = "Cierre contable anual - $year";
            }

            $entryData = array(
                'userID' => $userId,
                'entryDescription' => $entryDescription,
                'entryDate' => $endDate,
                'entryStoreId' => $storeId,
                'entryType' => 'Cierre',
                'entryTransactionType' => 'closing',
                'entryStatus' => 'activo',
                'entryCreateDate' => date('Y-m-d H:i:s'),
                'created_by' => $userId
            );

            // For net income (profit): DR Income Summary, CR Retained Earnings
            // For net loss: DR Retained Earnings, CR Income Summary
            if ($netIncome >= 0) {
                // Profit: Credit to equity (Utilidad del Ejercicio)
                $entryData['entryDebitAccount'] = $summaryAccount->id;
                $entryData['entryDebitBalance'] = abs($netIncome);
                $entryData['entryCreditAccount'] = $equityAccount->id;
                $entryData['entryCreditBalance'] = abs($netIncome);
            } else {
                // Loss: Debit to equity (Pérdida del Ejercicio)
                $entryData['entryDebitAccount'] = $equityAccount->id;
                $entryData['entryDebitBalance'] = abs($netIncome);
                $entryData['entryCreditAccount'] = $summaryAccount->id;
                $entryData['entryCreditBalance'] = abs($netIncome);
            }

            $this->entry_model->save($entryData);
            $closingEntryId = $this->db->insert_id();
        }

        // Update period status
        $totals = array(
            'income' => $income,
            'expenses' => $expenses,
            'costs' => $costs,
            'netIncome' => $netIncome
        );

        $periodUpdateData = array(
            'status' => 'closed',
            'closingEntryId' => $closingEntryId,
            'closedBy' => $userId,
            'closedAt' => date('Y-m-d H:i:s'),
            'totalIncome' => $income,
            'totalExpenses' => $expenses,
            'totalCosts' => $costs,
            'netIncome' => $netIncome,
            'notes' => $notes
        );

        $this->accountingperiods_model->update($period->id, $periodUpdateData);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->logs_model->logMessage("info", "Usuario $userId cerró el período $month/$year");
            $this->session->set_flashdata('success', 'Período cerrado exitosamente.');
            redirect(base_url() . 'sisvent/accounting/cierre/view/' . $period->id);
        } else {
            $this->session->set_flashdata('error', 'Error al cerrar el período.');
            redirect(base_url() . 'sisvent/accounting/cierre/preview?year=' . $year . '&month=' . $month . '&store=' . $storeId);
        }
    }

    /**
     * View a closed period
     */
    public function view($id)
    {
        $period = $this->accountingperiods_model->getPeriod($id);
        if (!$period) {
            redirect(base_url() . 'sisvent/accounting/cierre');
            return;
        }

        $closingEntry = null;
        if ($period->closingEntryId) {
            $closingEntry = $this->entry_model->getEntryWithDetails($period->closingEntryId);
        }

        $data = array(
            'period' => $period,
            'closingEntry' => $closingEntry
        );

        $this->load->view('sisvent/accounting/cierre/view', $data);
    }

    /**
     * Reopen a closed period (Admin only)
     */
    public function reopen($id)
    {
        // Only admin can reopen
        $role = $this->session->userdata('user_data')['role'];
        if ($role != 1) {
            $this->session->set_flashdata('error', 'No tiene permisos para reabrir períodos.');
            redirect(base_url() . 'sisvent/accounting/cierre');
            return;
        }

        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $period = $this->accountingperiods_model->getPeriod($id);
        if (!$period || $period->status !== 'closed') {
            $this->session->set_flashdata('error', 'Período no encontrado o no está cerrado.');
            redirect(base_url() . 'sisvent/accounting/cierre');
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];

        // Start transaction
        $this->db->trans_start();

        // If there was a closing entry, mark it as anulado (but don't delete)
        if ($period->closingEntryId) {
            $this->entry_model->update($period->closingEntryId, array(
                'entryStatus' => 'anulado',
                'entryStatusComment' => 'Anulado por reapertura de período - ' . date('Y-m-d H:i:s')
            ));
        }

        // Reopen the period
        $this->accountingperiods_model->reopenPeriod($id, $userId);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->logs_model->logMessage("warning", "Usuario $userId reabrió el período #{$period->periodMonth}/{$period->periodYear}");
            $this->session->set_flashdata('success', 'Período reabierto exitosamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al reabrir el período.');
        }

        redirect(base_url() . 'sisvent/accounting/cierre/view/' . $id);
    }

    /**
     * Get month name in Spanish
     */
    private function getMonthName($month)
    {
        $months = array(
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        );
        return isset($months[$month]) ? $months[$month] : '';
    }
}
