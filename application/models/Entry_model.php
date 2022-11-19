<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Entry_model extends CI_Model {

	public function getEntries(){
		$this->db->select('entries.*, debiAcc.accountName as debitaccName, auxDebiAcc.accountName as debitauxaccName, crediAcc.accountName as creditaccName, auxCrediAcc.accountName as creditauxaccName');
        $this->db->join('subaccounts debiAcc', 'debiAcc.id = entries.entryDebitAccount');
        $this->db->join('auxiliary_subaccounts auxDebiAcc', 'auxDebiAcc.id = entries.entryDebitAuxaccount', 'left');
        $this->db->join('subaccounts crediAcc', 'crediAcc.id = entries.entryCreditAccount');
        $this->db->join('auxiliary_subaccounts auxCrediAcc', 'auxCrediAcc.id = entries.entryCreditAuxaccount', 'left');
        $this->db->from('entries');
		$resultados = $this->db->get();
		return $resultados->result();
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
	
}