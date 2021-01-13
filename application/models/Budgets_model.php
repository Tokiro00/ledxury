<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class budgets_model extends CI_Model {

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
        	$this->db->where("budgets.clientId",$this->session->userdata('user_data')['uname']);
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
}