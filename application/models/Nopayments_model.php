<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nopayments_model extends CI_Model {

	public function getPayments($page = -1, $limit = 20){
		$this->db->select('nopayments.*,
			users.name as vendor_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = nopayments.vendorId');
        $this->db->join('clients', 'clients.idClient = nopayments.clientId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = nopayments.paymentMethod');
        $this->db->from('nopayments');
		$this->db->where("nopayments.deleted",0);
		$this->db->order_by("nopayments.date", "desc");
		if($page != -1)
        	$this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getPayment($id){
		$this->db->select('nopayments.*,
			users.name as vendor_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = nopayments.vendorId');
        $this->db->join('clients', 'clients.idClient = nopayments.clientId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = nopayments.paymentMethod');
        $this->db->from('nopayments');
		$this->db->where("nopayments.idPayment",$id);
		$this->db->where("nopayments.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function searchByWord($term, $page = 1, $limit = 20){
		$this->db->select('nopayments.*,
			users.name as vendor_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			paymentmethods.name as method_name');
        $this->db->join('users', 'users.idUser = nopayments.vendorId');
        $this->db->join('clients', 'clients.idClient = nopayments.clientId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = nopayments.paymentMethod');
        $this->db->from('nopayments');
        
		$this->db->where("nopayments.deleted",0);
       
        $this->db->like('clients.name', $term);
		$this->db->group_start(); // Start of the bracketed group
     	$this->db->or_like('nopayments.invoiceId', $term);
     	$this->db->or_like('nopayments.payment', $term);
     	$this->db->or_like('nopayments.idPayment', $term);
		$this->db->group_end(); // End of the bracketed group
		$this->db->order_by("nopayments.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotalSearch($term) 
    {
        $this->db->join('clients', 'clients.idClient = nopayments.clientId');
    	$this->db->from('nopayments');
    	
    	
    	$this->db->where("nopayments.deleted",0);
    	$this->db->like('clients.name', $term);
		$this->db->group_start(); // Start of the bracketed group
     	$this->db->or_like('nopayments.invoiceId', $term);
     	$this->db->or_like('nopayments.payment', $term);
     	$this->db->or_like('nopayments.idPayment', $term);
		$this->db->group_end(); // End of the bracketed group
        return $this->db->count_all_results();
    }

	public function getInvoicePayment($id){
		$this->db->select('SUM(nopayments.payment) as payment');
        $this->db->from('nopayments');
		$this->db->where("nopayments.invoiceId",$id);
		$this->db->where("nopayments.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("nopayments",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idPayment",$id);
		return $this->db->update("nopayments",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idPayment",$Store_id);
		//return $this->db->delete("nopayments");
	}

	public function getTotal() 
    {
    	$this->db->from('nopayments');
    	$this->db->where("nopayments.deleted",0);
        return $this->db->count_all_results();
    }

	public function getPaymentMethods(){
		$this->db->select('paymentmethods.*');
        $this->db->from('paymentmethods');
		$this->db->where("paymentmethods.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getPaymentMethod($id){
		$this->db->select('paymentMethods.*');
        $this->db->from('paymentmethods');
		$this->db->where("paymentmethods.idMethod",$id);
		$this->db->where("paymentmethods.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function saveMethod($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("paymentmethods",$data);
	}

	public function updateMethod($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idMethod",$id);
		return $this->db->update("paymentmethods",$data);
	}
	public function removeMethod($method_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->updateMethod($method_id,$data);
		//$this->db->where("idMethod",$Store_id);
		//return $this->db->delete("paymentmethods");
	}
}