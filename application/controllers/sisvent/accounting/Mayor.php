<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mayor extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('contabilidad');
        $this->load->model('entry_model');
        $this->load->model('subaccount_model');
    }

    public function index()
    {
        $accountId = $this->input->get('account');
        $from      = $this->input->get('from');
        $to        = $this->input->get('to');

        if (!$from) $from = date('Y-m-01');
        if (!$to)   $to   = date('Y-m-d');

        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';

        $account          = null;
        $filteredEntries  = array();
        $openingBalance   = 0.0;
        $closingBalance   = 0.0;

        if ($accountId) {
            $account = $this->subaccount_model->getSubaccount($accountId);

            if ($account) {
                $allEntries     = $this->entry_model->getEntriesByAccount($accountId);
                $accountSide    = $account->accountSide; // '1' = débito, '2' = crédito

                $runningBalance = 0.0;
                $openingBalance = 0.0;

                foreach ($allEntries as $entry) {
                    $entryDt = $entry->entryDate . ' 00:00:00';

                    // Determinar monto y dirección en este asiento
                    if ($entry->entryDebitAccount == $accountId) {
                        $amount = (float)$entry->entryDebitBalance;
                        $movType = 'debit';
                    } else {
                        $amount = (float)$entry->entryCreditBalance;
                        $movType = 'credit';
                    }

                    // Calcular movimiento según naturaleza de la cuenta
                    if ($accountSide == '1') {
                        // Cuenta de naturaleza débito: débitos suman, créditos restan
                        $movement = ($movType == 'debit') ? $amount : -$amount;
                    } else {
                        // Cuenta de naturaleza crédito: créditos suman, débitos restan
                        $movement = ($movType == 'credit') ? $amount : -$amount;
                    }

                    if ($entryDt < $fromDt) {
                        $runningBalance += $movement;
                        $openingBalance = $runningBalance;
                    } elseif ($entryDt <= $toDt) {
                        $runningBalance += $movement;
                        $entry->movType    = $movType;
                        $entry->movAmount  = $amount;
                        $entry->movement   = $movement;
                        $entry->runningBalance = $runningBalance;
                        $filteredEntries[] = $entry;
                    }
                }

                $closingBalance = $runningBalance;
            }
        }

        // Lista de subcuentas para el selector
        $subaccounts = $this->subaccount_model->getSubaccounts();

        $data = array(
            'subaccounts'    => $subaccounts,
            'account'        => $account,
            'accountId'      => $accountId,
            'entries'        => $filteredEntries,
            'from'           => $from,
            'to'             => $to,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance
        );

        $this->load->view('sisvent/accounting/mayor/list', $data);
    }
}