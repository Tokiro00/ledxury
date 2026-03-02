<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subaccount_model extends CI_Model {

	public function getSubaccounts(){
		$this->db->select('subaccounts.*, accounts_accounts.accountName as accName, accounts_accounts.groupID as groupID, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, account_side.name as sideName, account_statement.name as statementName');
        $this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->join('account_side', 'account_side.id = subaccounts.accountSide');
        $this->db->join('account_statement', 'account_statement.id = subaccounts.accountStatement');
        $this->db->from('subaccounts');
		$this->db->where("subaccounts.deleted",0);
		$this->db->order_by("subaccounts.accountID", "asc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getExpenseSubaccounts(){
		$this->db->select('subaccounts.*, accounts_accounts.accountName as accName, accounts_accounts.groupID as groupID, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, account_side.name as sideName, account_statement.name as statementName');
        $this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->join('account_side', 'account_side.id = subaccounts.accountSide');
        $this->db->join('account_statement', 'account_statement.id = subaccounts.accountStatement');
        $this->db->from('subaccounts');
		$this->db->where("subaccounts.deleted", 0);
		$this->db->like('subaccounts.pucCode', '5', 'after');
		$this->db->order_by("subaccounts.accountID", "asc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getSubaccount($id){
		$this->db->select('subaccounts.*, accounts_accounts.accountName as accName, accounts_accounts.groupID as groupID, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, account_side.name as sideName, account_statement.name as statementName');
        $this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->join('account_side', 'account_side.id = subaccounts.accountSide');
        $this->db->join('account_statement', 'account_statement.id = subaccounts.accountStatement');
        $this->db->from('subaccounts');
		$this->db->where("subaccounts.id",$id);
		$this->db->where("subaccounts.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getSubaccountByAccountId($id){
		$this->db->select('subaccounts.*, accounts_accounts.accountName as accName, accounts_accounts.groupID as groupID, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, account_side.name as sideName, account_statement.name as statementName');
        $this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
        $this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
        $this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
        $this->db->join('account_side', 'account_side.id = subaccounts.accountSide');
        $this->db->join('account_statement', 'account_statement.id = subaccounts.accountStatement');
        $this->db->from('subaccounts');
		$this->db->where("subaccounts.accountID",$id);
		$this->db->where("subaccounts.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getSubaccountsByAccount($accountId){
		$this->db->select('subaccounts.*, account_side.name as sideName, account_statement.name as statementName');
		$this->db->join('account_side', 'account_side.id = subaccounts.accountSide');
		$this->db->join('account_statement', 'account_statement.id = subaccounts.accountStatement');
		$this->db->from('subaccounts');
		$this->db->where('subaccounts.accountAccount', $accountId);
		$this->db->where('subaccounts.deleted', 0);
		$this->db->order_by('subaccounts.accountID', 'asc');
		return $this->db->get()->result();
	}

	public function getStoreSubaccounts($storeid){
		$resultados = $this->db->query("SELECT * FROM `subaccounts` WHERE accountAccount IN (SELECT id FROM `accounts_accounts` WHERE groupID IN (SELECT id FROM `accounts_group` WHERE classID IN (SELECT id FROM `accounts_class` WHERE store='".$storeid."')))");
		
		return $resultados->result();
	}

	public function getClientSubaccountsByStore($storeid){
		$resultados = $this->db->query("SELECT * FROM `subaccounts` WHERE accountAccount IN (SELECT id FROM `accounts_accounts` WHERE groupID IN (SELECT id FROM `accounts_group` WHERE classID IN (SELECT id FROM `accounts_class` WHERE store='".$storeid."'))) AND `accountID`='130505'");
		
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $this->session->userdata('user_data')['uname'];
		return $this->db->insert("subaccounts",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("id",$id);
		return $this->db->update("subaccounts",$data);
	}
	public function remove($subaccount_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($subaccount_id,$data);
		//$this->db->where("accountID",$subaccount_id);
		//return $this->db->delete("subaccounts");
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

	/**
	 * Get subaccounts for Balance Sheet (statement=1) or Income Statement (statement=2)
	 * Grouped by class for financial reports
	 */
	public function getSubaccountsByStatement($statementId){
		$this->db->select('subaccounts.*, accounts_accounts.accountName as accName, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID, accounts_class.pucCode as classPucCode, account_side.name as sideName');
		$this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
		$this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
		$this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
		$this->db->join('account_side', 'account_side.id = subaccounts.accountSide');
		$this->db->from('subaccounts');
		$this->db->where('subaccounts.accountStatement', $statementId);
		$this->db->where('subaccounts.deleted', 0);
		$this->db->order_by('accounts_class.classID', 'ASC');
		$this->db->order_by('subaccounts.accountID', 'ASC');
		return $this->db->get()->result();
	}

	/**
	 * Get account classes for a statement type (1=Balance, 2=Resultados)
	 */
	public function getClassesByStatement($statementId){
		// For Balance Sheet (1): Classes 1, 2, 3
		// For Income Statement (2): Classes 4, 5, 6
		if ($statementId == '1') {
			$classes = array(1, 2, 3);
		} else {
			$classes = array(4, 5, 6);
		}

		$this->db->select('accounts_class.*');
		$this->db->from('accounts_class');
		$this->db->where_in('accounts_class.classID', $classes);
		$this->db->where('accounts_class.deleted', 0);
		$this->db->order_by('accounts_class.classID', 'ASC');
		return $this->db->get()->result();
	}

	/**
	 * Get total balance by class ID
	 */
	public function getTotalByClass($classId){
		$this->db->select_sum('subaccounts.accountBalance', 'total');
		$this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
		$this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
		$this->db->from('subaccounts');
		$this->db->where('accounts_group.classID', $classId);
		$this->db->where('subaccounts.deleted', 0);
		$result = $this->db->get()->row();
		return $result ? (float)$result->total : 0;
	}

	/**
	 * Get subaccount by PUC code and store
	 * Used for finding specific accounts like Utility/Loss
	 */
	public function getSubaccountByPucAndStore($pucCode, $storeId = null){
		$this->db->select('subaccounts.*, accounts_accounts.accountName as accName, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID');
		$this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
		$this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
		$this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
		$this->db->from('subaccounts');
		$this->db->where('subaccounts.pucCode', $pucCode);
		$this->db->where('subaccounts.deleted', 0);
		if ($storeId) {
			$this->db->where('subaccounts.store', $storeId);
		}
		$this->db->limit(1);
		return $this->db->get()->row();
	}

	/**
	 * Get subaccount by account code (accountID field)
	 */
	public function getSubaccountByCode($accountCode, $storeId = null){
		$this->db->select('subaccounts.*, accounts_accounts.accountName as accName, accounts_group.groupName as groupName, accounts_class.className as className, accounts_class.classID as classID');
		$this->db->join('accounts_accounts', 'subaccounts.accountAccount = accounts_accounts.id');
		$this->db->join('accounts_group', 'accounts_accounts.groupID = accounts_group.id');
		$this->db->join('accounts_class', 'accounts_class.id = accounts_group.classID');
		$this->db->from('subaccounts');
		$this->db->where('subaccounts.accountID', $accountCode);
		$this->db->where('subaccounts.deleted', 0);
		if ($storeId) {
			$this->db->where('subaccounts.store', $storeId);
		}
		$this->db->limit(1);
		return $this->db->get()->row();
	}
}