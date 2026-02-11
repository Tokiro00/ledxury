<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices_model extends CI_Model {

	public function getInvoices($getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20, $from = "", $until = ""){
        //u.name as originalvendor_name,
		$this->db->select('invoices.*,
			users.name as vendor_name,
			users.f_id as vendorFId,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			clients.address as client_address,
			clients.cellphone as client_cellphone,
			clients.f_id as clientFId,
			clients.phone as client_phone,
            clients.is_new as client_new');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        //$this->db->join('users u', 'u.idUser = invoices.originalVendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
		$this->db->where("invoices.deleted",0);
        if(!$getOthers)
        {
        	$this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
        	$this->db->where("invoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("invoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("invoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("invoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("invoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("invoices.hasIva",$iva);
        }
        if(!empty($from))
        {
        	$this->db->where('invoices.idInvoice >=', $from);
        }
        if(!empty($until))
        {
			$this->db->where('invoices.idInvoice <=', $until);
        }
        /*
		if(!empty($from))
        {
        	$this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
			$this->db->where('invoices.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
        */
		$this->db->order_by("invoices.date", "desc");
		if($page != -1)
        	$this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function searchByWord($term, $getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20){
		$this->db->select('invoices.*,
            users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
            clients.is_new as client_new');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        
        if(!$getOthers)
        {
        	$this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
        	$this->db->where("invoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("invoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("invoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("invoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("invoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("invoices.hasIva",$iva);
        }

		$this->db->group_start();
        $this->db->like('clients.name', $term);
     	$this->db->or_like('invoices.total', $term);
     	$this->db->or_like('invoices.idInvoice', $term);
     	$this->db->group_end();
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

    public function searchByProduct($term, $getOthers, $page = 1, $limit = 20){
        $this->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.is_new as client_new');
        
        if(!$getOthers)
        {
            $this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        

        $this->db->join('invoices', 'invoice_details.invoiceId = invoices.idInvoice');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->like('invoice_details.productId', $term);
        $this->db->from('invoice_details');
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function searchByWordLC($term, $getOthers, $store, $admin_store, $page = 1, $limit = 20){
        $this->db->select('invoices.*,
            users.name as vendor_name,
            u.name as originalvendor_name,
            users.f_id as vendorFId,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.address as client_address,
            clients.cellphone as client_cellphone,
            clients.f_id as clientFId,
            clients.phone as client_phone,
            clients.is_new as client_new');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('users u', 'u.idUser = invoices.originalVendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        
        if(!$getOthers)
        {
            $this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
            $this->db->where("invoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("invoices.storeId",$admin_store);
        }
        

        $this->db->group_start();
        $this->db->like('clients.name', $term);
        $this->db->or_like('invoices.total', $term);
        $this->db->or_like('invoices.idInvoice', $term);
        $this->db->group_end();
        
        $this->db->where("invoices.legal_collection",1);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function searchByProductLC($term, $getOthers, $page = 1, $limit = 20){

        $this->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.is_new as client_new');
        
        if(!$getOthers)
        {
            $this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        

        $this->db->join('invoices', 'invoice_details.invoiceId = invoices.idInvoice');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->like('invoice_details.productId', $term);
        $this->db->from('invoice_details');
        $this->db->where("invoices.legal_collection",1);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
        $resultados = $this->db->get();
        return $resultados->result();
    }

	public function getTotalSearch($term, $store, $vendor, $state, $client, $iva, $admin_store) 
    {
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
    	$this->db->from('invoices');
    	
    	if($store != 'all')
        {
        	$this->db->where("invoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("invoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("invoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("invoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("invoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("invoices.hasIva",$iva);
        }
        		$this->db->group_start();
    	$this->db->like('clients.name', $term);
     	$this->db->or_like('invoices.total', $term);
     	     	$this->db->group_end();

    	$this->db->where("invoices.deleted",0);
        return $this->db->count_all_results();
    }
    public function getTotalSearchLC($term, $store, $admin_store) 
    {
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->from('invoices');
        
        if($store != 'all')
        {
            $this->db->where("invoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("invoices.storeId",$admin_store);
        }
        
                $this->db->group_start();
        $this->db->like('clients.name', $term);
        $this->db->or_like('invoices.total', $term);
                $this->db->group_end();

        $this->db->where("invoices.legal_collection",1);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where("invoices.deleted",0);
        return $this->db->count_all_results();
    }
    public function getTotalSearchByProductLC($term) 
    {
        $this->db->join('invoices', 'invoice_details.invoiceId = invoices.idInvoice');
        $this->db->from('invoice_details');
                
        $this->db->like('invoice_details.productId', $term);

        $this->db->where("invoices.legal_collection",1);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where("invoices.deleted",0);
        return $this->db->count_all_results();
    }
    public function getTotalSearchByProduct($term) 
    {
        $this->db->join('invoices', 'invoice_details.invoiceId = invoices.idInvoice');
        $this->db->from('invoice_details');
                
        $this->db->like('invoice_details.productId', $term);

        $this->db->where("invoices.deleted",0);
        return $this->db->count_all_results();
    }
	public function getTotal($store, $vendor, $state, $client, $iva, $admin_store) 
    {
    	$this->db->from('invoices');
    	if($store != 'all')
        {
        	$this->db->where("invoices.storeId",$store);
        }
        if(!empty($admin_store))
        {
            $this->db->where_in("invoices.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("invoices.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("invoices.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("invoices.clientId",$client);
        }
        if($iva != 'all')
        {
        	$this->db->where("invoices.hasIva",$iva);
        }
    	$this->db->where("invoices.deleted",0);
        return $this->db->count_all_results();
    }

    public function getLegalColletionInvoices($store, $getOthers, $page = 1, $limit = 20){
		$this->db->select('invoices.*,
			users.name as vendor_name,
		    u.name as originalvendor_name,
        	users.f_id as vendorFId,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			clients.address as client_address,
			clients.cellphone as client_cellphone,
			clients.f_id as clientFId,
			clients.phone as client_phone,
            clients.is_new as client_new');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('users u', 'u.idUser = invoices.originalVendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        if(!$getOthers)
        {
            $this->db->where("invoices.originalVendorId",$this->session->userdata('user_data')['uname']);
        }
         if($store != 'all')
        {
            $this->db->where("invoices.storeId",$store);
        }
		$this->db->where("invoices.legal_collection",1);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.date", "asc");
		if($page != -1)
        	$this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}
	public function getLCTotal($store, $getOthers) 
    {
    	$this->db->from('invoices');
        if(!$getOthers)
        {
            $this->db->where("invoices.originalVendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
            $this->db->where("invoices.storeId",$store);
        }
		$this->db->where("invoices.legal_collection",1);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
    	$this->db->where("invoices.deleted",0);
        return $this->db->count_all_results();
    }

    public function getClientDebt($client){
		$this->db->select('SUM(invoices.total - (invoices.payment + invoices.discount)) as debt');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.clientId",$client);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.date", "desc");
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function oldestNonPaidInvioce($client){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.clientId",$client);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.updated_at", "asc");
        $this->db->limit(1);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getNonPaidInvoices($getOthers){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        if(!$getOthers)
        {
        	$this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

    public function getNoPaidNoInLegalCollectionInvoices($getOthers){
        $this->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        if(!$getOthers)
        {
            $this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
        }
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where("invoices.legal_collection",0);
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.updated_at", "desc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getVendorLegalColletionInvoices($vendor){
        $this->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.originalVendorId",$vendor);
        $this->db->where("invoices.legal_collection",1);
        //$this->db->where("invoices.state",2);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1' OR invoices.state = '2')");
        $this->db->where("invoices.settled",0);
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.updated_at", "desc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getTotalVendorLegalColletionInvoices($vendor){
        $this->db->select('invoices.*');
        $this->db->from('invoices');
        $this->db->where("invoices.originalVendorId",$vendor);
        $this->db->where("invoices.legal_collection",1);
        //$this->db->where("invoices.state",2);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1' OR invoices.state = '2')");
        $this->db->where("invoices.settled",0);
        $this->db->where("invoices.deleted",0);
        $resultados = $this->db->get();
        return $resultados->num_rows();
    }

	public function nonPaidInvoicesCount($getOthers)
	{
		if(!$getOthers)
		{
			$this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
		}
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
		$this->db->where("invoices.deleted",0);
		$resultados = $this->db->get('invoices');
		return $resultados->num_rows();
	}

	public function getVendorInvoices($vendor){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendorTotalInvoicesSince($vendor,$date){
		$this->db->select('SUM(invoices.total) as total');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        $this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($date)));
		$this->db->where("invoices.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getVendorTotalPaidInvoicesSince($vendor,$date){
		$this->db->select('SUM(invoices.payment) as payment');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        $this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($date)));
		$this->db->where("invoices.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getVendorInvoicesSince($vendor,$date){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        $this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($date)));
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getVendorPaidInvoices($vendor){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        $this->db->where("invoices.state",2);
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}



	public function getVendorNonPaidInvoices($vendor){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function paidInvoicesCount($getOthers)
	{
		if(!$getOthers)
		{
			$this->db->where("invoices.vendorId",$this->session->userdata('user_data')['uname']);
		}
        $this->db->where("invoices.state",2);
		$this->db->where("invoices.deleted",0);
		$resultados = $this->db->get('invoices');
		return $resultados->num_rows();
	}

	public function getInvoice($id){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
			clients.address as address,
			clients.phone as phone,
			clients.state as client_state,
            clients.zone as zone,
			clients.city as city,
			clients.cellphone as cellphone');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
		$this->db->where("invoices.idInvoice",$id);
		$this->db->where("invoices.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	/*public function getVendorPaidInvoices2($vendor){
		$this->db->select('invoices.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
		//	max(payments.date) AS payday
		//	(SELECT max(payments.date) FROM payments WHERE payments.invoiceId = invoices.idInvoice) AS payday');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
		//$this->db->join('payments', 'payments.invoiceId = invoices.idInvoice', 'right');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        $this->db->where("invoices.state",2);
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.updated_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}*/

	public function getInvoicePaymentDate($id){
		$this->db->select('*');
        $this->db->from('payments');
		$this->db->where("payments.invoiceId",$id);
		$this->db->where("payments.deleted",0);
		$this->db->order_by("payments.date", "desc");
        $this->db->limit(1);
		$resultados = $this->db->get();
		return $resultados->row();
	}

    public function getVendorSalesByMonth($vendor, $year){
        $this->db->select('SUM(invoices.total - invoices.discount) as total,
            invoices.storeId as storeId,
            invoices.vendorId as vendorId,
            users.name as vendor_name,
            MONTH(invoices.date) as month');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        $this->db->where("YEAR(invoices.date)",$year);
        $this->db->where("invoices.deleted",0);
        $this->db->group_by("month");
        $this->db->order_by("month", "asc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getVendorSalesByDay($vendor, $from = "", $until = ""){
        $this->db->select('SUM(invoices.total - invoices.discount) as total,
            invoices.storeId as storeId,
            invoices.vendorId as vendorId,
            date(invoices.date) as date,
            users.name as vendor_name,
            DAY(invoices.date) as day');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->from('invoices');
        $this->db->where("invoices.vendorId",$vendor);
        if(!empty($from))
        {
            $this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
            $this->db->where('invoices.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
        $this->db->where("invoices.deleted",0);
        $this->db->group_by("day");
        //$this->db->group_by("invoices.vendorId");
        $this->db->order_by("day", "asc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getSalesByDay($store = -1, $from = "", $until = ""){
        $this->db->select('SUM(invoices.total - invoices.discount) as total,
            invoices.vendorId as vendorId,
            invoices.storeId as storeId,
            date(invoices.date) as date,
            users.name as vendor_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->from('invoices');
        if($store != -1)
            $this->db->where("invoices.storeId",$store);
        //$this->db->where("invoices.vendorId",$vendor);
        if(!empty($from))
        {
            $this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
            $this->db->where('invoices.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
        $this->db->where("invoices.deleted",0);
        //$this->db->group_by("day");
        $this->db->group_by("invoices.date");
        //$this->db->group_by("invoices.vendorId");
        $this->db->order_by("date", "asc");
        $this->db->order_by("invoices.vendorId", "asc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getTotalSalesByDay($store = -1, $from = "", $until = ""){
        $this->db->select('SUM(invoices.total - invoices.discount) as total,
            invoices.vendorId as vendorId,
            invoices.storeId as storeId,
            date(invoices.date) as date,
            users.name as vendor_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->from('invoices');
        if($store != -1)
            $this->db->where("invoices.storeId",$store);
        //$this->db->where("invoices.vendorId",$vendor);
        if(!empty($from))
        {
            $this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
            $this->db->where('invoices.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
        $this->db->where("invoices.deleted",0);
        //$this->db->group_by("day");
        //$this->db->group_by("invoices.date");
        $this->db->group_by("invoices.vendorId");
        $this->db->order_by("date", "asc");
        $this->db->order_by("invoices.vendorId", "asc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getVendorSalesGoal($vendor){
        $this->db->select('*');
        $this->db->from('sales_goal');
        $this->db->where("sales_goal.userId",$vendor);
        $resultados = $this->db->get();
        return $resultados->result_array();
    }

    public function getVendorSalesYearGoal($vendor, $year){
        $this->db->select('*');
        $this->db->from('sales_goal');
        $this->db->where("sales_goal.userId",$vendor);
        $this->db->where("sales_goal.year",$year);
        $resultados = $this->db->get();
        return $resultados->row_array();
    }

    public function saveVendorSalesGoal($data){
        $goal = $this->getVendorSalesYearGoal($data['userId'], $data['year']);
        if(empty($goal))
        {
            return $this->db->insert("sales_goal",$data);
        }else
        {
            $this->db->where("userId",$data['userId']);
            $this->db->where("year",$data['year']);
            return $this->db->update("sales_goal",$data);
        }
    }

    public function getStoreSalesByVendor($store, $year){
        $this->db->select('SUM(invoices.total - invoices.discount) as total, invoices.storeId,
            users.name as vendor_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->from('invoices');
        if($store != -1)
            $this->db->where("invoices.storeId",$store);
        //$this->db->where("invoices.vendorId",$vendor);
        $this->db->where("YEAR(invoices.date)",$year);
        $this->db->where("invoices.deleted",0);
        $this->db->group_by("vendorId");
        $this->db->order_by("invoices.storeId", "asc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

	public function saveRefund($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("refunds",$data);
	}

	public function save_refund_detail($data){
		return $this->db->insert("refund_details",$data);
	}

	/**
	 * Get refunds with pagination
	 */
	public function getRefunds($page = 1, $limit = 50, $invoiceId = null){
		$this->db->select('refunds.*, invoices.idInvoice, clients.name as client_name, clients.idNum as client_idNum, stores.name as store_name');
		$this->db->join('invoices', 'invoices.idInvoice = refunds.invoiceId');
		$this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'stores.idStore = invoices.storeId');
		$this->db->from('refunds');
		$this->db->where('refunds.deleted', 0);
		if ($invoiceId) {
			$this->db->where('refunds.invoiceId', $invoiceId);
		}
		$this->db->order_by('refunds.idRefund', 'DESC');
		$offset = ($page - 1) * $limit;
		$this->db->limit($limit, $offset);
		return $this->db->get()->result();
	}

	/**
	 * Get total refunds count
	 */
	public function getTotalRefunds($invoiceId = null){
		$this->db->from('refunds');
		$this->db->where('refunds.deleted', 0);
		if ($invoiceId) {
			$this->db->where('refunds.invoiceId', $invoiceId);
		}
		return $this->db->count_all_results();
	}

	/**
	 * Get single refund with invoice info
	 */
	public function getRefund($id){
		$this->db->select('refunds.*, invoices.idInvoice, invoices.total as invoiceTotal, invoices.payment as invoicePayment, invoices.discount as invoiceDiscount, invoices.state as invoiceState, invoices.storeId, invoices.clientId, invoices.list_price, clients.name as client_name, stores.name as store_name');
		$this->db->join('invoices', 'invoices.idInvoice = refunds.invoiceId');
		$this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'stores.idStore = invoices.storeId');
		$this->db->from('refunds');
		$this->db->where('refunds.idRefund', $id);
		return $this->db->get()->row();
	}

	/**
	 * Get refund details (products)
	 */
	public function getRefundDetails($refundId){
		$this->db->select('refund_details.*, products.description as product_name');
		$this->db->join('products', 'products.idProduct = refund_details.productId');
		$this->db->from('refund_details');
		$this->db->where('refund_details.refundId', $refundId);
		return $this->db->get()->result();
	}

	/**
	 * Soft delete refund
	 */
	public function deleteRefund($id){
		date_default_timezone_set("America/Bogota");
		$data = array(
			'deleted' => 1,
			'deleted_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		);
		$this->db->where('idRefund', $id);
		return $this->db->update('refunds', $data);
	}

	/**
	 * Get refunds by invoice
	 */
	public function getRefundsByInvoice($invoiceId){
		$this->db->select('refunds.*');
		$this->db->from('refunds');
		$this->db->where('refunds.invoiceId', $invoiceId);
		$this->db->where('refunds.deleted', 0);
		$this->db->order_by('refunds.date', 'DESC');
		return $this->db->get()->result();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_by'] = $this->session->userdata('user_data')['uname'];
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("invoices",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idInvoice",$id);
		return $this->db->update("invoices",$data);
	}
	public function printed($id){
		$data  = array(
                    'printed' => 1
                );
		$this->db->where("idInvoice",$id);
		return $this->db->update("invoices",$data);
	}

	public function remove($id){
		date_default_timezone_set("America/Bogota");

		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted_by' => $this->session->userdata('user_data')['uname'],
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idInvoice",$id);
		//return $this->db->delete("invoices");
	}

	public function lastID(){
		return $this->db->insert_id();
	}

	public function save_detail($data){
		return $this->db->insert("invoice_details",$data);
	}

	public function update_detail($idInvoice,$idProduct,$data){
		$this->db->where("invoiceId",$idInvoice);
		$this->db->where("productId",$idProduct);
		return $this->db->update("invoice_details",$data);
	}

	public function removeDetails($idInvoice){
		$this->db->where("invoice_details.invoiceId",$idInvoice);
        $this->db->delete('invoice_details');
	}
	
	public function getDetails($invoiceId){
		$this->db->select('invoice_details.*, products.*, invoice_details.total as subtotal');
        $this->db->join('products', 'products.idProduct = invoice_details.productId');
        $this->db->from('invoice_details');
		$this->db->where("invoice_details.invoiceId",$invoiceId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getInvoiceDetail($invoiceId, $productId){
		$this->db->select('invoice_details.*');
		$this->db->from('invoice_details');
		$this->db->where('invoice_details.invoiceId', $invoiceId);
		$this->db->where('invoice_details.productId', $productId);
		return $this->db->get()->row();
	}

    public function getIfDetailsHasNational($invoiceId){
        $this->db->select('invoice_details.*, products.*, invoice_details.total as subtotal');
        $this->db->join('products', 'products.idProduct = invoice_details.productId');
        $this->db->from('invoice_details');
        $this->db->where("invoice_details.invoiceId",$invoiceId);
        $this->db->where("products.is_national",1);
        $resultados = $this->db->get();
        return $resultados->result();
    }

	public function getProductLastPrice($productId,$vendor,$client){
		$this->db->select('invoice_details.*, invoices.*, invoice_details.total as subtotal');
        $this->db->join('invoices', 'invoices.idInvoice = invoice_details.invoiceId');
        $this->db->from('invoice_details');
		$this->db->where("invoice_details.productId",$productId);
		$this->db->where("invoices.vendorId",$vendor);
		$this->db->where("invoices.clientId",$client);
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.date", "desc");
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProductLastPriceOld($productId,$vendor,$client){
		$this->db->select('invoice_details.*, invoices.*, invoice_details.total as subtotal');
        $this->db->join('invoices', 'invoices.idInvoice = invoice_details.invoiceId');
        $this->db->from('invoice_details');
		$this->db->where("invoice_details.productId",$productId);
		$this->db->where("invoices.vendorId",$vendor);
		$this->db->where("invoices.deleted",0);
		$this->db->order_by("invoices.date", "desc");
        $resultados = $this->db->get();
		return $resultados->result();
	}
	
    public function getInvoicesToCheckDelivery(){
        $this->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.phone as client_phone,
            clients.cellphone as client_cellphone');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.check_delivery",0);
        //$this->db->where("invoices.state",2);
        //$this->db->where("(invoices.state = '0' OR invoices.state = '1' OR invoices.state = '2')");
        //$this->db->where('CURDATE() >= DATE(DATE_ADD(invoices.date, INTERVAL 3 DAY))');
        $this->db->where("(invoices.state = '0')");
        $this->db->where('CURDATE() >= ((invoices.date + INTERVAL 3 DAY))');
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.clientId", "asc");
        $this->db->order_by("invoices.date", "desc");
        //$this->db->group_by("invoices.clientId");
        $resultados = $this->db->get();
        return $resultados->result();
    }


    public function getInvoicesCloseToExpire(){
        $this->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.phone as client_phone,
            clients.cellphone as client_cellphone');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.check_delivery",0);
        //$this->db->where("invoices.state",2);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        //$this->db->where('CURDATE() >= DATE(DATE_ADD(invoices.date, INTERVAL 3 DAY))');
        $this->db->where('CURDATE() >= ((invoices.date + INTERVAL 1 MONTH) - INTERVAL 3 DAY)');
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.clientId", "asc");
        $this->db->order_by("invoices.updated_at", "desc");
        //$this->db->group_by("invoices.clientId");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getInvoicesExpired(){
        $this->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.phone as client_phone,
            clients.cellphone as client_cellphone');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.check_delivery",0);
        //$this->db->where("invoices.state",2);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        //$this->db->where('CURDATE() >= DATE(DATE_ADD(invoices.date, INTERVAL 3 DAY))');
        $this->db->where('CURDATE() >= ((invoices.date + INTERVAL 1 MONTH) + INTERVAL 3 DAY)');
        $this->db->where("invoices.deleted",0);
        $this->db->order_by("invoices.clientId", "asc");
        $this->db->order_by("invoices.updated_at", "desc");
        //$this->db->group_by("invoices.clientId");
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function updateInvoicesToCheckDelivery($invoices){
        $data  = array(
                    'check_delivery' => 1
                );
        $this->db->where_in("idInvoice",$invoices);
        return $this->db->update("invoices",$data);
    }

    public function updateInvoicesCloseToExpire($invoices){
        $data  = array(
                    'close_to_expire' => 1
                );
        $this->db->where_in("idInvoice",$invoices);
        return $this->db->update("invoices",$data);
    }

    public function updateInvoicesExpired($invoices){
        $data  = array(
                    'is_expired' => 1
                );
        $this->db->where_in("idInvoice",$invoices);
        return $this->db->update("invoices",$data);
    }

    /**
     * Get accounts receivable with aging data (Cuentas por Cobrar)
     * Returns pending invoices with balance > 0 and aging category
     */
    public function getAccountsReceivable($clientId = null, $storeId = null, $vendorId = null, $page = 1, $limit = 50){
        $this->db->select('invoices.*,
            invoices.total - (invoices.payment + invoices.discount) as balance,
            DATEDIFF(CURDATE(), invoices.date) as days_overdue,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.cellphone as client_cellphone,
            clients.phone as client_phone');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where('(invoices.total - (invoices.payment + invoices.discount)) >', 0);

        if ($clientId) {
            $this->db->where('invoices.clientId', $clientId);
        }
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        if ($vendorId) {
            $this->db->where('invoices.vendorId', $vendorId);
        }

        $this->db->order_by('invoices.date', 'ASC');
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    /**
     * Get total count of accounts receivable
     */
    public function getTotalAccountsReceivable($clientId = null, $storeId = null, $vendorId = null){
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where('(invoices.total - (invoices.payment + invoices.discount)) >', 0);

        if ($clientId) {
            $this->db->where('invoices.clientId', $clientId);
        }
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        if ($vendorId) {
            $this->db->where('invoices.vendorId', $vendorId);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get accounts receivable aging summary (Resumen por antigüedad)
     */
    public function getAccountsReceivableAging($clientId = null, $storeId = null, $vendorId = null){
        // Get all pending invoices with balance
        $this->db->select('invoices.total, invoices.payment, invoices.discount,
            DATEDIFF(CURDATE(), invoices.date) as days_overdue');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where('(invoices.total - (invoices.payment + invoices.discount)) >', 0);

        if ($clientId) {
            $this->db->where('invoices.clientId', $clientId);
        }
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        if ($vendorId) {
            $this->db->where('invoices.vendorId', $vendorId);
        }

        $invoices = $this->db->get()->result();

        // Calculate aging buckets
        $aging = array(
            'current' => 0,      // Al día (0-30 días)
            'days_31_60' => 0,   // 31-60 días
            'days_61_90' => 0,   // 61-90 días
            'days_91_plus' => 0, // +90 días
            'total' => 0,
            'count_current' => 0,
            'count_31_60' => 0,
            'count_61_90' => 0,
            'count_91_plus' => 0,
            'count_total' => 0
        );

        foreach ($invoices as $inv) {
            $balance = $inv->total - ($inv->payment + $inv->discount);
            $days = $inv->days_overdue;

            $aging['total'] += $balance;
            $aging['count_total']++;

            if ($days <= 30) {
                $aging['current'] += $balance;
                $aging['count_current']++;
            } else if ($days <= 60) {
                $aging['days_31_60'] += $balance;
                $aging['count_31_60']++;
            } else if ($days <= 90) {
                $aging['days_61_90'] += $balance;
                $aging['count_61_90']++;
            } else {
                $aging['days_91_plus'] += $balance;
                $aging['count_91_plus']++;
            }
        }

        return $aging;
    }

    /**
     * Get accounts receivable grouped by client
     */
    public function getAccountsReceivableByClient($storeId = null, $vendorId = null){
        $this->db->select('clients.idClient, clients.name as client_name, clients.idNum as client_idNum,
            clients.cellphone, clients.phone,
            SUM(invoices.total - (invoices.payment + invoices.discount)) as total_balance,
            COUNT(invoices.idInvoice) as invoice_count,
            MIN(invoices.date) as oldest_invoice,
            DATEDIFF(CURDATE(), MIN(invoices.date)) as max_days_overdue');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where('(invoices.total - (invoices.payment + invoices.discount)) >', 0);

        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        if ($vendorId) {
            $this->db->where('invoices.vendorId', $vendorId);
        }

        $this->db->group_by('clients.idClient');
        $this->db->order_by('total_balance', 'DESC');
        return $this->db->get()->result();
    }
}