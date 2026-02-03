<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounting Reports Controller
 *
 * Estados Financieros: Balance General y Estado de Resultados
 */
class Reports extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 4]);
        $this->load->model('subaccount_model');
    }

    /**
     * Balance General (Balance Sheet)
     * Shows: Assets (1), Liabilities (2), Equity (3)
     */
    public function balance()
    {
        // Get all subaccounts for Balance Sheet (accountStatement = '1')
        $subaccounts = $this->subaccount_model->getSubaccountsByStatement('1');

        // Group by class
        $groupedAccounts = array();
        foreach ($subaccounts as $acc) {
            $classId = $acc->classID;
            if (!isset($groupedAccounts[$classId])) {
                $groupedAccounts[$classId] = array(
                    'className' => $acc->className,
                    'classId'   => $classId,
                    'accounts'  => array(),
                    'total'     => 0
                );
            }
            $groupedAccounts[$classId]['accounts'][] = $acc;
            $groupedAccounts[$classId]['total'] += (float)$acc->accountBalance;
        }

        // Calculate totals
        $totalActivos    = isset($groupedAccounts[1]) ? $groupedAccounts[1]['total'] : 0;
        $totalPasivos    = isset($groupedAccounts[2]) ? $groupedAccounts[2]['total'] : 0;
        $totalPatrimonio = isset($groupedAccounts[3]) ? $groupedAccounts[3]['total'] : 0;

        // Balance check: Assets = Liabilities + Equity
        $balanceCheck = $totalActivos - ($totalPasivos + $totalPatrimonio);

        $data = array(
            'groupedAccounts' => $groupedAccounts,
            'totalActivos'    => $totalActivos,
            'totalPasivos'    => $totalPasivos,
            'totalPatrimonio' => $totalPatrimonio,
            'balanceCheck'    => $balanceCheck,
            'reportDate'      => date('Y-m-d')
        );

        $this->load->view('sisvent/accounting/reports/balance', $data);
    }

    /**
     * Estado de Resultados (Income Statement / P&L)
     * Shows: Revenue (4), Expenses (5), Costs (6)
     */
    public function resultados()
    {
        // Get all subaccounts for Income Statement (accountStatement = '2')
        $subaccounts = $this->subaccount_model->getSubaccountsByStatement('2');

        // Group by class
        $groupedAccounts = array();
        foreach ($subaccounts as $acc) {
            $classId = $acc->classID;
            if (!isset($groupedAccounts[$classId])) {
                $groupedAccounts[$classId] = array(
                    'className' => $acc->className,
                    'classId'   => $classId,
                    'accounts'  => array(),
                    'total'     => 0
                );
            }
            $groupedAccounts[$classId]['accounts'][] = $acc;
            $groupedAccounts[$classId]['total'] += (float)$acc->accountBalance;
        }

        // Calculate totals
        $totalIngresos = isset($groupedAccounts[4]) ? $groupedAccounts[4]['total'] : 0;
        $totalGastos   = isset($groupedAccounts[5]) ? $groupedAccounts[5]['total'] : 0;
        $totalCostos   = isset($groupedAccounts[6]) ? $groupedAccounts[6]['total'] : 0;

        // Net Income = Revenue - Expenses - Costs
        $utilidadNeta = $totalIngresos - $totalGastos - $totalCostos;

        $data = array(
            'groupedAccounts' => $groupedAccounts,
            'totalIngresos'   => $totalIngresos,
            'totalGastos'     => $totalGastos,
            'totalCostos'     => $totalCostos,
            'utilidadNeta'    => $utilidadNeta,
            'reportDate'      => date('Y-m-d')
        );

        $this->load->view('sisvent/accounting/reports/resultados', $data);
    }
}
