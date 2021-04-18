<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_model extends CI_Model {

	public function getInventories(){
		$this->db->select('inventories.*, stores.name as store_name,
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
	public function compareInventory($inventory){
		$resultados = $this->db->query('SELECT A.idInventory, A.idProduct, A.quantity AS quantity_1, B.quantity AS quantity_2, products.description
			FROM count_1_details A 
			LEFT OUTER JOIN count_2_details B 
			ON A.idProduct = B.idProduct AND A.idInventory = '.$inventory.' AND B.idInventory = '.$inventory.'
			INNER JOIN products
			ON A.idProduct = products.idProduct
			WHERE B.quantity != A.quantity');
		
		return $resultados->result();
	}
	public function finalInventory($inventory){
		$resultados = $this->db->query('INSERT INTO final_count_details (`idInventory`,`idProduct`,`quantity`) SELECT `idInventory`,`idProduct`,`quantity` FROM count_1_details WHERE `idInventory`='.$inventory);
		//return $resultados->result();
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

	public function getCount1InventoryProduct($idInventory,$product){
		$this->db->select('count_1_details.quantity, products.*');
		$this->db->join('products', 'count_1_details.idProduct = products.idProduct');
	    $this->db->from('count_1_details');
	    $this->db->where('count_1_details.idInventory',$idInventory);
	    $this->db->where('count_1_details.idProduct',$product);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function saveCount1($data){
		return $this->db->insert("count_1_details",$data);
	}

	public function updateCount1($idInventory,$product,$data){
		$this->db->where("idProduct",$product);
		$this->db->where("idInventory",$idInventory);
		return $this->db->update("count_1_details",$data);
	}

	public function removeCount1($idInventory,$product){
		$this->db->where("idProduct",$product);
		$this->db->where("idInventory",$idInventory);
		return $this->db->delete("count_1_details");
	}

	/***/

	public function getCount2($inventoryId){
		$this->db->select('count_2_details.*, products.*');
        $this->db->join('products', 'products.idProduct = count_2_details.idProduct');
        $this->db->from('count_2_details');
		$this->db->where("count_2_details.idInventory",$inventoryId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getCount2InventoryProduct($idInventory,$product){
		$this->db->select('count_2_details.quantity, products.*');
		$this->db->join('products', 'count_2_details.idProduct = products.idProduct');
	    $this->db->from('count_2_details');
	    $this->db->where('count_2_details.idInventory',$idInventory);
	    $this->db->where('count_2_details.idProduct',$product);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function saveCount2($data){
		return $this->db->insert("count_2_details",$data);
	}

	public function updateCount2($idInventory,$product,$data){
		$this->db->where("idProduct",$product);
		$this->db->where("idInventory",$idInventory);
		return $this->db->update("count_2_details",$data);
	}

	public function removeCount2($idInventory,$product){
		$this->db->where("idProduct",$product);
		$this->db->where("idInventory",$idInventory);
		return $this->db->delete("count_2_details");
	}

	/***/

	public function getFinal($inventoryId){
		$this->db->select('final_count_details.*, products.*');
        $this->db->join('products', 'products.idProduct = final_count_details.idProduct');
        $this->db->from('final_count_details');
		$this->db->where("final_count_details.idInventory",$inventoryId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getFinalInventoryProduct($idInventory,$product){
		$this->db->select('final_count_details.quantity, products.*');
		$this->db->join('products', 'final_count_details.idProduct = products.idProduct');
	    $this->db->from('final_count_details');
	    $this->db->where('final_count_details.idInventory',$idInventory);
	    $this->db->where('final_count_details.idProduct',$product);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function saveFinal($data){
		return $this->db->insert("final_count_details",$data);
	}

	public function updateFinal($idInventory,$product,$data){
		$this->db->where("idProduct",$product);
		$this->db->where("idInventory",$idInventory);
		return $this->db->update("final_count_details",$data);
	}

	public function removeFinalTotal($idInventory){
		$this->db->where("idInventory",$idInventory);
		return $this->db->delete("final_count_details");
	}
	public function removeFinal($idInventory,$product){
		$this->db->where("idProduct",$product);
		$this->db->where("idInventory",$idInventory);
		return $this->db->delete("final_count_details");
	}

	/***/

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