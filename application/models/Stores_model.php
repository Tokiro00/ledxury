<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stores_model extends CI_Model {

	public function getStores(){
		$this->db->select('stores.*');
        $this->db->from('stores');
		$this->db->where("stores.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getStore($id){
		$this->db->select('stores.*');
        $this->db->from('stores');
		$this->db->where("stores.idStore",$id);
		$this->db->where("stores.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("stores",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idStore",$id);
		return $this->db->update("stores",$data);
	}
	public function remove($Store_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($Store_id,$data);
		//$this->db->where("idStore",$Store_id);
		//return $this->db->delete("stores");
	}
}