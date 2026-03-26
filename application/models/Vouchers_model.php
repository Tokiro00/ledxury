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
       
		$this->db->group_start(); // Start of the bracketed group
        $this->db->like('users.name', $term);
     	$this->db->or_like('vouchers.idVoucher', $term);
     	$this->db->or_like('vouchers.value', $term);
     	$this->db->or_like('vouchers.userId', $term);
		$this->db->group_end(); // End of the bracketed group
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
		$this->db->group_start(); // Start of the bracketed group
    	$this->db->like('users.name', $term);
     	$this->db->or_like('vouchers.idVoucher', $term);
     	$this->db->or_like('vouchers.value', $term);
     	$this->db->or_like('vouchers.userId', $term);
 		$this->db->group_end(); // End of the bracketed group
        return $this->db->count_all_results();
    }

    public function getVendorVouchers($vendor, $since = "", $until = ""){
		$this->db->select('vouchers.*,
			users.name as vendor_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = vouchers.paymentMethod');
        $this->db->from('vouchers');
        $this->db->where("vouchers.userId",$vendor);
        if(!empty($since))
        {
        	$this->db->where('vouchers.date >=', date('Y-m-d H:i:s',strtotime($since)));
        }
        if(!empty($until))
        {
			$this->db->where('vouchers.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
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

	public function getTotal($filters = array())
    {
    	$this->db->from('vouchers');
    	$this->db->join('users', 'users.idUser = vouchers.userId');
    	$this->db->where("vouchers.deleted",0);
    	$this->applyFilters($filters);
        return $this->db->count_all_results();
    }

    public function getFilteredVouchers($page = 1, $limit = 50, $filters = array())
    {
        $this->db->select('vouchers.*, users.name as vendor_name, paymentmethods.name as method_name');
        $this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = vouchers.paymentMethod');
        $this->db->from('vouchers');
        $this->db->where("vouchers.deleted", 0);
        $this->applyFilters($filters);
        $this->db->order_by("vouchers.userId", "asc");
        $this->db->order_by("vouchers.date", "desc");
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getVouchersSummaryByVendor($filters = array())
    {
        $this->db->select('vouchers.userId, users.name as vendor_name, COUNT(*) as total_vouchers, SUM(vouchers.value) as total_value');
        $this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->from('vouchers');
        $this->db->where("vouchers.deleted", 0);
        $this->applyFilters($filters);
        $this->db->group_by("vouchers.userId");
        $this->db->order_by("total_value", "desc");
        return $this->db->get()->result();
    }

    public function getVouchersGrandTotal($filters = array())
    {
        $this->db->select('SUM(vouchers.value) as total');
        $this->db->join('users', 'users.idUser = vouchers.userId');
        $this->db->from('vouchers');
        $this->db->where("vouchers.deleted", 0);
        $this->applyFilters($filters);
        $row = $this->db->get()->row();
        return $row ? (float)$row->total : 0;
    }

    private function applyFilters($filters)
    {
        if (!empty($filters['vendor'])) {
            $this->db->where('vouchers.userId', $filters['vendor']);
        }
        if (!empty($filters['state'])) {
            $this->db->where('vouchers.state', $filters['state']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('vouchers.date >=', $filters['from'] . ' 00:00:00');
        }
        if (!empty($filters['to'])) {
            $this->db->where('vouchers.date <=', $filters['to'] . ' 23:59:59');
        }
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