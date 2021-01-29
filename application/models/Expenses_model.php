<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses_model extends CI_Model {

	public function getExpenses(){
		$this->db->select('expenses.*');
        $this->db->from('expenses');
		$this->db->where("expenses.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getExpense($id){
		$this->db->select('expenses.*');
        $this->db->from('expenses');
		$this->db->where("expenses.idExpense",$id);
		$this->db->where("expenses.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("expenses",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idExpense",$id);
		return $this->db->update("expenses",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idExpense",$Store_id);
		//return $this->db->delete("expenses");
	}

	
}