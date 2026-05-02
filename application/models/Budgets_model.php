<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budgets_model extends CI_Model {

	public function getBudgets($getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20, $type = 'all'){
		$this->db->select('budgets.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
            clients.is_new as client_new,
            clients.cellphone as client_cellphone,
            clients.phone as client_phone,
            ua.name as almacenista_name,
            inv.idInvoice as invoice_id');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
		$this->db->join('stores', 'budgets.storeId = stores.idStore');
        $this->db->join('users ua', 'ua.idUser = budgets.asignado_a', 'left');
        $this->db->join('invoices inv', 'inv.budgetId = budgets.idBudget AND inv.deleted = 0', 'left');
        $this->db->from('budgets');
        $userData = $this->session->userdata('user_data');
        if(!$getOthers)
        {
            $role = isset($userData['role']) ? $userData['role'] : 0;
            $uname = isset($userData['uname']) ? $userData['uname'] : '';
            if ($role == 4) {
                // Almacenista/Bodeguero: solo ve presupuestos asignados a él
                $this->db->where("budgets.asignado_a", $uname);
            } else {
                $this->db->where("budgets.vendorId", $uname);
            }
        }
        if($store != 'all')
        {
        	$this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
        if($type != 'all')
        {
            $this->db->where("budgets.budget_type",$type);
        }
        $this->db->where("budgets.archived",0);
		$this->db->where("budgets.deleted",0);
        $this->db->order_by("FIELD(budgets.state, 0, 2, 1, 4)", "", false);
		$this->db->order_by("budgets.date", "desc");
        if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function searchByWord($term, $getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20){
		$this->db->select('budgets.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name,
            clients.is_new as client_new');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
		$this->db->join('stores', 'budgets.storeId = stores.idStore');
        $this->db->where("budgets.archived",0);
        $this->db->where("budgets.deleted",0);
        $this->db->from('budgets');
        
        if(!$getOthers)
        {
        	$this->db->where("budgets.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
        	$this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
                $this->db->group_start();

        $this->db->like('clients.name', $term);
        $this->db->or_like('budgets.total', $term);
        $this->db->or_like('budgets.idBudget', $term);
                $this->db->group_end();

        $this->db->where("budgets.archived",0);
		$this->db->where("budgets.deleted",0);
        $this->db->order_by("FIELD(budgets.state, 0, 2, 1, 4)", "", false);
		$this->db->order_by("budgets.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotalSearch($term, $store, $vendor, $state, $client, $iva, $admin_store) 
    {
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
        $this->db->from('budgets');
        
    	if($store != 'all')
        {
        	$this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
                $this->db->group_start();

        $this->db->like('clients.name', $term);
        $this->db->or_like('budgets.total', $term);
                $this->db->group_end();

        $this->db->where("budgets.archived",0);
    	$this->db->where("budgets.deleted",0);
        return $this->db->count_all_results();
    }

    public function getTotal($getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $type = 'all')
    {
        $this->db->from('budgets');
        if(!$getOthers)
        {
            $this->db->where("budgets.vendorId",$this->session->userdata('user_data')['uname']);
        }
    	if($store != 'all')
        {
        	$this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
        	$this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
        	$this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
        	$this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
        if($type != 'all')
        {
            $this->db->where("budgets.budget_type",$type);
        }
        $this->db->where("budgets.archived",0);
    	$this->db->where("budgets.deleted",0);
        return $this->db->count_all_results();
    }

	public function getBudget($id){
		$this->db->select('clients.*,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
            clients.name as client_name,
			clients.state as client_state,
			budgets.*');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
		$this->db->join('stores', 'budgets.storeId = stores.idStore');
        $this->db->from('budgets');
		$this->db->where("budgets.idBudget",$id);
		$this->db->where("budgets.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

    public function getBudgetsByDay($store = -1, $from = "", $until = ""){
        $this->db->select('SUM(budgets.total) as total,
            budgets.vendorId as vendorId,
            budgets.storeId as storeId,
            date(budgets.date) as date,
            users.name as vendor_name');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->from('budgets');
        if($store != -1)
            $this->db->where("budgets.storeId",$store);
        //$this->db->where("budgets.vendorId",$vendor);
        if(!empty($from))
        {
            $this->db->where('budgets.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
            $this->db->where('budgets.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
        $this->db->where("budgets.state",0);
        $this->db->where("budgets.deleted",0);
        //$this->db->group_by("day");
        $this->db->group_by("budgets.date");
        //$this->db->group_by("budgets.vendorId");
        $this->db->order_by("date", "asc");
        $this->db->order_by("budgets.vendorId", "asc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
        $user_data = $this->session->userdata('user_data');
        $data['created_by'] = isset($user_data['uname']) ? $user_data['uname'] : 'cron';
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("budgets",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idBudget",$id);
		return $this->db->update("budgets",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");

		$data  = array(
                    'deleted_at' => date('Y-m-d H:i:s'),
					'deleted_by' => $this->session->userdata('user_data')['uname'],
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idBudget",$id);
		//return $this->db->delete("budgets");
	}

	public function lastID(){
		return $this->db->insert_id();
	}

    public function printed($idBudget){
        $data  = array(
                    'printed' => 1
                );
        $this->db->where("idBudget",$idBudget);
        return $this->db->update("budgets",$data);
    }

	public function save_detail($data){
		return $this->db->insert("budget_detail",$data);
	}

	public function update_detail($idBudget,$idProduct,$data){
		$this->db->where("budgetId",$idBudget);
		$this->db->where("productId",$idProduct);
		return $this->db->update("budget_detail",$data);
	}

	public function getDetails($budgetId){
		$this->db->select('budget_detail.*, products.*, budget_detail.total as subtotal');
        $this->db->join('products', 'products.idProduct = budget_detail.productId');
        $this->db->from('budget_detail');
		$this->db->where("budget_detail.budgetId",$budgetId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function removeDetails($budgetId){
		$this->db->where("budget_detail.budgetId",$budgetId);
        $this->db->delete('budget_detail');
	}


    /******** Archivadsos ********/
public function getArchivedBudgets($getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20){
        $this->db->select('budgets.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.is_new as client_new');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
        $this->db->join('stores', 'budgets.storeId = stores.idStore');
        $this->db->from('budgets');
        if(!$getOthers)
        {
            $this->db->where("budgets.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
            $this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
            $this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
            $this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
            $this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
        $this->db->where("budgets.archived",1);
        $this->db->where("budgets.deleted",0);
        $this->db->order_by("FIELD(budgets.state, 0, 2, 1, 4)", "", false);
        $this->db->order_by("budgets.date", "desc");
        if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function searchArchivedByWord($term, $getOthers, $store, $vendor, $state, $client, $iva, $admin_store, $page = 1, $limit = 20){
        $this->db->select('budgets.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name,
            clients.is_new as client_new');
        $this->db->join('users', 'users.idUser = budgets.vendorId');
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
        $this->db->join('stores', 'budgets.storeId = stores.idStore');
        $this->db->from('budgets');
        
        if(!$getOthers)
        {
            $this->db->where("budgets.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
            $this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
            $this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
            $this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
            $this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
                $this->db->group_start();

        $this->db->like('clients.name', $term);
        $this->db->or_like('budgets.total', $term);
        $this->db->or_like('budgets.idBudget', $term);
                $this->db->group_end();

        $this->db->where("budgets.archived",1);
        $this->db->where("budgets.deleted",0);
        $this->db->order_by("FIELD(budgets.state, 0, 2, 1, 4)", "", false);
        $this->db->order_by("budgets.date", "desc");
        $this->db->limit($limit, (($page-1) * $limit));
        $resultados = $this->db->get();
        return $resultados->result();
    }

    public function getTotalArchivedSearch($term, $store, $vendor, $state, $client, $iva, $admin_store) 
    {
        $this->db->join('clients', 'clients.idClient = budgets.clientId');
        $this->db->from('budgets');
        
        if($store != 'all')
        {
            $this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
            $this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
            $this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
            $this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
                $this->db->group_start();

        $this->db->like('clients.name', $term);
        $this->db->or_like('budgets.total', $term);
                $this->db->group_end();

        $this->db->where("budgets.archived",1);
        $this->db->where("budgets.deleted",0);
        return $this->db->count_all_results();
    }

    public function getTotalArchived($getOthers, $store, $vendor, $state, $client, $iva, $admin_store) 
    {
        $this->db->from('budgets');
        if(!$getOthers)
        {
            $this->db->where("budgets.vendorId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
            $this->db->where("budgets.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("budgets.storeId",$admin_store);
        }
        if($vendor != 'all')
        {
            $this->db->where("budgets.vendorId",$vendor);
        }
        if($state != 'all')
        {
            $this->db->where("budgets.state",$state);
        }
        if($client != 'all')
        {
            $this->db->where("budgets.clientId",$client);
        }
        if($iva != 'all')
        {
            $this->db->where("budgets.hasIva",$iva);
        }
        $this->db->where("budgets.archived",1);
        $this->db->where("budgets.deleted",0);
        return $this->db->count_all_results();
    }

    /*****************************/
}