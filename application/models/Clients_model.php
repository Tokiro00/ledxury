<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients_model extends CI_Model {

	public function getClients(){
		$this->db->select('clients.*,users.name as vendor_name');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor');
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getClient($id){
		$this->db->select('clients.*,users.name as vendor_name');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor');
		$this->db->where("clients.idClient",$id);
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("clients",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idClient",$id);
		return $this->db->update("clients",$data);
	}
	public function remove($client_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($client_id,$data);
		//$this->db->where("idClient",$client_id);
		//return $this->db->delete("clients");
	}
}