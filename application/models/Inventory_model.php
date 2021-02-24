<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_model extends CI_Model {

	public function getInventories(){
		$this->db->select('inventories.*, stores.name as store_name,,
			users.name as user_name');
		$this->db->join('stores', 'inventories.storeId = stores.idStore');
		$this->db->join('users', 'users.idUser = inventories.userId');
	    $this->db->from('inventories');
		$this->db->where("inventories.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getInventory($inventory){
			$this->db->select('inventories.*, stores.name as store_name,,
			users.name as user_name');
		$this->db->join('stores', 'inventories.storeId = stores.idStore');
		$this->db->join('users', 'users.idUser = inventories.userId');
	    $this->db->from('inventories');
		    $this->db->where('inventories.idInventory',$inventory);
			$resultados = $this->db->get();
			return $resultados->row();
		
	}

	public function saveInventory($data){
		return $this->db->insert("inventories",$data);
	}

	public function updateInventory($inventory,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idInventory",$inventory);
		return $this->db->update("inventories",$data);
	}
	public function removeInventory($inventory){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->updateInventory($inventory,$data);
	}

	public function getCount1($inventoryId){
		$this->db->select('count_1_details.*, products.*');
        $this->db->join('products', 'products.idProduct = count_1_details.idProduct');
        $this->db->from('count_1_details');
		$this->db->where("count_1_details.idInventory",$inventoryId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getCurrentInventory($store){
		if($store != -1)
		{
			$this->db->select('inventory.stock, products.*,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
		    $this->db->from('inventory');
		    $this->db->where('inventory.idStore',$store);
			$resultados = $this->db->get();
			return $resultados->result();
		}else
		{
			$this->db->select('SUM(inventory.stock) as stock, products.*,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
			$this->db->group_by('inventory.idProduct');
		    $this->db->from('inventory');
			$resultados = $this->db->get();
			return $resultados->result();
		}
	}

	public function getAllProducts(){
		$this->db->select('products.*,
			product_families.name as family_name,
			providers.name as provider_name,
			CONCAT(products.idProduct, " - " , products.description) AS label', FALSE);
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
        $this->db->from('products');
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProducts($valor){
		$this->db->select('products.*,
			product_families.name as family_name,
			providers.name as provider_name,
			CONCAT(products.idProduct, " - " , products.description) AS label', FALSE);
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
        $this->db->from('products');
        $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProduct($id){
		$this->db->select('products.*,
			product_families.name as family_name,
			providers.name as provider_name');
        $this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
        $this->db->from('products');
		$this->db->where("products.idProduct",$id);
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getStoreProduct($store,$product){
		$this->db->select('inventory.stock, products.*,
				stores.*');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore');
	    $this->db->from('inventory');
	    $this->db->where('inventory.idStore',$store);
	    $this->db->where('inventory.idProduct',$product);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getStoreProducts($valor,$store){
		$this->db->select('inventory.stock, inventory.idStore, products.*,
				stores.*,
			CONCAT(products.idProduct, " - " , products.description) AS label');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore AND inventory.idStore = "'.$store.'"');
	    $this->db->from('inventory');
	    $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
	    //$this->db->where('inventory.idStore',$store);
	    $resultados = $this->db->get();
		return $resultados->result();
	}

	public function save($data){
		return $this->db->insert("inventory",$data);
	}

	public function update($store,$product,$data){
		$this->db->where("idProduct",$product);
		$this->db->where("idStore",$store);
		return $this->db->update("inventory",$data);
	}
	public function remove($store,$product){
		/*date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($product_id,$data);*/
		$this->db->where("idProduct",$product);
		$this->db->where("idStore",$store);
		return $this->db->delete("inventory");
	}/**/
	
}