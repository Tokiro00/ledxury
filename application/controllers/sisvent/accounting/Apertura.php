<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Apertura de Balance (Opening Balance)
 * Records initial balances for subaccounts via journal entries
 */
class Apertura extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->backend_lib->controlModule('apertura');
		$this->load->model("entry_model");
		$this->load->model("subaccount_model");
		$this->load->model("stores_model");
		$this->load->model("cashboxes_model");
		$this->load->model("bankaccounts_model");
		$this->load->model("accountingperiods_model");
	}

	public function index()
	{
		$storeId = $this->input->get('store');

		// Get subaccounts for Balance Sheet (classes 1,2,3 - Activos, Pasivos, Patrimonio)
		$subaccounts = $this->subaccount_model->getSubaccounts();

		// Group subaccounts by class for display
		$grouped = array();
		foreach ($subaccounts as $sub) {
			// Only show Balance Sheet accounts (classes 1, 2, 3)
			if (!isset($sub->classID) || $sub->classID > 3) continue;
			$className = $sub->className;
			if (!isset($grouped[$className])) {
				$grouped[$className] = array(
					'classID' => $sub->classID,
					'className' => $className,
					'subaccounts' => array()
				);
			}
			$grouped[$className]['subaccounts'][] = $sub;
		}

		// Sort by classID
		uasort($grouped, function($a, $b) {
			return $a['classID'] - $b['classID'];
		});

		// Get existing apertura entries for this store
		$aperturaEntries = $this->entry_model->getAperturaEntries($storeId);

		// Index existing entries by debit/credit account for quick lookup
		$existingBalances = array();
		foreach ($aperturaEntries as $entry) {
			if (!isset($existingBalances[$entry->entryDebitAccount])) {
				$existingBalances[$entry->entryDebitAccount] = array('debit' => 0, 'credit' => 0);
			}
			$existingBalances[$entry->entryDebitAccount]['debit'] += $entry->entryDebitBalance;

			if (!isset($existingBalances[$entry->entryCreditAccount])) {
				$existingBalances[$entry->entryCreditAccount] = array('debit' => 0, 'credit' => 0);
			}
			$existingBalances[$entry->entryCreditAccount]['credit'] += $entry->entryCreditBalance;
		}

		$data = array(
			'grouped' => $grouped,
			'stores' => $this->stores_model->getStores(),
			'filter_store' => $storeId,
			'existingBalances' => $existingBalances,
			'aperturaEntries' => $aperturaEntries,
		);
		$this->load->view("sisvent/accounting/apertura/index", $data);
	}

	/**
	 * Save opening balance entries
	 * Creates journal entries of type 'apertura'
	 */
	public function save()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$storeId = $this->input->post('storeId');
		$entryDate = $this->input->post('entryDate');
		$accounts = $this->input->post('accounts'); // array of account_id => amount
		$types = $this->input->post('types');       // array of account_id => 'debit' or 'credit'
		$userId = $this->session->userdata('user_data')['uname'];

		if (!$entryDate || empty($accounts)) {
			$this->session->set_flashdata('error', 'Debe completar la fecha y al menos un saldo inicial.');
			redirect(base_url() . 'sisvent/accounting/apertura?store=' . $storeId);
			return;
		}

		// Validate period is not closed
		$year = date('Y', strtotime($entryDate));
		$month = date('n', strtotime($entryDate));
		if ($this->accountingperiods_model->isPeriodClosed($year, $month, $storeId)) {
			$this->session->set_flashdata('error', 'No se pueden crear asientos en un periodo cerrado (' . $month . '/' . $year . ').');
			redirect(base_url() . 'sisvent/accounting/apertura?store=' . $storeId);
			return;
		}

		$this->db->trans_start();

		$entriesCreated = 0;

		foreach ($accounts as $accountId => $amount) {
			$amount = floatval(str_replace(',', '', $amount));
			if ($amount <= 0) continue;

			$type = isset($types[$accountId]) ? $types[$accountId] : 'debit';

			// Get the subaccount to determine its nature
			$subaccount = $this->db->select('id, accountSide, accountName, accountID')
				->from('subaccounts')
				->where('id', $accountId)
				->get()->row();

			if (!$subaccount) continue;

			// For apertura entries, we need a contra account.
			// Use account 3115 (Capital Social / Aportes Sociales) or similar patrimonio account
			// as the balancing entry for opening balances
			$contraAccount = $this->getContraAccount($subaccount, $storeId);
			if (!$contraAccount) continue;

			if ($type == 'debit') {
				$debitAccountId = $accountId;
				$creditAccountId = $contraAccount->id;
			} else {
				$debitAccountId = $contraAccount->id;
				$creditAccountId = $accountId;
			}

			$entryData = array(
				'userID' => $userId,
				'entryDescription' => 'Apertura de balance - ' . $subaccount->accountName,
				'entryDate' => $entryDate,
				'entryStoreId' => $storeId,
				'entryType' => 'Apertura',
				'entryTransactionType' => 'apertura',
				'entryTransactionId' => null,
				'entryDebitAccount' => $debitAccountId,
				'entryDebitAuxaccount' => null,
				'entryDebitBalance' => $amount,
				'entryCreditAccount' => $creditAccountId,
				'entryCreditAuxaccount' => null,
				'entryCreditBalance' => $amount,
				'entryStatus' => 'activo',
				'created_by' => $userId,
				'entryCreateDate' => date('Y-m-d H:i:s'),
				'deleted' => 0
			);

			$this->entry_model->save($entryData);

			// Update subaccount balances
			$this->updateAccountBalance($accountId, $amount, $type);
			$this->updateAccountBalance($contraAccount->id, $amount, ($type == 'debit') ? 'credit' : 'debit');

			$entriesCreated++;
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() && $entriesCreated > 0) {
			$this->logs_model->logMessage("info", "Usuario $userId creo $entriesCreated asientos de apertura");
			$this->session->set_flashdata('success', "Se crearon $entriesCreated asientos de apertura exitosamente.");
		} elseif ($entriesCreated == 0) {
			$this->session->set_flashdata('error', 'No se encontraron saldos validos para crear asientos.');
		} else {
			$this->session->set_flashdata('error', 'Error al crear los asientos de apertura.');
		}

		redirect(base_url() . 'sisvent/accounting/apertura?store=' . $storeId);
	}

	/**
	 * Get the contra account for opening balance entries.
	 * For assets (class 1): contra is a patrimonio account (class 3)
	 * For liabilities (class 2): contra is a patrimonio account (class 3)
	 * For patrimonio (class 3): contra is itself or another patrimonio sub
	 */
	private function getContraAccount($subaccount, $storeId)
	{
		// Try to find an "Apertura" or "Ajustes de periodos anteriores" subaccount
		$contra = $this->db->select('subaccounts.id, subaccounts.accountSide')
			->from('subaccounts')
			->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id')
			->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id')
			->join('accounts_class', 'accounts_class.id = accounts_group.classID')
			->where('accounts_class.classID', 3)
			->where('subaccounts.deleted', 0)
			->order_by('subaccounts.accountID', 'asc')
			->limit(1)
			->get()->row();

		return $contra;
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
			// Debit nature account
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
			// Credit nature account
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
}
