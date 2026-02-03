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
		$this->db->select_count_rows('total');
		$this->db->from('entries');
		$this->db->where('entryDate >=', $startDate);
		$this->db->where('entryDate <=', $endDate);
		if ($storeId) $this->db->where('entryStoreId', $storeId);
		$result = $this->db->get();
		return $result->row()->total;
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

}