<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accountclass_model extends CI_Model {

	public function getClasses(){
		$this->db->select('accounts_class.*,stores.name as store_name');
        $this->db->from('accounts_class')->join('stores', 'stores.idStore = accounts_class.store');
		$this->db->where("accounts_class.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getClass($id){
		$this->db->select('accounts_class.*,stores.name as store_name');
        $this->db->from('accounts_class')->join('stores', 'stores.idStore = accounts_class.store');
		$this->db->where("accounts_class.id",$id);
		$this->db->where("accounts_class.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("accounts_class",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("id",$id);
		return $this->db->update("accounts_class",$data);
	}
	public function remove($class_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($class_id,$data);
		//$this->db->where("classID",$Store_id);
		//return $this->db->delete("accounts_class");
	}
}