<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchases_model extends CI_Model {

	public function getPurchases($getOthers, $store, $buyer, $state, $provider, $page = 1, $limit = 20){
		$this->db->select('purchases.*,
			users.name as buyer_name,
			stores.name as store_name,
			providers.idNum as provider_idNum,
			providers.name as provider_name');
        $this->db->join('users', 'users.idUser = purchases.buyerId');
        $this->db->join('providers', 'providers.idProvider = purchases.providerId');
		$this->db->join('stores', 'purchases.storeId = stores.idStore');
        $this->db->from('purchases');
        if(!$getOthers)
        {
        	$this->db->where("purchases.buyerId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
        	$this->db->where("purchases.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("purchases.storeId",$admin_store);
        }
        if($buyer != 'all')
        {
        	$this->db->where("purchases.buyerId",$buyer);
        }
        if($state != 'all')
        {
        	$this->db->where("purchases.state",$state);
        }
        if($provider != 'all')
        {
        	$this->db->where("purchases.providerId",$provider);
        }
        $this->db->where("purchases.deleted",0);
        $this->db->order_by("purchases.state", "asc");
		$this->db->order_by("purchases.date", "asc");
        if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function searchByWord($term, $getOthers, $store, $buyer, $state, $provider, $page = 1, $limit = 20){
		$this->db->select('purchases.*,
			users.name as buyer_name,
			stores.name as store_name,
			providers.idNum as provider_idNum,
			providers.name as provider_name');
        $this->db->join('users', 'users.idUser = purchases.buyerId');
        $this->db->join('providers', 'providers.idProvider = purchases.providerId');
		$this->db->join('stores', 'purchases.storeId = stores.idStore');
        $this->db->where("purchases.deleted",0);
        $this->db->from('purchases');
        
        if(!$getOthers)
        {
        	$this->db->where("purchases.buyerId",$this->session->userdata('user_data')['uname']);
        }
        if($store != 'all')
        {
        	$this->db->where("purchases.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("purchases.storeId",$admin_store);
        }
        if($buyer != 'all')
        {
        	$this->db->where("purchases.buyerId",$buyer);
        }
        if($state != 'all')
        {
        	$this->db->where("purchases.state",$state);
        }
        if($provider != 'all')
        {
        	$this->db->where("purchases.providerId",$provider);
        }
                $this->db->group_start();

        $this->db->like('providers.name', $term);
        $this->db->or_like('purchases.total', $term);
        $this->db->or_like('purchases.idPurchase', $term);
                $this->db->group_end();

        $this->db->where("purchases.deleted",0);
        $this->db->order_by("purchases.state", "asc");
		$this->db->order_by("purchases.date", "asc");
        $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotalSearch($term, $store, $buyer, $state, $provider, $iva, $admin_store) 
    {
        $this->db->join('providers', 'providers.idProvider = purchases.providerId');
        $this->db->from('purchases');
        
    	if($store != 'all')
        {
        	$this->db->where("purchases.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("purchases.storeId",$admin_store);
        }
        if($buyer != 'all')
        {
        	$this->db->where("purchases.buyerId",$buyer);
        }
        if($state != 'all')
        {
        	$this->db->where("purchases.state",$state);
        }
        if($provider != 'all')
        {
        	$this->db->where("purchases.providerId",$provider);
        }
                $this->db->group_start();

        $this->db->like('providers.name', $term);
        $this->db->or_like('purchases.total', $term);
                $this->db->group_end();

        $this->db->where("purchases.deleted",0);
        return $this->db->count_all_results();
    }

    public function getTotal($getOthers, $store, $buyer, $state, $provider, $iva, $admin_store) 
    {
        $this->db->from('purchases');
        if(!$getOthers)
        {
            $this->db->where("purchases.buyerId",$this->session->userdata('user_data')['uname']);
        }
    	if($store != 'all')
        {
        	$this->db->where("purchases.storeId",$store);
        }
        if((!is_array($admin_store) && !empty($admin_store)) || (is_array($admin_store) && sizeof($admin_store) > 0))
        {
            $this->db->where_in("purchases.storeId",$admin_store);
        }
        if($buyer != 'all')
        {
        	$this->db->where("purchases.buyerId",$buyer);
        }
        if($state != 'all')
        {
        	$this->db->where("purchases.state",$state);
        }
        if($provider != 'all')
        {
        	$this->db->where("purchases.providerId",$provider);
        }
        $this->db->where("purchases.deleted",0);
        return $this->db->count_all_results();
    }

	public function getPurchase($id){
		$this->db->select('purchases.*,
			users.name as buyer_name,
			stores.name as store_name,
			providers.idNum as provider_idNum,
            providers.name as provider_name,
			providers.state as provider_state,
			providers.*');
        $this->db->join('users', 'users.idUser = purchases.buyerId');
        $this->db->join('providers', 'providers.idProvider = purchases.providerId');
		$this->db->join('stores', 'purchases.storeId = stores.idStore');
        $this->db->from('purchases');
		$this->db->where("purchases.idPurchase",$id);
		$this->db->where("purchases.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

    public function getPurchasesByDay($store = -1, $from = "", $until = ""){
        $this->db->select('SUM(purchases.total) as total,
            purchases.buyerId as buyerId,
            purchases.storeId as storeId,
            date(purchases.date) as date,
            users.name as buyer_name');
        $this->db->join('users', 'users.idUser = purchases.buyerId');
        $this->db->from('purchases');
        if($store != -1)
            $this->db->where("purchases.storeId",$store);
        //$this->db->where("purchases.buyerId",$buyer);
        if(!empty($from))
        {
            $this->db->where('purchases.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
            $this->db->where('purchases.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
        $this->db->where("purchases.state",0);
        $this->db->where("purchases.deleted",0);
        //$this->db->group_by("day");
        $this->db->group_by("purchases.date");
        //$this->db->group_by("purchases.buyerId");
        $this->db->order_by("date", "asc");
        $this->db->order_by("purchases.buyerId", "asc");
        $resultados = $this->db->get();
        return $resultados->result();
    }

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $this->session->userdata('user_data')['uname'];
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("purchases",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idPurchase",$id);
		return $this->db->update("purchases",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");

		$data  = array(
                    'deleted_at' => date('Y-m-d H:i:s'),
					'deleted_by' => $this->session->userdata('user_data')['uname'],
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idPurchase",$id);
		//return $this->db->delete("purchases");
	}

	public function lastID(){
		return $this->db->insert_id();
	}

    public function printed($idPurchase){
        $data  = array(
                    'printed' => 1
                );
        $this->db->where("idPurchase",$idPurchase);
        return $this->db->update("purchases",$data);
    }

	public function save_detail($data){
		return $this->db->insert("purchase_detail",$data);
	}

	public function update_detail($idPurchase,$idProduct,$data){
		$this->db->where("purchaseId",$idPurchase);
		$this->db->where("productId",$idProduct);
		return $this->db->update("purchase_detail",$data);
	}

	public function getDetails($purchaseId){
		$this->db->select('purchase_detail.*, products.*, purchase_detail.total as subtotal');
        $this->db->join('products', 'products.idProduct = purchase_detail.productId');
        $this->db->from('purchase_detail');
		$this->db->where("purchase_detail.purchaseId",$purchaseId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function removeDetails($purchaseId){
		$this->db->where("purchase_detail.purchaseId",$purchaseId);
        $this->db->delete('purchase_detail');
	}


}