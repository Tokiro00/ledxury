<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transfers_model extends CI_Model {

	public function getTransfersss(){
		$resultados = $this->db->query('SELECT t2.*,
			u.name as user_name, 
			storeso.name as origin_name, 
			storesd.name as destination_name     
			FROM   transfers t2
			LEFT   JOIN stores storeso ON storeso.idStore = t2.originId
			LEFT   JOIN stores storesd ON storesd.idStore = t2.destinationId
			LEFT   JOIN users u ON u.idUser = t2.userId');
		
		return $resultados->result();
	}

	public function getTransfers(){
		$this->db->select('t2.*,
			u.name as user_name, 
			storeso.name as origin_name, 
			storesd.name as destination_name');
		$this->db->join('users u', 'u.idUser = t2.userId');
		$this->db->join('stores storeso', 'storeso.idStore = t2.originId');
		$this->db->join('stores storesd', 'storesd.idStore = t2.destinationId');
        $this->db->from('transfers t2');
		$this->db->where("t2.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTransfer($id){
		$this->db->select('t2.*,
			u.name as user_name, 
			storeso.name as origin_name, 
			storesd.name as destination_name');
		$this->db->join('users u', 'u.idUser = t2.userId');
		$this->db->join('stores storeso', 'storeso.idStore = t2.originId');
		$this->db->join('stores storesd', 'storesd.idStore = t2.destinationId');
        $this->db->from('transfers t2');
		$this->db->where("t2.idTransfer",$id);
		$this->db->where("t2.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("transfers",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idTransfer",$id);
		return $this->db->update("transfers",$data);
	}
	public function remove($transfer_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($transfer_id,$data);
		//$this->db->where("idTransfer",$transfer_id);
		//return $this->db->delete("transfers");
	}
	public function lastID(){
		return $this->db->insert_id();
	}

	public function save_detail($data){
		return $this->db->insert("transfer_details",$data);
	}

	public function getDetails($idTransfer){
		$this->db->select('transfer_details.*, products.*');
        $this->db->join('products', 'products.idProduct = transfer_details.idProduct');
        $this->db->from('transfer_details');
		$this->db->where("transfer_details.idTransfer",$idTransfer);
        $resultados = $this->db->get();
		return $resultados->result();
	}
}