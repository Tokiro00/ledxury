<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auxsubaccount_model extends CI_Model {

	public function getAuxsubaccounts(){
		$this->db->select('auxiliary_subaccounts.*, subaccounts.accountName as accName, accounts_accounts.groupID as groupID, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, account_side.name as sideName, account_statement.name as statementName');
        $this->db->join('subaccounts', 'auxiliary_subaccounts.accountAccount = subaccounts.id');
        $this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->join('account_side', 'account_side.id = auxiliary_subaccounts.accountSide');
        $this->db->join('account_statement', 'account_statement.id = auxiliary_subaccounts.accountStatement');
        $this->db->from('auxiliary_subaccounts');
		$this->db->where("auxiliary_subaccounts.deleted",0);
		$this->db->order_by("auxiliary_subaccounts.accountID", "asc");

		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getAuxsubaccount($id){
		$this->db->select('auxiliary_subaccounts.*, subaccounts.accountName as accName, accounts_accounts.groupID as groupID, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, account_side.name as sideName, account_statement.name as statementName');
        $this->db->join('subaccounts', 'auxiliary_subaccounts.accountAccount = subaccounts.id');
        $this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->join('account_side', 'account_side.id = auxiliary_subaccounts.accountSide');
        $this->db->join('account_statement', 'account_statement.id = auxiliary_subaccounts.accountStatement');
        $this->db->from('auxiliary_subaccounts');
		$this->db->where("auxiliary_subaccounts.id",$id);
		$this->db->where("auxiliary_subaccounts.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}
	
	public function getAuxsubaccountByAccountId($id){
		$this->db->select('auxiliary_subaccounts.*, subaccounts.accountName as accName, accounts_accounts.groupID as groupID, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, account_side.name as sideName, account_statement.name as statementName');
        $this->db->join('subaccounts', 'auxiliary_subaccounts.accountAccount = subaccounts.id');
        $this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->join('account_side', 'account_side.id = auxiliary_subaccounts.accountSide');
        $this->db->join('account_statement', 'account_statement.id = auxiliary_subaccounts.accountStatement');
        $this->db->from('auxiliary_subaccounts');
		$this->db->where("auxiliary_subaccounts.accountID",$id);
		$this->db->where("auxiliary_subaccounts.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getAuxsubaccountsBySubaccount($subaccountId){
		$this->db->select('auxiliary_subaccounts.*, account_side.name as sideName, account_statement.name as statementName');
		$this->db->join('account_side', 'account_side.id = auxiliary_subaccounts.accountSide');
		$this->db->join('account_statement', 'account_statement.id = auxiliary_subaccounts.accountStatement');
		$this->db->from('auxiliary_subaccounts');
		$this->db->where('auxiliary_subaccounts.accountAccount', $subaccountId);
		$this->db->where('auxiliary_subaccounts.deleted', 0);
		$this->db->order_by('auxiliary_subaccounts.accountID', 'asc');
		return $this->db->get()->result();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $this->session->userdata('user_data')['uname'];
		return $this->db->insert("auxiliary_subaccounts",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("id",$id);
		return $this->db->update("auxiliary_subaccounts",$data);
	}
	public function remove($subaccount_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($subaccount_id,$data);
		//$this->db->where("accountID",$subaccount_id);
		//return $this->db->delete("auxiliary_subaccounts");
	}

	public function getAccountSides(){
		$this->db->select('account_side.*');
        $this->db->from('account_side');
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getAccountStatements(){
		$this->db->select('account_statement.*');
        $this->db->from('account_statement');
		$resultados = $this->db->get();
		return $resultados->result();
	}
}