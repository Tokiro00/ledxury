<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounting Reports Controller
 *
 * Estados Financieros: Balance General, Estado de Resultados, Balance de Comprobación
 */
class Reports extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('reportes_contables'); // Admin
        $this->load->model('subaccount_model');
        $this->load->model('entry_model');
        $this->load->model('stores_model');
        $this->load->model('costcenters_model');
    }

    /**
     * Reports Index - List of available reports
     */
    public function index()
    {
        $this->load->view('sisvent/accounting/reports/index');
    }

    /**
     * Balance General (Balance Sheet)
     * Shows: Assets (1), Liabilities (2), Equity (3)
     */
    public function balance()
    {
        $endDate = $this->input->get('to') ?: date('Y-m-d');
        $storeId = $this->input->get('store');
        $costCenterId = $this->input->get('cost_center');

        $stores = $this->stores_model->getStores();
        $costcenters = $this->costcenters_model->getActiveCostCenters();

        // Get balances from entries up to end date
        $balances = $this->entry_model->getBalancesByAccount(null, $endDate, $storeId, $costCenterId);

        // Get all subaccounts for Balance Sheet (accountStatement = '1')
        $subaccounts = $this->subaccount_model->getSubaccountsByStatement('1');

        // Group by class and calculate balances from movements
        $groupedAccounts = array();
        foreach ($subaccounts as $acc) {
            $classId = $acc->classID;

            // Get movements for this account
            $debit = isset($balances[$acc->id]) ? $balances[$acc->id]['debit'] : 0;
            $credit = isset($balances[$acc->id]) ? $balances[$acc->id]['credit'] : 0;

            // Calculate balance based on account side
            if ($acc->accountSide == '1') {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            $acc->calculatedBalance = $balance;

            if (!isset($groupedAccounts[$classId])) {
                $groupedAccounts[$classId] = array(
                    'className' => $acc->className,
                    'classId'   => $classId,
                    'accounts'  => array(),
                    'total'     => 0
                );
            }
            $groupedAccounts[$classId]['accounts'][] = $acc;
            $groupedAccounts[$classId]['total'] += $balance;
        }

        // Calculate totals
        $totalActivos    = isset($groupedAccounts[1]) ? $groupedAccounts[1]['total'] : 0;
        $totalPasivos    = isset($groupedAccounts[2]) ? $groupedAccounts[2]['total'] : 0;
        $totalPatrimonio = isset($groupedAccounts[3]) ? $groupedAccounts[3]['total'] : 0;

        // Balance check: Assets = Liabilities + Equity
        $balanceCheck = $totalActivos - ($totalPasivos + $totalPatrimonio);

        $data = array(
            'stores' => $stores,
            'costcenters' => $costcenters,
            'groupedAccounts' => $groupedAccounts,
            'totalActivos'    => $totalActivos,
            'totalPasivos'    => $totalPasivos,
            'totalPatrimonio' => $totalPatrimonio,
            'balanceCheck'    => $balanceCheck,
            'reportDate'      => $endDate,
            'filter_to'       => $endDate,
            'filter_store'    => $storeId,
            'filter_cost_center' => $costCenterId
        );

        $this->load->view('sisvent/accounting/reports/balance', $data);
    }

    /**
     * Estado de Resultados (Income Statement / P&L)
     * Shows: Revenue (4), Expenses (5), Costs (6)
     */
    public function resultados()
    {
        $startDate = $this->input->get('from') ?: date('Y-01-01');
        $endDate = $this->input->get('to') ?: date('Y-m-d');
        $storeId = $this->input->get('store');
        $costCenterId = $this->input->get('cost_center');

        $stores = $this->stores_model->getStores();
        $costcenters = $this->costcenters_model->getActiveCostCenters();

        // Get balances for the period
        $balances = $this->entry_model->getBalancesByAccount($startDate, $endDate, $storeId, $costCenterId);

        // Get all subaccounts for Income Statement (accountStatement = '2')
        $subaccounts = $this->subaccount_model->getSubaccountsByStatement('2');

        // Group by class
        $groupedAccounts = array();
        foreach ($subaccounts as $acc) {
            $classId = $acc->classID;

            // Get movements for this account
            $debit = isset($balances[$acc->id]) ? $balances[$acc->id]['debit'] : 0;
            $credit = isset($balances[$acc->id]) ? $balances[$acc->id]['credit'] : 0;

            // Calculate balance based on account side
            if ($acc->accountSide == '1') {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            $acc->calculatedBalance = $balance;

            if (!isset($groupedAccounts[$classId])) {
                $groupedAccounts[$classId] = array(
                    'className' => $acc->className,
                    'classId'   => $classId,
                    'accounts'  => array(),
                    'total'     => 0
                );
            }
            $groupedAccounts[$classId]['accounts'][] = $acc;
            $groupedAccounts[$classId]['total'] += $balance;
        }

        // Calculate totals
        $totalIngresos = isset($groupedAccounts[4]) ? $groupedAccounts[4]['total'] : 0;
        $totalGastos   = isset($groupedAccounts[5]) ? $groupedAccounts[5]['total'] : 0;
        $totalCostos   = isset($groupedAccounts[6]) ? $groupedAccounts[6]['total'] : 0;

        // Utilidad Bruta = Ingresos - Costos
        $utilidadBruta = $totalIngresos - $totalCostos;

        // Net Income = Revenue - Costs - Expenses
        $utilidadNeta = $utilidadBruta - $totalGastos;

        $data = array(
            'stores' => $stores,
            'costcenters' => $costcenters,
            'groupedAccounts' => $groupedAccounts,
            'totalIngresos'   => $totalIngresos,
            'totalGastos'     => $totalGastos,
            'totalCostos'     => $totalCostos,
            'utilidadBruta'   => $utilidadBruta,
            'utilidadNeta'    => $utilidadNeta,
            'reportDate'      => date('Y-m-d'),
            'filter_from'     => $startDate,
            'filter_to'       => $endDate,
            'filter_store'    => $storeId,
            'filter_cost_center' => $costCenterId
        );

        $this->load->view('sisvent/accounting/reports/resultados', $data);
    }

    /**
     * Balance de Comprobación (Trial Balance)
     */
    public function comprobacion()
    {
        $startDate = $this->input->get('from') ?: date('Y-01-01');
        $endDate = $this->input->get('to') ?: date('Y-m-d');
        $storeId = $this->input->get('store');
        $costCenterId = $this->input->get('cost_center');

        $stores = $this->stores_model->getStores();
        $costcenters = $this->costcenters_model->getActiveCostCenters();

        // Get balances for the period
        $balances = $this->entry_model->getBalancesByAccount($startDate, $endDate, $storeId, $costCenterId);

        // Get all subaccounts
        $subaccounts = $this->subaccount_model->getSubaccounts();

        $trialData = array();
        $totalDebits = 0;
        $totalCredits = 0;
        $totalDebitBalance = 0;
        $totalCreditBalance = 0;

        foreach ($subaccounts as $acc) {
            $debit = isset($balances[$acc->id]) ? $balances[$acc->id]['debit'] : 0;
            $credit = isset($balances[$acc->id]) ? $balances[$acc->id]['credit'] : 0;

            // Skip accounts with no movements
            if ($debit == 0 && $credit == 0) continue;

            $acc->totalDebit = $debit;
            $acc->totalCredit = $credit;

            // Calculate balance based on account side
            if ($acc->accountSide == '1') {
                // Debit nature: balance = debits - credits
                $balance = $debit - $credit;
                $acc->debitBalance = $balance > 0 ? $balance : 0;
                $acc->creditBalance = $balance < 0 ? abs($balance) : 0;
            } else {
                // Credit nature: balance = credits - debits
                $balance = $credit - $debit;
                $acc->creditBalance = $balance > 0 ? $balance : 0;
                $acc->debitBalance = $balance < 0 ? abs($balance) : 0;
            }

            $trialData[] = $acc;
            $totalDebits += $debit;
            $totalCredits += $credit;
            $totalDebitBalance += $acc->debitBalance;
            $totalCreditBalance += $acc->creditBalance;
        }

        $data = array(
            'stores' => $stores,
            'costcenters' => $costcenters,
            'accounts' => $trialData,
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'totalDebitBalance' => $totalDebitBalance,
            'totalCreditBalance' => $totalCreditBalance,
            'filter_from' => $startDate,
            'filter_to' => $endDate,
            'filter_store' => $storeId,
            'filter_cost_center' => $costCenterId
        );

        $this->load->view('sisvent/accounting/reports/comprobacion', $data);
    }

    /**
     * Reporte de Inventario Valorizado
     * Muestra stock actual × costo por producto/bodega y compara con saldo contable
     */
    public function inventario()
    {
        $storeId = $this->input->get('store');
        $endDate = $this->input->get('to') ?: date('Y-m-d');

        $stores = $this->stores_model->getStores();

        $this->load->model('inventory_model');
        $this->load->model('products_model');
        $this->load->model('accountingsettings_model');

        // Get inventory account subaccount id
        $invSubaccountId = $this->accountingsettings_model->getSubaccountId('account_inventory');

        // Get accounting balance for inventory account up to endDate
        $accountingBalance = 0;
        if ($invSubaccountId) {
            $balances = $this->entry_model->getBalancesByAccount(null, $endDate, $storeId);
            if (isset($balances[$invSubaccountId])) {
                $accountingBalance = $balances[$invSubaccountId]['debit'] - $balances[$invSubaccountId]['credit'];
            }
        }

        // Get physical inventory with costs
        $sql = "SELECT i.idStore, i.idProduct, i.stock,
                       p.description, COALESCE(NULLIF(p.cost_cop,0), p.cost, 0) as cost_cop, p.family,
                       s.name as storeName,
                       pf.name as familyName,
                       (i.stock * COALESCE(NULLIF(p.cost_cop,0), p.cost, 0)) as valorTotal
                FROM inventory i
                INNER JOIN products p ON p.idProduct = i.idProduct AND p.deleted = 0
                INNER JOIN stores s ON s.idStore = i.idStore
                LEFT JOIN product_families pf ON pf.idFamily = p.family
                WHERE i.stock > 0";
        $params = array();
        if ($storeId) {
            $sql .= " AND i.idStore = ?";
            $params[] = $storeId;
        }
        $sql .= " ORDER BY s.name, pf.name, p.idProduct";
        $inventory = $this->db->query($sql, $params)->result();

        // Totals
        $totalStock = 0;
        $totalValue = 0;
        $totalItems = count($inventory);
        $storeBreakdown = array();

        foreach ($inventory as $item) {
            $totalStock += (int)$item->stock;
            $totalValue += (float)$item->valorTotal;

            $sid = $item->idStore;
            if (!isset($storeBreakdown[$sid])) {
                $storeBreakdown[$sid] = array(
                    'name' => $item->storeName,
                    'items' => 0,
                    'stock' => 0,
                    'value' => 0
                );
            }
            $storeBreakdown[$sid]['items']++;
            $storeBreakdown[$sid]['stock'] += (int)$item->stock;
            $storeBreakdown[$sid]['value'] += (float)$item->valorTotal;
        }

        $difference = $totalValue - $accountingBalance;

        // Get accounting entry detail (ledger) for the inventory account
        $ledgerEntries = array();
        $invAccountName = '';
        $invAccountCode = '';
        if ($invSubaccountId) {
            $ledgerEntries = $this->entry_model->getLedgerByAccount($invSubaccountId, null, $endDate, $storeId);
            // Get account info
            $invAccount = $this->subaccount_model->getSubaccount($invSubaccountId);
            if ($invAccount) {
                $invAccountName = $invAccount->accountName;
                $invAccountCode = $invAccount->accountID;
            }
        }

        $data = array(
            'stores'            => $stores,
            'inventory'         => $inventory,
            'totalItems'        => $totalItems,
            'totalStock'        => $totalStock,
            'totalValue'        => $totalValue,
            'accountingBalance' => $accountingBalance,
            'difference'        => $difference,
            'storeBreakdown'    => $storeBreakdown,
            'ledgerEntries'     => $ledgerEntries,
            'invAccountName'    => $invAccountName,
            'invAccountCode'    => $invAccountCode,
            'filter_store'      => $storeId,
            'filter_to'         => $endDate
        );

        $this->load->view('sisvent/accounting/reports/inventario', $data);
    }
}
