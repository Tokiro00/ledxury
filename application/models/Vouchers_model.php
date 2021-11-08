<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vouchers_model extends CI_Model {

	public function getVouchers($page = -1, $limit = 20){
		$this->db->select('vouchers.*,
			users.name as vendor_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = vouchers.paymentMethod');
        $this->db->from('vouchers');
		$this->db->where("vouchers.deleted",0);
		$this->db->order_by("vouchers.date", "desc");
		if($page != -1)
        	$this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function searchByWord($term, $page = 1, $limit = 20){
		$this->db->select('vouchers.*,
			users.name as vendor_name,
			paymentmethods.name as method_name');
        $this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = vouchers.paymentMethod');
        $this->db->from('vouchers');
        
		$this->db->where("vouchers.deleted",0);
       
        $this->db->like('users.name', $term);
     	$this->db->or_like('vouchers.idVoucher', $term);
     	$this->db->or_like('vouchers.value', $term);
     	$this->db->or_like('vouchers.userId', $term);
		$this->db->order_by("vouchers.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotalSearch($term) 
    {
        $this->db->join('users', 'users.idUser = vouchers.userId');
    	$this->db->from('vouchers');
    	
    	$this->db->where("vouchers.deleted",0);
    	$this->db->like('users.name', $term);
     	$this->db->or_like('vouchers.idVoucher', $term);
     	$this->db->or_like('vouchers.value', $term);
     	$this->db->or_like('vouchers.userId', $term);
        return $this->db->count_all_results();
    }

    public function getVendorVouchers($vendor){
		$this->db->select('vouchers.*,
			users.name as vendor_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = vouchers.paymentMethod');
        $this->db->from('vouchers');
        $this->db->where("vouchers.userId",$vendor);
		$this->db->where("vouchers.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendorPaidVouchers($vendor){
		$this->db->select('vouchers.*,
			users.name as vendor_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = vouchers.paymentMethod');
        $this->db->from('vouchers');
        $this->db->where("vouchers.userId",$vendor);
		$this->db->where("vouchers.state",1);
		$this->db->where("vouchers.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendorPaidVouchersTotal($vendor){
		$this->db->select('SUM(vouchers.value) as total');
		$this->db->from('vouchers');
        $this->db->where("vouchers.userId",$vendor);
		$this->db->where("vouchers.state",1);
		$this->db->where("vouchers.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getVoucher($id){
		$this->db->select('vouchers.*,
			users.name as vendor_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = vouchers.paymentMethod');
        $this->db->from('vouchers');
		$this->db->where("vouchers.idVoucher",$id);
		$this->db->where("vouchers.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getTotal() 
    {
    	$this->db->from('vouchers');
    	$this->db->where("vouchers.deleted",0);
        return $this->db->count_all_results();
    }
    
	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("vouchers",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idVoucher",$id);
		return $this->db->update("vouchers",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idVoucher",$Store_id);
		//return $this->db->delete("vouchers");
	}

	
}