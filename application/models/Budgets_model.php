<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budgets_model extends CI_Model {

	public function getBudgets($getOthers){
		$this->db->select('budgets.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
		$this->db->join('stores', 'budgets.storeId = stores.idStore');
        $this->db->from('budgets');
        if(!$getOthers)
        {
        	$this->db->where("budgets.vendorId",$this->session->userdata('user_data')['uname']);
        }
		$this->db->where("budgets.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getBudget($id){
		$this->db->select('budgets.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
		$this->db->join('stores', 'budgets.storeId = stores.idStore');
        $this->db->from('budgets');
		$this->db->where("budgets.idBudget",$id);
		$this->db->where("budgets.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("budgets",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idBudget",$id);
		return $this->db->update("budgets",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");

		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idBudget",$id);
		//return $this->db->delete("budgets");
	}

	public function lastID(){
		return $this->db->insert_id();
	}

	public function save_detail($data){
		return $this->db->insert("budget_detail",$data);
	}

	public function update_detail($idBudget,$idProduct,$data){
		$this->db->where("budgetId",$idBudget);
		$this->db->where("productId",$idProduct);
		return $this->db->update("budget_detail",$data);
	}

	public function getDetails($budgetId){
		$this->db->select('budget_detail.*, products.*, budget_detail.total as subtotal');
        $this->db->join('products', 'products.idProduct = budget_detail.productId');
        $this->db->from('budget_detail');
		$this->db->where("budget_detail.budgetId",$budgetId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function removeDetails($budgetId){
		$this->db->where("budget_detail.budgetId",$budgetId);
        $this->db->delete('budget_detail');
	}
}