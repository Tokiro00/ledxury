<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Financial Dashboard Controller
 * Provides a comprehensive view of the company's financial status
 */
class Financialdashboard extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 2, 4]); // Admin, Gerente, Contador
        $this->load->model('cashboxes_model');
        $this->load->model('bankaccounts_model');
        $this->load->model('cashmovements_model');
        $this->load->model('invoices_model');
        $this->load->model('entry_model');
        $this->load->model('subaccount_model');
        $this->load->model('supplierbills_model');
        $this->load->model('expenserecords_model');
    }

    public function index()
    {
        date_default_timezone_set("America/Bogota");
        $today = date('Y-m-d');
        $todayStart = $today . ' 00:00:00';
        $todayEnd = $today . ' 23:59:59';
        $monthStart = date('Y-m-01');
        $yearStart = date('Y-01-01');

        // ====== CASH AND BANKS ======
        // Get all active cashboxes with today's movements
        $cashboxes = $this->cashboxes_model->getActiveCashboxes();
        $totalCashboxBalance = 0;
        foreach ($cashboxes as $cb) {
            $totals = $this->cashmovements_model->getTotalsBySource('caja', $cb->idCashbox, $todayStart, $todayEnd);
            $cb->todayIngress = $totals->totalIngress ?: 0;
            $cb->todayEgress = $totals->totalEgress ?: 0;
            $totalCashboxBalance += $cb->currentBalance;
        }

        // Get all active bank accounts with today's movements
        $bankAccounts = $this->bankaccounts_model->getActiveBankAccounts();
        $totalBankBalance = 0;
        foreach ($bankAccounts as $bank) {
            $totals = $this->cashmovements_model->getTotalsBySource('banco', $bank->idBankAccount, $todayStart, $todayEnd);
            $bank->todayIngress = $totals->totalIngress ?: 0;
            $bank->todayEgress = $totals->totalEgress ?: 0;
            $totalBankBalance += $bank->currentBalance;
        }

        $totalLiquidity = $totalCashboxBalance + $totalBankBalance;

        // ====== ACCOUNTS RECEIVABLE ======
        $receivableAging = $this->invoices_model->getAccountsReceivableAging();
        $topDebtors = $this->invoices_model->getAccountsReceivableByClient(null, null);
        $topDebtors = array_slice($topDebtors, 0, 5); // Top 5 debtors

        // ====== ACCOUNTS PAYABLE ======
        $payableAging = $this->supplierbills_model->getAgingReport();
        $payableTotal = $payableAging['total'];

        // ====== RECENT MOVEMENTS ======
        $recentMovements = $this->cashmovements_model->getMovements(array(), 1, 10);

        // ====== MONTHLY SUMMARY ======
        $monthlyIngress = $this->cashmovements_model->getTotalsByDateRange($monthStart, $today, 'ingreso');
        $monthlyEgress = $this->cashmovements_model->getTotalsByDateRange($monthStart, $today, 'egreso');

        // ====== EXPENSES ======
        $monthlyExpenses = $this->expenserecords_model->getMonthlyTotal();
        $pendingExpenses = $this->expenserecords_model->getPendingTotal();
        $recentExpenses = $this->expenserecords_model->getRecentExpenses(5);

        // ====== QUICK RATIOS ======
        // Current Ratio = Current Assets / Current Liabilities (simplified)
        $currentRatio = $receivableAging['total'] > 0
            ? ($totalLiquidity + $receivableAging['total']) / max($payableTotal, 1)
            : 0;

        $data = array(
            // Cash & Banks
            'cashboxes' => $cashboxes,
            'bankAccounts' => $bankAccounts,
            'totalCashboxBalance' => $totalCashboxBalance,
            'totalBankBalance' => $totalBankBalance,
            'totalLiquidity' => $totalLiquidity,

            // Accounts Receivable
            'receivableAging' => $receivableAging,
            'topDebtors' => $topDebtors,

            // Accounts Payable
            'payableAging' => $payableAging,
            'payableTotal' => $payableTotal,

            // Movements
            'recentMovements' => $recentMovements,
            'monthlyIngress' => $monthlyIngress,
            'monthlyEgress' => $monthlyEgress,

            // Expenses
            'monthlyExpenses' => $monthlyExpenses,
            'pendingExpenses' => $pendingExpenses,
            'recentExpenses' => $recentExpenses,

            // Dates
            'today' => $today,
            'monthStart' => $monthStart
        );

        $this->load->view('sisvent/admin/financialdashboard/index', $data);
    }
}
