<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accountgroup_model extends CI_Model {

	public function getGroups(){
		$this->db->select('accounts_group.*');
        $this->db->from('accounts_group');
		$this->db->where("accounts_group.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getGroup($id){
		$this->db->select('accounts_group.*');
        $this->db->from('accounts_group');
		$this->db->where("accounts_group.idStore",$id);
		$this->db->where("accounts_group.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("accounts_group",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idStore",$id);
		return $this->db->update("accounts_group",$data);
	}
	public function remove($Store_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($Store_id,$data);
		//$this->db->where("idStore",$Store_id);
		//return $this->db->delete("accounts_group");
	}
}