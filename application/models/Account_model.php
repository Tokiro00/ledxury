<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account_model extends CI_Model {

	public function getAccounts(){
		$this->db->select('accounts_accounts.*, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->from('accounts_accounts');
		$this->db->where("accounts_accounts.deleted",0);
		$this->db->order_by("accounts_accounts.accountID", "asc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getAccount($id){
		$this->db->select('accounts_accounts.*, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->from('accounts_accounts');
		$this->db->where("accounts_accounts.id",$id);
		$this->db->where("accounts_accounts.deleted",0);
		$this->db->order_by("accounts_accounts.accountID", "asc");
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getAccountsByGroup($groupId){
		$this->db->select('accounts_accounts.*');
		$this->db->from('accounts_accounts');
		$this->db->where('accounts_accounts.groupID', $groupId);
		$this->db->where('accounts_accounts.deleted', 0);
		$this->db->order_by('accounts_accounts.accountID', 'asc');
		return $this->db->get()->result();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("accounts_accounts",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("id",$id);
		return $this->db->update("accounts_accounts",$data);
	}
	public function remove($account_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($account_id,$data);
		//$this->db->where("accountID",$account_id);
		//return $this->db->delete("accounts_accounts");
	}
}