<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments_model extends CI_Model {

	public function getPayments($page = -1, $limit = 20){
		$this->db->select('payments.*,
			users.name as vendor_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = payments.vendorId');
        $this->db->join('clients', 'clients.idClient = payments.clientId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = payments.paymentMethod');
        $this->db->from('payments');
		$this->db->where("payments.deleted",0);
		$this->db->order_by("payments.date", "desc");
		if($page != -1)
        	$this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getPayment($id){
		$this->db->select('payments.*,
			users.name as vendor_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			paymentmethods.name as method_name');
		$this->db->join('users', 'users.idUser = payments.vendorId');
        $this->db->join('clients', 'clients.idClient = payments.clientId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = payments.paymentMethod');
        $this->db->from('payments');
		$this->db->where("payments.idPayment",$id);
		$this->db->where("payments.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function searchByWord($term, $page = 1, $limit = 20){
		$this->db->select('payments.*,
			users.name as vendor_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			paymentmethods.name as method_name');
        $this->db->join('users', 'users.idUser = payments.vendorId');
        $this->db->join('clients', 'clients.idClient = payments.clientId');
        $this->db->join('paymentmethods', 'paymentmethods.idMethod = payments.paymentMethod');
        $this->db->from('payments');
        
		$this->db->where("payments.deleted",0);
       
        $this->db->like('clients.name', $term);
     	$this->db->or_like('payments.payment', $term);
     	$this->db->or_like('payments.idPayment', $term);
		$this->db->order_by("invoices.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotalSearch($term) 
    {
        $this->db->join('clients', 'clients.idClient = payments.clientId');
    	$this->db->from('payments');
    	
    	
    	$this->db->where("payments.deleted",0);
    	$this->db->like('clients.name', $term);
     	$this->db->or_like('payments.payment', $term);
     	$this->db->or_like('payments.idPayment', $term);
        return $this->db->count_all_results();
    }

	public function getInvoicePayment($id){
		$this->db->select('SUM(payments.payment) as payment');
        $this->db->from('payments');
		$this->db->where("payments.invoiceId",$id);
		$this->db->where("payments.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("payments",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idPayment",$id);
		return $this->db->update("payments",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idPayment",$Store_id);
		//return $this->db->delete("payments");
	}

	public function getTotal() 
    {
    	$this->db->from('payments');
    	$this->db->where("payments.deleted",0);
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