<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Entry_model extends CI_Model {

	public function getEntries($page = 1, $limit = 50){
		$this->db->select('entries.*, debiAcc.accountName as debitaccName, auxDebiAcc.accountName as debitauxaccName, crediAcc.accountName as creditaccName, auxCrediAcc.accountName as creditauxaccName');
        $this->db->join('subaccounts debiAcc', 'debiAcc.id = entries.entryDebitAccount');
        $this->db->join('auxiliary_subaccounts auxDebiAcc', 'auxDebiAcc.id = entries.entryDebitAuxaccount', 'left');
        $this->db->join('subaccounts crediAcc', 'crediAcc.id = entries.entryCreditAccount');
        $this->db->join('auxiliary_subaccounts auxCrediAcc', 'auxCrediAcc.id = entries.entryCreditAuxaccount', 'left');
        $this->db->from('entries');
		$this->db->order_by('entries.entryID', 'DESC');
		$offset = ($page - 1) * $limit;
		$this->db->limit($limit, $offset);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotalEntries(){
		$this->db->from('entries');
		return $this->db->count_all_results();
	}

	/**
	 * Get total entries with filters
	 */
	public function getTotalEntriesFiltered($filters = array()){
		$this->db->from('entries');
		if (!empty($filters['from'])) {
			$this->db->where('entries.entryDate >=', $filters['from']);
		}
		if (!empty($filters['to'])) {
			$this->db->where('entries.entryDate <=', $filters['to']);
		}
		if (!empty($filters['store'])) {
			$this->db->where('entries.entryStoreId', $filters['store']);
		}
		if (!empty($filters['type'])) {
			$this->db->where('entries.entryTransactionType', $filters['type']);
		}
		if (!empty($filters['cost_center'])) {
			$this->db->where('entries.cost_center_id', $filters['cost_center']);
		}
		return $this->db->count_all_results();
	}

	/**
	 * Get entries with filters and pagination
	 */
	public function getEntriesFiltered($filters = array(), $page = 1, $limit = 50){
		$this->db->select('entries.*, debiAcc.accountName as debitaccName, debiAcc.accountID as debitaccCode, auxDebiAcc.accountName as debitauxaccName, crediAcc.accountName as creditaccName, crediAcc.accountID as creditaccCode, auxCrediAcc.accountName as creditauxaccName, cc.name as costCenterName');
		$this->db->join('subaccounts debiAcc', 'debiAcc.id = entries.entryDebitAccount');
		$this->db->join('auxiliary_subaccounts auxDebiAcc', 'auxDebiAcc.id = entries.entryDebitAuxaccount', 'left');
		$this->db->join('subaccounts crediAcc', 'crediAcc.id = entries.entryCreditAccount');
		$this->db->join('auxiliary_subaccounts auxCrediAcc', 'auxCrediAcc.id = entries.entryCreditAuxaccount', 'left');
		$this->db->join('cost_centers cc', 'cc.id = entries.cost_center_id', 'left');
		$this->db->from('entries');

		if (!empty($filters['from'])) {
			$this->db->where('entries.entryDate >=', $filters['from']);
		}
		if (!empty($filters['to'])) {
			$this->db->where('entries.entryDate <=', $filters['to']);
		}
		if (!empty($filters['store'])) {
			$this->db->where('entries.entryStoreId', $filters['store']);
		}
		if (!empty($filters['type'])) {
			$this->db->where('entries.entryTransactionType', $filters['type']);
		}
		if (!empty($filters['cost_center'])) {
			$this->db->where('entries.cost_center_id', $filters['cost_center']);
		}

		$this->db->order_by('entries.entryDate', 'ASC');
		$this->db->order_by('entries.entryID', 'ASC');
		$offset = ($page - 1) * $limit;
		$this->db->limit($limit, $offset);
		return $this->db->get()->result();
	}

	/**
	 * Get entry with full details including store name
	 */
	public function getEntryWithDetails($id){
		$this->db->select('entries.*, debiAcc.accountName as debitaccName, debiAcc.accountID as debitaccCode, auxDebiAcc.accountName as debitauxaccName, crediAcc.accountName as creditaccName, crediAcc.accountID as creditaccCode, auxCrediAcc.accountName as creditauxaccName, stores.name as storeName');
		$this->db->join('subaccounts debiAcc', 'debiAcc.id = entries.entryDebitAccount');
		$this->db->join('auxiliary_subaccounts auxDebiAcc', 'auxDebiAcc.id = entries.entryDebitAuxaccount', 'left');
		$this->db->join('subaccounts crediAcc', 'crediAcc.id = entries.entryCreditAccount');
		$this->db->join('auxiliary_subaccounts auxCrediAcc', 'auxCrediAcc.id = entries.entryCreditAuxaccount', 'left');
		$this->db->join('stores', 'stores.idStore = entries.entryStoreId', 'left');
		$this->db->from('entries');
		$this->db->where('entries.entryID', $id);
		return $this->db->get()->row();
	}

	public function getEntry($id){
		$this->db->select('entries.*');
        $this->db->from('entries');
		$this->db->where("entries.entryID",$id);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		return $this->db->insert("entries",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$this->db->where("entryID",$id);
		return $this->db->update("entries",$data);
	}

	public function getEntriesByDateRange($startDate, $endDate, $storeId = null, $page = 1, $limit = 50){
		$this->db->select('entries.*, debiAcc.accountName as debitaccName, auxDebiAcc.accountName as debitauxaccName, crediAcc.accountName as creditaccName, auxCrediAcc.accountName as creditauxaccName');
		$this->db->join('subaccounts debiAcc', 'debiAcc.id = entries.entryDebitAccount');
		$this->db->join('auxiliary_subaccounts auxDebiAcc', 'auxDebiAcc.id = entries.entryDebitAuxaccount', 'left');
		$this->db->join('subaccounts crediAcc', 'crediAcc.id = entries.entryCreditAccount');
		$this->db->join('auxiliary_subaccounts auxCrediAcc', 'auxCrediAcc.id = entries.entryCreditAuxaccount', 'left');
		$this->db->from('entries');
		$this->db->where('entries.entryDate >=', $startDate);
		$this->db->where('entries.entryDate <=', $endDate);
		if ($storeId) $this->db->where('entries.entryStoreId', $storeId);
		$this->db->order_by('entries.entryDate', 'ASC');
		$this->db->order_by('entries.entryID', 'ASC');
		$offset = ($page - 1) * $limit;
		$this->db->limit($limit, $offset);
		return $this->db->get()->result();
	}

	public function getTotalByDateRange($startDate, $endDate, $storeId = null){
		//$this->db->num_rows('total');
		$this->db->from('entries');
		$this->db->where('entryDate >=', $startDate);
		$this->db->where('entryDate <=', $endDate);
		if ($storeId) $this->db->where('entryStoreId', $storeId);
		$result = $this->db->get();
		return $result->num_rows();
	}

	public function getTotalsByDateRange($startDate, $endDate, $storeId = null){
		$this->db->select_sum('entryDebitBalance', 'totalDebit');
		$this->db->select_sum('entryCreditBalance', 'totalCredit');
		$this->db->from('entries');
		$this->db->where('entryDate >=', $startDate);
		$this->db->where('entryDate <=', $endDate);
		if ($storeId) $this->db->where('entryStoreId', $storeId);
		return $this->db->get()->row();
	}

	public function getEntriesByAccount($accountId){
		$this->db->select('entries.*');
		$this->db->from('entries');
		$this->db->group_start();
		$this->db->where('entries.entryDebitAccount', $accountId);
		$this->db->or_where('entries.entryCreditAccount', $accountId);
		$this->db->group_end();
		$this->db->order_by('entries.entryDate', 'ASC');
		$this->db->order_by('entries.entryID', 'ASC');
		return $this->db->get()->result();
	}

	/**
	 * Get ledger entries for a specific account within a date range
	 * Returns entries with debit/credit amounts specific to this account
	 */
	public function getLedgerByAccount($accountId, $startDate = null, $endDate = null, $storeId = null){
		$accountId = (int)$accountId;
		$this->db->select("entries.entryID, entries.entryDate, entries.entryDescription,
			CASE WHEN entries.entryDebitAccount = {$accountId} THEN entries.entryDebitBalance ELSE 0 END as debit,
			CASE WHEN entries.entryCreditAccount = {$accountId} THEN entries.entryCreditBalance ELSE 0 END as credit,
			debiAcc.accountID as debitAccCode, debiAcc.accountName as debitAccName,
			crediAcc.accountID as creditAccCode, crediAcc.accountName as creditAccName", FALSE);
		$this->db->join('subaccounts debiAcc', 'debiAcc.id = entries.entryDebitAccount');
		$this->db->join('subaccounts crediAcc', 'crediAcc.id = entries.entryCreditAccount');
		$this->db->from('entries');
		$this->db->group_start();
		$this->db->where('entries.entryDebitAccount', $accountId);
		$this->db->or_where('entries.entryCreditAccount', $accountId);
		$this->db->group_end();

		if ($startDate) {
			$this->db->where('entries.entryDate >=', $startDate);
		}
		if ($endDate) {
			$this->db->where('entries.entryDate <=', $endDate);
		}
		if ($storeId) {
			$this->db->where('entries.entryStoreId', $storeId);
		}

		$this->db->order_by('entries.entryDate', 'ASC');
		$this->db->order_by('entries.entryID', 'ASC');
		return $this->db->get()->result();
	}

	/**
	 * Get opening balance for an account before a specific date
	 */
	public function getOpeningBalance($accountId, $beforeDate, $storeId = null){
		// Get sum of debits
		$this->db->select_sum('entryDebitBalance', 'totalDebit');
		$this->db->from('entries');
		$this->db->where('entryDebitAccount', $accountId);
		$this->db->where('entryDate <', $beforeDate);
		if ($storeId) $this->db->where('entryStoreId', $storeId);
		$debits = $this->db->get()->row()->totalDebit ?: 0;

		// Get sum of credits
		$this->db->select_sum('entryCreditBalance', 'totalCredit');
		$this->db->from('entries');
		$this->db->where('entryCreditAccount', $accountId);
		$this->db->where('entryDate <', $beforeDate);
		if ($storeId) $this->db->where('entryStoreId', $storeId);
		$credits = $this->db->get()->row()->totalCredit ?: 0;

		return array(
			'debits' => (float)$debits,
			'credits' => (float)$credits
		);
	}

	/**
	 * Get balance totals by account for a date range
	 * Used for Balance Sheet and Income Statement
	 */
	public function getBalancesByAccount($startDate = null, $endDate = null, $storeId = null, $costCenterId = null){
		// Get all debit totals by account
		$this->db->select('entryDebitAccount as accountId, SUM(entryDebitBalance) as totalDebit');
		$this->db->from('entries');
		if ($startDate) $this->db->where('entryDate >=', $startDate);
		if ($endDate) $this->db->where('entryDate <=', $endDate);
		if ($storeId) $this->db->where('entryStoreId', $storeId);
		if ($costCenterId) $this->db->where('cost_center_id', $costCenterId);
		$this->db->group_by('entryDebitAccount');
		$debits = $this->db->get()->result();

		// Get all credit totals by account
		$this->db->select('entryCreditAccount as accountId, SUM(entryCreditBalance) as totalCredit');
		$this->db->from('entries');
		if ($startDate) $this->db->where('entryDate >=', $startDate);
		if ($endDate) $this->db->where('entryDate <=', $endDate);
		if ($storeId) $this->db->where('entryStoreId', $storeId);
		if ($costCenterId) $this->db->where('cost_center_id', $costCenterId);
		$this->db->group_by('entryCreditAccount');
		$credits = $this->db->get()->result();

		// Combine into single array indexed by accountId
		$balances = array();
		foreach ($debits as $d) {
			if (!isset($balances[$d->accountId])) {
				$balances[$d->accountId] = array('debit' => 0, 'credit' => 0);
			}
			$balances[$d->accountId]['debit'] = (float)$d->totalDebit;
		}
		foreach ($credits as $c) {
			if (!isset($balances[$c->accountId])) {
				$balances[$c->accountId] = array('debit' => 0, 'credit' => 0);
			}
			$balances[$c->accountId]['credit'] = (float)$c->totalCredit;
		}

		return $balances;
	}

	/**
	 * Get all accounts that have movements in a date range
	 */
	/**
	 * Obtener asientos de apertura (type = apertura) por tienda
	 */
	public function getAperturaEntries($storeId = null) {
		$this->db->select('entries.*,
			debiAcc.accountName as debitaccName, debiAcc.accountID as debitaccCode, debiAcc.pucCode as debitPucCode,
			crediAcc.accountName as creditaccName, crediAcc.accountID as creditaccCode, crediAcc.pucCode as creditPucCode,
			debiAux.accountName as debitAuxName, crediAux.accountName as creditAuxName');
		$this->db->join('subaccounts debiAcc',  'debiAcc.id  = entries.entryDebitAccount');
		$this->db->join('subaccounts crediAcc', 'crediAcc.id = entries.entryCreditAccount');
		$this->db->join('auxiliary_subaccounts debiAux', 'debiAux.id = entries.entryDebitAuxaccount', 'left');
		$this->db->join('auxiliary_subaccounts crediAux', 'crediAux.id = entries.entryCreditAuxaccount', 'left');
		$this->db->from('entries');
		$this->db->where('entries.entryTransactionType', 'apertura');
		$this->db->where('entries.deleted', 0);
		if ($storeId) $this->db->where('entries.entryStoreId', $storeId);
		$this->db->order_by('entries.entryDate', 'ASC');
		$this->db->order_by('entries.entryID', 'ASC');
		return $this->db->get()->result();
	}

	public function getAccountsWithMovements($startDate = null, $endDate = null, $storeId = null){
		$sql = "SELECT DISTINCT s.id, s.accountID, s.accountName, s.accountSide, s.accountStatement,
				ac.className, ac.classID
				FROM entries e
				JOIN subaccounts s ON s.id = e.entryDebitAccount OR s.id = e.entryCreditAccount
				JOIN accounts_accounts aa ON s.accountAccount = aa.id
				JOIN accounts_group ag ON aa.groupID = ag.id
				JOIN accounts_class ac ON ag.classID = ac.id
				WHERE 1=1";

		if ($startDate) $sql .= " AND e.entryDate >= " . $this->db->escape($startDate);
		if ($endDate) $sql .= " AND e.entryDate <= " . $this->db->escape($endDate);
		if ($storeId) $sql .= " AND e.entryStoreId = " . $this->db->escape($storeId);

		$sql .= " ORDER BY s.accountID ASC";

		return $this->db->query($sql)->result();
	}

}