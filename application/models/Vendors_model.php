<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vendors_model extends CI_Model {

	public function getVendors($admin_store = '', $getOthers = null){
		$this->db->select('users.*,stores.name as store_name');
        $this->db->from('users')->join('stores', 'stores.idStore = users.store');
		//$this->db->where("users.role",3);
		//$this->db->where("(users.role = '3' OR users.role = '2' OR users.role = '1')");
		if(!empty($admin_store))
        {
            $this->db->where_in("users.store",$admin_store);
        }
        if($getOthers == null || !$getOthers)
		{
			$this->db->where("users.idUser",$this->session->userdata('user_data')['uname']);
		}
		$this->db->where("users.archived",0);
		$this->db->where("users.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendor($id){
		$this->db->select('users.*,stores.name as store_name');
        $this->db->from('users')->join('stores', 'stores.idStore = users.store');
		//$this->db->where("users.role",3);
		//$this->db->where("(users.role = '3' OR users.role = '2' OR users.role = '1')");
		$this->db->where("users.idUser",$id);
		$this->db->where("users.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("users",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idUser",$id);
		return $this->db->update("users",$data);
	}
	public function remove($user_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($user_id,$data);
		//$this->db->where("idUser",$user_id);
		//return $this->db->delete("users");
	}
}