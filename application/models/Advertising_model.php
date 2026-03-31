<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Advertising_model extends CI_Model {

	public function getAdvertisingAmountSinceUntil($since = "", $until = ""){
		$this->db->select('advertising.*, SUM(advertising.amount) as amount,
			users.name as vendor_name');
        $this->db->join('users', 'users.idUser = advertising.vendor');
        $this->db->from('advertising');
        //$this->db->where("advertising.vendor",$vendor);
        if(!empty($since))
        {
	        $this->db->where('advertising.date >=', date('Y-m-d H:i:s',strtotime($since)));
	    }
	    if(!empty($until))
        {
	        $this->db->where('advertising.date <=', date('Y-m-d H:i:s',strtotime($until)));
	    }
		$this->db->group_by("advertising.vendor");
		$this->db->order_by("advertising.date", "asc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendorAdvertisingAmountSinceUntil($vendor, $since, $until){
		$this->db->select('advertising.*,
			users.name as vendor_name');
        $this->db->join('users', 'users.idUser = advertising.vendor');
        $this->db->from('advertising');
        $this->db->where("advertising.vendor",$vendor);
        $this->db->where('advertising.date >=', date('Y-m-d H:i:s',strtotime($since)));
        $this->db->where('advertising.date <=', date('Y-m-d H:i:s',strtotime($until)));
		$this->db->order_by("advertising.date", "asc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendorTotalAdvertisingAmountSinceUntil($vendor, $since, $until){
		$this->db->select('SUM(advertising.amount) as amount,
			users.name as vendor_name');
        $this->db->join('users', 'users.idUser = advertising.vendor');
        $this->db->from('advertising');
        $this->db->where("advertising.vendor",$vendor);
        $this->db->where('advertising.date >=', date('Y-m-d H:i:s',strtotime($since)));
        $this->db->where('advertising.date <=', date('Y-m-d H:i:s',strtotime($until)));
		$this->db->order_by("advertising.date", "asc");
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getEntry($id){
		$this->db->select('advertising.*');
        $this->db->from('advertising');
		$this->db->where("advertising.id",$id);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		return $this->db->insert("advertising",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$this->db->where("id",$id);
		return $this->db->update("advertising",$data);
	}
	
}