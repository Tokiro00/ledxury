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
        $this->db->where('invoices.date >=', $year . '-01-01');
        $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
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
        $this->db->where('invoices.date >=', $year . '-01-01');
        $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
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
     * Get invoices for a specific client within a date range
     * Used for Client Account Statement
     */
    public function getInvoicesByClient($clientId, $from = null, $to = null){
        $this->db->select('invoices.idInvoice, invoices.date, invoices.total, invoices.payment,
            invoices.discount, invoices.state, invoices.storeId,
            stores.name as store_name');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where('invoices.clientId', $clientId);
        $this->db->where('invoices.deleted', 0);
        if ($from) $this->db->where('invoices.date >=', $from);
        if ($to) $this->db->where('invoices.date <=', $to . ' 23:59:59');
        $this->db->order_by('invoices.date', 'ASC');
        $this->db->order_by('invoices.idInvoice', 'ASC');
        return $this->db->get()->result();
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

    // ═══════════════════════════════════════════════════════════════════════
    // MÉTODOS DE TRACKING / RASTREO DE GUÍAS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Actualizar información de tracking de una factura
     *
     * @param int $invoiceId ID de la factura
     * @param array $data Datos de tracking
     * @return bool
     */
    public function updateTracking($invoiceId, $data) {
        date_default_timezone_set("America/Bogota");
        $data['tracking_last_update'] = date('Y-m-d H:i:s');
        $this->db->where("idInvoice", $invoiceId);
        return $this->db->update("invoices", $data);
    }

    /**
     * Obtener facturas con tracking activo (enviadas pero no entregadas/devueltas)
     *
     * @return array
     */
    public function getInvoicesWithActiveTracking() {
        $this->db->select('invoices.*,
            users.name as vendor_name,
            users.email as vendor_email,
            clients.name as client_name,
            clients.cellphone as client_cellphone,
            stores.name as store_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where('invoices.tracking_number IS NOT NULL');
        $this->db->where('invoices.tracking_number !=', '');
        $this->db->where_not_in('invoices.tracking_status', ['delivered', 'returned']);
        $this->db->where('invoices.deleted', 0);
        $this->db->order_by('invoices.shipped_at', 'ASC');
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

    /**
     * Cartera agrupada por tienda y vendedor con antigüedad
     */
    public function getDebtByStoreAndVendor() {
        $this->db->select("
            invoices.storeId,
            stores.name as store_name,
            invoices.vendorId,
            users.name as vendor_name,
            COUNT(DISTINCT invoices.clientId) as client_count,
            COUNT(invoices.idInvoice) as invoice_count,
            SUM(invoices.total - invoices.discount) as total_invoiced,
            SUM(invoices.payment) as total_paid,
            SUM(invoices.total - invoices.discount - invoices.payment) as total_debt,
            SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.date) > 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_over_90,
            SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.date) BETWEEN 61 AND 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_61_90,
            SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.date) BETWEEN 31 AND 60 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_31_60,
            SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.date) <= 30 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_0_30
        ", FALSE);
        $this->db->from('invoices');
        $this->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->where('invoices.deleted', 0);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where('(invoices.total - invoices.discount - invoices.payment) >', 0);
        $this->db->group_by(array('invoices.storeId', 'invoices.vendorId'));
        $this->db->order_by('total_debt', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Reporte: Ventas de todos los vendedores por mes con cobros
     */
    public function getAllVendorsSalesByMonth($year, $store = -1) {
        $this->db->select('SUM(invoices.total - invoices.discount) as total_sales,
            SUM(invoices.payment) as total_collected,
            COUNT(invoices.idInvoice) as invoice_count,
            invoices.vendorId,
            invoices.storeId,
            users.name as vendor_name,
            stores.name as store_name,
            MONTH(invoices.date) as month');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->db->from('invoices');
        $this->db->where('invoices.date >=', $year . '-01-01');
        $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        $this->db->where("invoices.deleted", 0);
        if ($store != -1) {
            $this->db->where("invoices.storeId", $store);
        }
        $this->db->group_by(array("invoices.vendorId", "month"));
        $this->db->order_by("total_sales", "DESC");
        return $this->db->get()->result();
    }

    /**
     * Reporte: Metas de todos los vendedores para un año
     */
    public function getAllVendorsGoals($year) {
        $this->db->select('*');
        $this->db->from('sales_goal');
        $this->db->where('year', $year);
        return $this->db->get()->result();
    }

    /**
     * Reporte: Analisis ABC de clientes con cartera por antigüedad
     */
    public function getClientSalesAnalysis($year = null, $store = -1) {
        $this->db->select("clients.idClient, clients.name as client_name, clients.idNum,
            clients.city, clients.vendor,
            users.name as vendor_name,
            COUNT(DISTINCT invoices.idInvoice) as invoice_count,
            SUM(invoices.total - invoices.discount) as total_purchases,
            SUM(invoices.payment) as total_paid,
            SUM(invoices.total - invoices.discount - invoices.payment) as total_debt,
            MIN(invoices.date) as first_purchase,
            MAX(invoices.date) as last_purchase,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) > 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_over_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 61 AND 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_61_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 31 AND 60 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_31_60,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) <= 30 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_0_30", FALSE);
        $this->db->join('invoices', 'invoices.clientId = clients.idClient AND invoices.deleted = 0');
        $this->db->join('users', 'users.idUser = clients.vendor', 'left');
        $this->db->from('clients');
        $this->db->where('clients.deleted', 0);
        if ($year) {
            $this->db->where('invoices.date >=', $year . '-01-01');
            $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        }
        if ($store != -1) {
            $this->db->where("invoices.storeId", $store);
        }
        $this->db->group_by('clients.idClient');
        $this->db->order_by('total_purchases', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Reporte: Cartera por ciudad y vendedor con antigüedad
     */
    public function getDebtByCityAndVendor($year = null, $store = -1, $vendorId = null, $clientId = null) {
        $this->db->select("
            clients.city,
            invoices.vendorId,
            invoices.storeId,
            users.name as vendor_name,
            stores.name as store_name,
            COUNT(DISTINCT clients.idClient) as client_count,
            COUNT(DISTINCT invoices.idInvoice) as invoice_count,
            SUM(invoices.total - invoices.discount) as total_invoiced,
            SUM(invoices.payment) as total_paid,
            SUM(invoices.total - invoices.discount - invoices.payment) as total_debt,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) > 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_over_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 61 AND 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_61_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 31 AND 60 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_31_60,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) <= 30 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_0_30
        ", FALSE);
        $this->db->from('invoices');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('(invoices.total - invoices.discount - invoices.payment) >', 0);
        if ($year) {
            $this->db->where('invoices.date >=', $year . '-01-01');
            $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        }
        if ($store != -1) {
            $this->db->where("invoices.storeId", $store);
        }
        if ($vendorId) {
            $this->db->where("invoices.vendorId", $vendorId);
        }
        if ($clientId) {
            $this->db->where("invoices.clientId", $clientId);
        }
        $this->db->group_by(array('clients.city', 'invoices.vendorId'));
        $this->db->order_by('total_debt', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Reporte: Rentabilidad por Producto
     */
    public function getProductProfitability($year, $store = -1, $family = null, $sort = 'revenue') {
        $this->db->select("
            products.idProduct,
            products.description,
            pf.name as family_name,
            SUM(invoice_details.quantity) as qty_sold,
            SUM(invoice_details.total) as revenue,
            SUM(invoice_details.quantity * products.cost_cop) as total_cost,
            SUM(invoice_details.total) - SUM(invoice_details.quantity * products.cost_cop) as margin,
            CASE WHEN SUM(invoice_details.total) > 0
                THEN ((SUM(invoice_details.total) - SUM(invoice_details.quantity * products.cost_cop)) / SUM(invoice_details.total)) * 100
                ELSE 0 END as margin_pct
        ", FALSE);
        $this->db->from('invoice_details');
        $this->db->join('invoices', 'invoices.idInvoice = invoice_details.invoiceId');
        $this->db->join('products', 'products.idProduct = invoice_details.productId');
        $this->db->join('product_families pf', 'pf.idFamily = products.family', 'left');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $year . '-01-01');
        $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($store != -1) $this->db->where('invoices.storeId', $store);
        if ($family) $this->db->where('products.family', $family);
        $this->db->group_by('products.idProduct');
        if ($sort == 'margin') {
            $this->db->order_by('margin', 'DESC');
        } else {
            $this->db->order_by('revenue', 'DESC');
        }
        return $this->db->get()->result();
    }

    /**
     * Reporte: Rentabilidad por Vendedor
     */
    public function getVendorProfitability($year, $store = -1) {
        $this->db->select("
            invoices.vendorId,
            users.name as vendor_name,
            stores.name as store_name,
            users.commission_perc,
            COUNT(DISTINCT invoices.idInvoice) as invoice_count,
            SUM(invoices.total - invoices.discount) as revenue,
            SUM(sub.total_cost) as cogs,
            SUM(invoices.total - invoices.discount) - SUM(sub.total_cost) as gross_margin,
            CASE WHEN SUM(invoices.total - invoices.discount) > 0
                THEN ((SUM(invoices.total - invoices.discount) - SUM(sub.total_cost)) / SUM(invoices.total - invoices.discount)) * 100
                ELSE 0 END as margin_pct,
            SUM(invoices.total - invoices.discount) * COALESCE(users.commission_perc, 0) / 100 as commission_earned
        ", FALSE);
        $this->db->from('invoices');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->db->join("(SELECT invoiceId, SUM(quantity * p.cost_cop) as total_cost FROM invoice_details JOIN products p ON p.idProduct = invoice_details.productId GROUP BY invoiceId) sub", 'sub.invoiceId = invoices.idInvoice', 'left');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $year . '-01-01');
        $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($store != -1) $this->db->where('invoices.storeId', $store);
        $this->db->group_by('invoices.vendorId');
        $this->db->order_by('revenue', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Reporte: Comparativo Ventas Ano vs Ano (YoY)
     */
    public function getSalesYoY($yearCurrent, $yearPrevious, $store = -1, $vendor = null) {
        $sql = "SELECT
            m.month_num,
            COALESCE(curr.total, 0) as current_total,
            COALESCE(curr.invoice_count, 0) as current_count,
            COALESCE(prev.total, 0) as previous_total,
            COALESCE(prev.invoice_count, 0) as previous_count
        FROM (SELECT 1 as month_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) m
        LEFT JOIN (
            SELECT MONTH(date) as mes, SUM(total - discount) as total, COUNT(*) as invoice_count
            FROM invoices WHERE deleted = 0 AND YEAR(date) = " . $this->db->escape($yearCurrent);
        if ($store != -1) $sql .= " AND storeId = " . $this->db->escape($store);
        if ($vendor) $sql .= " AND vendorId = " . $this->db->escape($vendor);
        $sql .= " GROUP BY MONTH(date)
        ) curr ON curr.mes = m.month_num
        LEFT JOIN (
            SELECT MONTH(date) as mes, SUM(total - discount) as total, COUNT(*) as invoice_count
            FROM invoices WHERE deleted = 0 AND YEAR(date) = " . $this->db->escape($yearPrevious);
        if ($store != -1) $sql .= " AND storeId = " . $this->db->escape($store);
        if ($vendor) $sql .= " AND vendorId = " . $this->db->escape($vendor);
        $sql .= " GROUP BY MONTH(date)
        ) prev ON prev.mes = m.month_num
        ORDER BY m.month_num";
        return $this->db->query($sql)->result();
    }

    /**
     * Reporte: Top Productos mas Vendidos
     */
    public function getTopProducts($year, $store = -1, $family = null, $topN = 25, $orderBy = 'qty') {
        $this->db->select("
            products.idProduct,
            products.description,
            pf.name as family_name,
            SUM(invoice_details.quantity) as qty_sold,
            SUM(invoice_details.total) as revenue,
            SUM(invoice_details.total) / SUM(invoice_details.quantity) as avg_price,
            SUM(invoice_details.quantity * products.cost_cop) as total_cost,
            CASE WHEN SUM(invoice_details.total) > 0
                THEN ((SUM(invoice_details.total) - SUM(invoice_details.quantity * products.cost_cop)) / SUM(invoice_details.total)) * 100
                ELSE 0 END as margin_pct
        ", FALSE);
        $this->db->from('invoice_details');
        $this->db->join('invoices', 'invoices.idInvoice = invoice_details.invoiceId');
        $this->db->join('products', 'products.idProduct = invoice_details.productId');
        $this->db->join('product_families pf', 'pf.idFamily = products.family', 'left');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $year . '-01-01');
        $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($store != -1) $this->db->where('invoices.storeId', $store);
        if ($family) $this->db->where('products.family', $family);
        $this->db->group_by('products.idProduct');
        $this->db->order_by($orderBy === 'revenue' ? 'revenue' : 'qty_sold', 'DESC');
        $this->db->limit((int)$topN);
        return $this->db->get()->result();
    }

    /**
     * Reporte: Comisiones de Vendedores
     */
    public function getVendorCommissions($year, $month = null, $store = -1) {
        $this->db->select("
            invoices.vendorId,
            users.name as vendor_name,
            stores.name as store_name,
            users.commission_perc,
            MONTH(invoices.date) as mes,
            SUM(invoices.total - invoices.discount) as total_sales,
            SUM(invoices.total - invoices.discount) * COALESCE(users.commission_perc, 0) / 100 as commission_amount
        ", FALSE);
        $this->db->from('invoices');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $year . '-01-01');
        $this->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($month) {
            $this->db->where('invoices.date >=', $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
            $this->db->where('invoices.date <', ($month == 12 ? ((int)$year + 1) . '-01-01' : $year . '-' . str_pad((int)$month + 1, 2, '0', STR_PAD_LEFT) . '-01'));
        }
        if ($store != -1) $this->db->where('invoices.storeId', $store);
        $this->db->group_by(array('invoices.vendorId', 'MONTH(invoices.date)'));
        $this->db->order_by('vendor_name', 'ASC');
        $this->db->order_by('mes', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get vendor settlements (expenses paid to vendors)
     */
    public function getVendorSettlements($year, $vendorId = null) {
        $this->db->select("vendorId, SUM(value) as total_settled");
        $this->db->from('expenses');
        $this->db->where('YEAR(created_at)', $year);
        if ($vendorId) $this->db->where('vendorId', $vendorId);
        $this->db->group_by('vendorId');
        return $this->db->get()->result();
    }

    /**
     * Obtener facturas con guía por vendedor
     *
     * @param string $vendorId ID del vendedor
     * @param string $status Filtro de estado (opcional)
     * @return array
     */
    public function getInvoicesByVendorWithTracking($vendorId, $status = null) {
        $this->db->select('invoices.*,
            clients.name as client_name,
            clients.cellphone as client_cellphone,
            stores.name as store_name');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where('invoices.vendorId', $vendorId);
        $this->db->where('invoices.tracking_number IS NOT NULL');
        $this->db->where('invoices.tracking_number !=', '');
        if ($status) {
            $this->db->where('invoices.tracking_status', $status);
        }
        $this->db->where('invoices.deleted', 0);
        $this->db->order_by('invoices.shipped_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Buscar factura por número de guía
     *
     * @param string $trackingNumber Número de guía
     * @return object|null
     */
    public function getInvoiceByTrackingNumber($trackingNumber) {
        $this->db->select('invoices.*,
            users.name as vendor_name,
            clients.name as client_name,
            stores.name as store_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where('invoices.tracking_number', $trackingNumber);
        $this->db->where('invoices.deleted', 0);
        return $this->db->get()->row();
    }

    /**
     * Obtener resumen de tracking por vendedor
     *
     * @param string $vendorId ID del vendedor
     * @return array Conteo por estado
     */
    public function getTrackingSummaryByVendor($vendorId) {
        $this->db->select('tracking_status, COUNT(*) as count');
        $this->db->from('invoices');
        $this->db->where('vendorId', $vendorId);
        $this->db->where('tracking_number IS NOT NULL');
        $this->db->where('tracking_number !=', '');
        $this->db->where('deleted', 0);
        $this->db->group_by('tracking_status');
        $result = $this->db->get()->result();

        $summary = [
            'pending' => 0,
            'in_transit' => 0,
            'out_for_delivery' => 0,
            'delivered' => 0,
            'returned' => 0,
            'exception' => 0,
            'total' => 0
        ];

        foreach ($result as $row) {
            $status = $row->tracking_status ?: 'pending';
            if (isset($summary[$status])) {
                $summary[$status] = (int)$row->count;
            }
            $summary['total'] += (int)$row->count;
        }

        return $summary;
    }

    /**
     * Obtener facturas recién enviadas (últimos 7 días) sin tracking
     *
     * @return array
     */
    public function getRecentInvoicesWithoutTracking() {
        $this->db->select('invoices.*,
            users.name as vendor_name,
            clients.name as client_name,
            stores.name as store_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where('(invoices.tracking_number IS NULL OR invoices.tracking_number = "")');
        $this->db->where('invoices.state', 0); // Pendiente de pago = recién creada
        $this->db->where('invoices.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $this->db->where('invoices.deleted', 0);
        $this->db->order_by('invoices.date', 'DESC');
        return $this->db->get()->result();
    }
}