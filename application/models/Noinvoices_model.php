<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Noinvoices_model extends CI_Model {

	public function getInvoices($getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20, $from = "", $until = ""){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			users.f_id as vendorFId,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			clients.address as client_address,
			clients.cellphone as client_cellphone,
			clients.f_id as clientFId,
			clients.phone as client_phone');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
		$this->db->where("noinvoices.deleted",0);
        if(!$getOthers)
        {
        	$this->db->where("noinvoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
        	$this->db->where("noinvoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("noinvoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("noinvoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("noinvoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("noinvoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("noinvoices.hasIva",$iva);
        }
        if(!empty($from))
        {
        	$this->db->where('noinvoices.idInvoice >=', $from);
        }
        if(!empty($until))
        {
			$this->db->where('noinvoices.idInvoice <=', $until);
        }
        /*
		if(!empty($from))
        {
        	$this->db->where('noinvoices.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
			$this->db->where('noinvoices.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
        */
		$this->db->order_by("noinvoices.date", "desc");
		if($page != -1)
        	$this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function searchByWord($term, $getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
        
		$this->db->where("noinvoices.deleted",0);
        if(!$getOthers)
        {
        	$this->db->where("noinvoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
        	$this->db->where("noinvoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("noinvoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("noinvoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("noinvoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("noinvoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("noinvoices.hasIva",$iva);
        }
        $this->db->like('clients.name', $term);
     	$this->db->or_like('noinvoices.total', $term);
     	$this->db->or_like('noinvoices.idInvoice', $term);
		$this->db->order_by("noinvoices.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotalSearch($term, $store, $vendor, $state, $client, $iva, $admin_store) 
    {
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
    	$this->db->from('noinvoices');
    	
    	if($store != 'all')
        {
        	$this->db->where("noinvoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("noinvoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("noinvoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("noinvoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("noinvoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("noinvoices.hasIva",$iva);
        }
    	$this->db->where("noinvoices.deleted",0);
    	$this->db->like('clients.name', $term);
     	$this->db->or_like('noinvoices.total', $term);
        return $this->db->count_all_results();
    }
	public function getTotal($store, $vendor, $state, $client, $iva, $admin_store) 
    {
    	$this->db->from('noinvoices');
    	if($store != 'all')
        {
        	$this->db->where("noinvoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("noinvoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("noinvoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("noinvoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("noinvoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("noinvoices.hasIva",$iva);
        }
    	$this->db->where("noinvoices.deleted",0);
        return $this->db->count_all_results();
    }

    public function getClientDebt($client){
		$this->db->select('SUM(noinvoices.total - (noinvoices.payment + noinvoices.discount)) as debt');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
        $this->db->where("noinvoices.clientId",$client);
        $this->db->where("(noinvoices.state = '0' OR noinvoices.state = '1')");
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.date", "desc");
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function oldestNonPaidInvioce($client){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
        $this->db->where("noinvoices.clientId",$client);
        $this->db->where("(noinvoices.state = '0' OR noinvoices.state = '1')");
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.updated_at", "asc");
        $this->db->limit(1);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getNonPaidInvoices($getOthers){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
        if(!$getOthers)
        {
        	$this->db->where("noinvoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        $this->db->where("(noinvoices.state = '0' OR noinvoices.state = '1')");
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}
	public function nonPaidInvoicesCount($getOthers)
	{
		if(!$getOthers)
		{
			$this->db->where("noinvoices.vendorId",$this->session->userdata('user_data')['uname']);
		}
        $this->db->where("(noinvoices.state = '0' OR noinvoices.state = '1')");
		$this->db->where("noinvoices.deleted",0);
		$resultados = $this->db->get('noinvoices');
		return $resultados->num_rows();
	}

	public function getVendorInvoices($vendor){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
        $this->db->where("noinvoices.vendorId",$vendor);
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendorPaidInvoices($vendor){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
        $this->db->where("noinvoices.vendorId",$vendor);
        $this->db->where("noinvoices.state",2);
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}



	public function getVendorNonPaidInvoices($vendor){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
        $this->db->where("noinvoices.vendorId",$vendor);
        $this->db->where("(noinvoices.state = '0' OR noinvoices.state = '1')");
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function paidInvoicesCount($getOthers)
	{
		if(!$getOthers)
		{
			$this->db->where("noinvoices.vendorId",$this->session->userdata('user_data')['uname']);
		}
        $this->db->where("noinvoices.state",2);
		$this->db->where("noinvoices.deleted",0);
		$resultados = $this->db->get('noinvoices');
		return $resultados->num_rows();
	}

	public function getInvoice($id){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			clients.address as address,
			clients.phone as phone,
			clients.cellphone as cellphone');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
        $this->db->from('noinvoices');
		$this->db->where("noinvoices.idInvoice",$id);
		$this->db->where("noinvoices.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	/*public function getVendorPaidInvoices2($vendor){
		$this->db->select('noinvoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
		//	max(nopayments.date) AS payday
		//	(SELECT max(nopayments.date) FROM nopayments WHERE nopayments.invoiceId = noinvoices.idInvoice) AS payday');
        $this->db->join('users', 'users.idUser = noinvoices.vendorId');
        $this->db->join('clients', 'clients.idClient = noinvoices.clientId');
		$this->db->join('stores', 'noinvoices.storeId = stores.idStore');
		//$this->db->join('nopayments', 'nopayments.invoiceId = noinvoices.idInvoice', 'right');
        $this->db->from('noinvoices');
        $this->db->where("noinvoices.vendorId",$vendor);
        $this->db->where("noinvoices.state",2);
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}*/

	public function getInvoicePaymentDate($id){
		$this->db->select('*');
        $this->db->from('nopayments');
		$this->db->where("nopayments.invoiceId",$id);
		$this->db->where("nopayments.deleted",0);
		$this->db->order_by("nopayments.date", "desc");
        $this->db->limit(1);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	/*public function saveRefund($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("refunds",$data);
	}

	public function save_refund_detail($data){
		return $this->db->insert("refund_details",$data);
	}*/

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("noinvoices",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idInvoice",$id);
		return $this->db->update("noinvoices",$data);
	}
	public function printed($id){
		$data  = array(
                    'printed' => 1
                );
		$this->db->where("idInvoice",$id);
		return $this->db->update("noinvoices",$data);
	}

	public function remove($id){
		date_default_timezone_set("America/Bogota");

		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idInvoice",$id);
		//return $this->db->delete("noinvoices");
	}

	public function lastID(){
		return $this->db->insert_id();
	}

	/*public function save_detail($data){
		return $this->db->insert("invoice_details",$data);
	}

	public function update_detail($idInvoice,$idProduct,$data){
		$this->db->where("invoiceId",$idInvoice);
		$this->db->where("productId",$idProduct);
		return $this->db->update("invoice_details",$data);
	}

	public function getDetails($invoiceId){
		$this->db->select('invoice_details.*, products.*, invoice_details.total as subtotal');
        $this->db->join('products', 'products.idProduct = invoice_details.productId');
        $this->db->from('invoice_details');
		$this->db->where("invoice_details.invoiceId",$invoiceId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProductLastPrice($productId,$vendor,$client){
		$this->db->select('invoice_details.*, noinvoices.*, invoice_details.total as subtotal');
        $this->db->join('noinvoices', 'noinvoices.idInvoice = invoice_details.invoiceId');
        $this->db->from('invoice_details');
		$this->db->where("invoice_details.productId",$productId);
		$this->db->where("noinvoices.vendorId",$vendor);
		$this->db->where("noinvoices.clientId",$client);
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.date", "desc");
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProductLastPriceOld($productId,$vendor,$client){
		$this->db->select('invoice_details.*, noinvoices.*, invoice_details.total as subtotal');
        $this->db->join('noinvoices', 'noinvoices.idInvoice = invoice_details.invoiceId');
        $this->db->from('invoice_details');
		$this->db->where("invoice_details.productId",$productId);
		$this->db->where("noinvoices.vendorId",$vendor);
		$this->db->where("noinvoices.deleted",0);
		$this->db->order_by("noinvoices.date", "desc");
        $resultados = $this->db->get();
		return $resultados->result();
	}*/
	
}