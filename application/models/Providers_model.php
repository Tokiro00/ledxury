<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Providers_model extends CI_Model {

	public function getProviders(){
		$this->db->select('providers.*, IFNULL(providers.puc_code, "220501") as puc_code');
        $this->db->from('providers');
		$this->db->where("providers.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProvider($id){
		$this->db->select('providers.*');
        $this->db->from('providers');
		$this->db->where("providers.idProvider",$id);
		$this->db->where("providers.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("providers",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idProvider",$id);
		return $this->db->update("providers",$data);
	}
	public function remove($client_id){
		date_default_timezone_set("America/Bogota");

		$data  = array(
					'provider' => 1
				);
		$this->db->where("provider",$client_id);
		$this->db->update("products",$data);

		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($client_id,$data);
		//$this->db->where("idProvider",$client_id);
		//return $this->db->delete("providers");
	}
}