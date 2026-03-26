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

	public function getCounts($page = -1, $limit = 10){
		$this->db->select('counts.*, stores.name as store_name,
			users.name as user_name');
		$this->db->join('stores', 'counts.storeId = stores.idStore');
		$this->db->join('users', 'users.idUser = counts.userId');
	    $this->db->from('counts');
		$this->db->where("counts.deleted",0);
		$this->db->order_by("counts.date", "desc");
		if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getCount($count){
			$this->db->select('counts.*, stores.name as store_name,,
			users.name as user_name');
		$this->db->join('stores', 'counts.storeId = stores.idStore');
		$this->db->join('users', 'users.idUser = counts.userId');
	    $this->db->from('counts');
		    $this->db->where('counts.idCount',$count);
			$resultados = $this->db->get();
			return $resultados->row();
		
	}

	public function getTotalCount() 
    {
        $this->db->from('counts');
    	$this->db->where("counts.deleted",0);
        return $this->db->count_all_results();
    }

	public function saveCount($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("counts",$data);
	}

	public function saveCountDetails($data){
		return $this->db->insert("count_details",$data);
	}

	public function updateCountDetails($idCount,$product,$data){
		$this->db->where("idCount",$idCount);
		$this->db->where("idProduct",$product);
		return $this->db->update("count_details",$data);
	}

	public function getCountDetails($countId){
		$this->db->select('count_details.*, products.*');
        $this->db->join('products', 'products.idProduct = count_details.idProduct');
        $this->db->from('count_details');
		$this->db->where("count_details.idCount",$countId);
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function resetCount($store){
		$data['counted'] = 0;
		$this->db->where("idStore",$store);
		return $this->db->update("inventory",$data);
	}

	public function lastID(){
		return $this->db->insert_id();
	}

	public function getCurrentInventories(){
		
		$this->db->select('SUM(inventory.stock) as stock, products.*,
			product_families.name as family_name,
			stores.*');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('stores', 'inventory.idStore = stores.idStore');
	    $this->db->from('inventory');
    	$this->db->where('stock >',0);
	    $this->db->order_by("products.family", "ASC");
		$this->db->order_by("inventory.idProduct", "ASC");
		$this->db->group_by('inventory.idStore');
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getCurrentCount($store, $limit = 10){
		$this->db->select('inventory.stock, products.*,
			product_families.name as family_name,
			stores.*');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('stores', 'inventory.idStore = stores.idStore');
	    $this->db->from('inventory');
	    $this->db->where('inventory.idStore',$store);
    	$this->db->where('inventory.counted',0);
	    $this->db->limit($limit);
		$this->db->order_by("products.family", "ASC");
		$this->db->order_by("inventory.idProduct", "ASC");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getCurrentInventory($store, $page = -1, $limit = 20){
		if($store != -1)
		{
			$this->db->select('inventory.stock, products.*,
				product_families.name as family_name,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('product_families', 'product_families.idFamily = products.family');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
		    $this->db->from('inventory');
		    $this->db->where('inventory.idStore',$store);
	    	$this->db->where('inventory.stock >',0);
		    if($page != -1)
		    	$this->db->limit($limit, (($page-1) * $limit));
			$this->db->order_by("products.family", "ASC");
			$this->db->order_by("inventory.idProduct", "ASC");
			$resultados = $this->db->get();
			return $resultados->result();
		}else
		{
			$this->db->select('SUM(inventory.stock) as stock, products.*,
				product_families.name as family_name,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('product_families', 'product_families.idFamily = products.family');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
			$this->db->group_by('inventory.idProduct');
		    $this->db->from('inventory');
	    	$this->db->where('stock >',0);
		    if($page != -1)
		    	$this->db->limit($limit, (($page-1) * $limit));
			$this->db->order_by("products.family", "ASC");
			$this->db->order_by("inventory.idProduct", "ASC");
			$resultados = $this->db->get();
			return $resultados->result();
		}
	}

	public function getCurrentInventoryCount($store){
		if($store != -1)
		{
			$this->db->select('inventory.stock, products.*,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
		    $this->db->from('inventory');
		    $this->db->where('inventory.idStore',$store);
	    	$this->db->where('inventory.stock >',0);
			$resultados = $this->db->get();
			return $resultados->num_rows();
		}else
		{
			$this->db->select('SUM(inventory.stock) as stock, products.*,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
			$this->db->group_by('inventory.idProduct');
	    	$this->db->where('stock >',0);
		    $this->db->from('inventory');
			$resultados = $this->db->get();
			return $resultados->num_rows();
		}
	}

	public function getCurrentInventoryByWord($valor, $store, $page = -1, $limit = 20){
		if($store != -1)
		{
			$this->db->select('inventory.stock, products.*,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
		    $this->db->from('inventory');
		    $this->db->where('inventory.idStore',$store);
		    $this->db->where("(products.idProduct LIKE '%".$valor."%' OR products.description LIKE '%".$valor."%')", NULL, FALSE);
        	//$this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
	    	//$this->db->where('inventory.stock >',0);
		    if($page != -1)
		    	$this->db->limit($limit, (($page-1) * $limit));
			$this->db->order_by("inventory.idProduct", "ASC");
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
		    $this->db->where("(products.idProduct LIKE '%".$valor."%' OR products.description LIKE '%".$valor."%')", NULL, FALSE);
        	//$this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
		    if($page != -1)
		    	$this->db->limit($limit, (($page-1) * $limit));
			$this->db->order_by("inventory.idProduct", "ASC");
			$resultados = $this->db->get();
			return $resultados->result();
		}
	}

	public function getCurrentInventorySearchCount($store, $valor, $page = 1, $limit = 20){
		if($store != -1)
		{
			$this->db->select('inventory.stock, products.*,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
		    $this->db->from('inventory');
		    $this->db->where('inventory.idStore',$store);
		    $this->db->where("(products.idProduct LIKE '%".$valor."%' OR products.description LIKE '%".$valor."%')", NULL, FALSE);
        	//$this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
			$resultados = $this->db->get();
			return $resultados->num_rows();
		}else
		{
			$this->db->select('SUM(inventory.stock) as stock, products.*,
				stores.*');
			$this->db->join('products', 'inventory.idProduct = products.idProduct');
			$this->db->join('stores', 'inventory.idStore = stores.idStore');
			$this->db->group_by('inventory.idProduct');
		    $this->db->from('inventory');
		    $this->db->where("(products.idProduct LIKE '%".$valor."%' OR products.description LIKE '%".$valor."%')", NULL, FALSE);
        	//$this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
			$resultados = $this->db->get();
			return $resultados->num_rows();
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
		$this->db->group_start(); // Start of the bracketed group
        $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
		$this->db->group_end(); // End of the bracketed group
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function searchProducts($valor, $store){
		$this->db->select('products.*,
			product_families.name as family_name,
			product_datasheets.name as datasheet_name,
			inventory.stock as stock,
			providers.name as provider_name,
			CONCAT(products.idProduct, " - " , products.description) AS label', FALSE);
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
		$this->db->join('inventory', 'inventory.idProduct = products.idProduct AND inventory.idStore = "'.$store.'"');
		$this->db->join('product_datasheets', 'product_datasheets.idDatasheet = products.datasheet', 'left');
        $this->db->from('products');
		$this->db->group_start(); // Start of the bracketed group
        $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
		$this->db->group_end(); // End of the bracketed group
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function searchAllProducts($valor){
		$this->db->select('products.*,
			product_families.name as family_name,
			product_datasheets.name as datasheet_name,
			providers.name as provider_name,
			CONCAT(products.idProduct, " - " , products.description) AS label', FALSE);
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
		$this->db->join('product_datasheets', 'product_datasheets.idDatasheet = products.datasheet', 'left');
        $this->db->from('products');
		$this->db->group_start(); // Start of the bracketed group
        $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
		$this->db->group_end(); // End of the bracketed group
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProduct($id){
		$this->db->select('products.*,
			product_families.name as family_name,
			product_datasheets.name as datasheet_name,
			providers.name as provider_name');
        $this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
		$this->db->join('product_datasheets', 'product_datasheets.idDatasheet = products.datasheet', 'left');
        $this->db->from('products');
		$this->db->where("products.idProduct",$id);
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function removeStoreProducts($store){
		$this->db->where("idStore",$store);
		return $this->db->delete("inventory");
	}

	public function getAllStoreProducts($store){
		$this->db->select('inventory.stock, inventory.counted, products.*,
				stores.*');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore');
	    $this->db->from('inventory');
	    $this->db->where('inventory.idStore',$store);
		$resultados = $this->db->get();
		return $resultados->result();
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
		$this->db->group_start(); // Start of the bracketed group
	    $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
		$this->db->group_end(); // End of the bracketed group
	    $this->db->where('inventory.stock >',0);
	    $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getFamilyProducts($store,$family){
		$this->db->select('inventory.stock, products.*,
				stores.*');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore');
	    $this->db->from('inventory');
	    $this->db->where('inventory.idStore',$store);
	    $this->db->where('products.family',$family);
	    $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getSectionProducts($store,$section){
		$this->db->select('inventory.stock, products.*,
				stores.*');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore');
	    $this->db->from('inventory');
	    $this->db->where('inventory.idStore',$store);
	    $this->db->where('products.section',$section);
	    $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getLowInventoryProducts($store, $page = 1, $limit = 20){
		
		$this->db->select('inventory.stock, inventory.idStore, products.*,
				stores.name as store_name');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore AND inventory.idStore = "'.$store.'"');
	    $this->db->from('inventory');
	    $this->db->where('inventory.stock <= products.min && inventory.stock > \'0\'');
	    $this->db->order_by('inventory.stock','desc');
        $this->db->limit($limit, (($page-1) * $limit));
	    $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getNoInventoryProducts($store, $page = 1, $limit = 20){
		
		$this->db->select('inventory.stock, inventory.idStore, products.*,
				stores.name as store_name');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore AND inventory.idStore = "'.$store.'"');
	    $this->db->from('inventory');
	    $this->db->where('inventory.stock <= \'0\'');
	    $this->db->order_by('inventory.updated_at','desc');
	    $this->db->order_by('products.idProduct','asc');
        $this->db->limit($limit, (($page-1) * $limit));
	    $resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotal($store) 
    {
        $this->db->select('inventory.stock, inventory.idStore, products.*,
				stores.name as store_name');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore AND inventory.idStore = "'.$store.'"');
	    $this->db->from('inventory');
	    $this->db->where('inventory.stock <= products.min && inventory.stock > \'0\'');
        return $this->db->count_all_results();
    }

    public function getTotalNoInve($store) 
    {
        $this->db->select('inventory.stock, inventory.idStore, products.*,
				stores.name as store_name');
		$this->db->join('products', 'inventory.idProduct = products.idProduct');
		$this->db->join('stores', 'inventory.idStore = stores.idStore AND inventory.idStore = "'.$store.'"');
	    $this->db->from('inventory');
	    $this->db->where('inventory.stock <= \'0\'');
        return $this->db->count_all_results();
    }

	public function save($data){
		return $this->db->insert("inventory",$data);
	}

	public function update($store,$product,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
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
	
public function getVentasStock($store, $page = -1, $limit = 20, $activeStores = [])
{
    // Subconsulta: ventas por producto (filtra si se seleccionó tienda específica)
    $ventas_query = $this->db
        ->select('invoice_details.productId, SUM(invoice_details.quantity) as ventas')
        ->from('invoice_details')
        ->join('invoices', 'invoices.idInvoice = invoice_details.invoiceId');

    if ($store != -1) {
        $ventas_query->where('invoices.storeId', $store);
    }

    $ventas_query = $ventas_query
        ->where('invoices.date >=', 'CURDATE() - INTERVAL 12 MONTH', false)
        ->group_by('invoice_details.productId')
        ->get_compiled_select();

    // Subconsulta: stock actual total + por tienda (dinámico)
    $storeCols = "idProduct, SUM(stock) AS stock_total";
    foreach ($activeStores as $s) {
        $id = (int)$s->idStore;
        $storeCols .= ", MAX(CASE WHEN idStore = {$id} THEN stock ELSE 0 END) AS stock_store_{$id}";
    }

    $stock_query = $this->db
        ->select($storeCols)
        ->from('inventory')
        ->group_by('idProduct')
        ->get_compiled_select();

    // Columnas dinámicas por tienda
    $storeSelect = "";
    foreach ($activeStores as $s) {
        $id = (int)$s->idStore;
        $storeSelect .= ", COALESCE(s.stock_store_{$id}, 0) AS stock_store_{$id}";
    }

    $this->db->select("
        p.idProduct,
        p.description AS nombre_producto,
        p.picture_url,
        COALESCE(s.stock_total, 0) AS stock,
        COALESCE(v.ventas, 0) AS ventas,
        FLOOR(COALESCE(v.ventas, 0) / 12) AS promedio_mensual,
        FLOOR(COALESCE(v.ventas, 0) / 12) AS inventario_optimo,
        GREATEST(FLOOR(COALESCE(v.ventas, 0) / 12) - COALESCE(s.stock_total, 0), 0) AS orden_sugerida
        {$storeSelect}
    ");
    $this->db->from('products p');
    $this->db->join("($ventas_query) v", 'p.idProduct = v.productId', 'left');
    $this->db->join("($stock_query) s", 'p.idProduct = s.idProduct', 'left');

    if ($page != -1) {
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);
    }

    $this->db->order_by("promedio_mensual", "DESC");

    return $this->db->get()->result();
}

    /**
     * Reporte: Inventario Valorizado por Bodega
     */
    public function getValorizedInventory($store = -1, $family = null) {
        $this->db->select("
            inventory.idStore,
            stores.name as store_name,
            products.idProduct,
            products.description,
            pf.name as family_name,
            inventory.stock,
            products.cost_cop,
            (inventory.stock * products.cost_cop) as value
        ", FALSE);
        $this->db->from('inventory');
        $this->db->join('products', 'products.idProduct = inventory.idProduct');
        $this->db->join('stores', 'stores.idStore = inventory.idStore');
        $this->db->join('product_families pf', 'pf.idFamily = products.family', 'left');
        $this->db->where('inventory.stock >', 0);
        if ($store != -1) $this->db->where('inventory.idStore', $store);
        if ($family) $this->db->where('products.family', $family);
        $this->db->order_by('stores.name', 'ASC');
        $this->db->order_by('value', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Reporte: Inventario Valorizado resumen por bodega
     */
    public function getValorizedInventorySummary($store = -1, $family = null) {
        $this->db->select("
            inventory.idStore,
            stores.name as store_name,
            COUNT(DISTINCT inventory.idProduct) as product_count,
            SUM(inventory.stock) as total_units,
            SUM(inventory.stock * products.cost_cop) as total_value
        ", FALSE);
        $this->db->from('inventory');
        $this->db->join('products', 'products.idProduct = inventory.idProduct');
        $this->db->join('stores', 'stores.idStore = inventory.idStore');
        $this->db->join('product_families pf', 'pf.idFamily = products.family', 'left');
        $this->db->where('inventory.stock >', 0);
        if ($store != -1) $this->db->where('inventory.idStore', $store);
        if ($family) $this->db->where('products.family', $family);
        $this->db->group_by('inventory.idStore');
        $this->db->order_by('total_value', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Reporte: Rotacion de Inventario
     */
    public function getInventoryRotation($store = -1, $family = null) {
        $sql = "SELECT
            p.idProduct,
            p.description,
            pf.name as family_name,
            COALESCE(inv.stock, 0) as stock,
            COALESCE(sales.qty_sold, 0) as qty_sold,
            COALESCE(sales.last_sale, NULL) as last_sale,
            CASE WHEN COALESCE(inv.stock, 0) > 0
                THEN COALESCE(sales.qty_sold, 0) / inv.stock
                ELSE 0 END as rotation_index,
            CASE WHEN COALESCE(sales.qty_sold, 0) > 0
                THEN ROUND(COALESCE(inv.stock, 0) / (COALESCE(sales.qty_sold, 0) / 365), 0)
                ELSE 9999 END as days_of_stock
        FROM products p
        LEFT JOIN product_families pf ON pf.idFamily = p.family
        LEFT JOIN (
            SELECT idProduct, SUM(stock) as stock
            FROM inventory";
        if ($store != -1) $sql .= " WHERE idStore = " . $this->db->escape($store);
        $sql .= " GROUP BY idProduct
        ) inv ON inv.idProduct = p.idProduct
        LEFT JOIN (
            SELECT id2.productId, SUM(id2.quantity) as qty_sold, MAX(i2.date) as last_sale
            FROM invoice_details id2
            JOIN invoices i2 ON i2.idInvoice = id2.invoiceId
            WHERE i2.deleted = 0 AND i2.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        if ($store != -1) $sql .= " AND i2.storeId = " . $this->db->escape($store);
        $sql .= " GROUP BY id2.productId
        ) sales ON sales.productId = p.idProduct
        WHERE p.deleted = 0 AND (COALESCE(inv.stock, 0) > 0 OR COALESCE(sales.qty_sold, 0) > 0)";
        if ($family) $sql .= " AND p.family = " . $this->db->escape($family);
        $sql .= " ORDER BY rotation_index DESC";
        return $this->db->query($sql)->result();
    }

    /**
     * Get product families list
     */
    public function getProductFamilies() {
        $this->db->select('*');
        $this->db->from('product_families');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }

    // ====================================================================
    // ABC Classification & Reorder Engine
    // ====================================================================

    /**
     * Obtiene productos con datos ABC para una tienda específica.
     * Incluye: ingresos 12m, unidades vendidas, demanda mensual, stock actual.
     */
    public function getAbcData($storeId = -1) {
        date_default_timezone_set("America/Bogota");
        $dateFrom = date('Y-m-d', strtotime('-12 months'));

        $storeFilter = '';
        $params = array($dateFrom);
        if ($storeId != -1) {
            $storeFilter = 'AND i.storeId = ?';
            $params[] = $storeId;
        }

        $stockJoin = ($storeId != -1)
            ? "LEFT JOIN inventory inv ON inv.idProduct = p.idProduct AND inv.idStore = {$this->db->escape($storeId)}"
            : "LEFT JOIN (SELECT idProduct, SUM(stock) as stock FROM inventory GROUP BY idProduct) inv ON inv.idProduct = p.idProduct";

        $sql = "SELECT
                    p.idProduct, p.description, p.abc_type, p.provider,
                    pf.name as family_name,
                    prov.name as provider_name,
                    COALESCE(v.revenue, 0) as revenue_12m,
                    COALESCE(v.units_sold, 0) as units_12m,
                    ROUND(COALESCE(v.units_sold, 0) / 12) as demand_monthly,
                    COALESCE(inv.stock, 0) as stock_actual
                FROM products p
                LEFT JOIN product_families pf ON pf.idFamily = p.family
                LEFT JOIN providers prov ON prov.idProvider = p.provider
                LEFT JOIN (
                    SELECT id.productId,
                        SUM(id.total) as revenue,
                        SUM(id.quantity) as units_sold
                    FROM invoice_details id
                    JOIN invoices i ON i.idInvoice = id.invoiceId
                    WHERE i.date >= ? AND i.deleted = 0 {$storeFilter}
                    GROUP BY id.productId
                ) v ON v.productId = p.idProduct
                {$stockJoin}
                WHERE p.deleted = 0
                ORDER BY revenue_12m DESC";

        return $this->db->query($sql, $params)->result();
    }

    /**
     * Calcula y almacena la clasificación ABC en la tabla products.
     * Pareto: A = 80% ingresos, B = siguiente 15%, C = resto, N = sin ventas.
     */
    public function calculateAndStoreAbc($storeId = -1) {
        date_default_timezone_set("America/Bogota");
        $products = $this->getAbcData($storeId);

        $totalRevenue = 0;
        foreach ($products as $p) {
            $totalRevenue += (float) $p->revenue_12m;
        }

        if ($totalRevenue == 0) return 0;

        $accumulated = 0;
        $updates = array();

        foreach ($products as $p) {
            $revenue = (float) $p->revenue_12m;

            if ($revenue == 0) {
                $type = 'N';
            } else {
                $accumulated += $revenue;
                $pct = ($accumulated / $totalRevenue) * 100;

                if ($pct <= 80) {
                    $type = 'A';
                } elseif ($pct <= 95) {
                    $type = 'B';
                } else {
                    $type = 'C';
                }
            }

            $updates[] = array(
                'idProduct' => $p->idProduct,
                'abc_type' => $type,
                'abc_revenue' => $revenue,
                'abc_calculated_at' => date('Y-m-d H:i:s')
            );
        }

        // Batch update
        $this->db->update_batch('products', $updates, 'idProduct');

        return count($updates);
    }

    /**
     * Genera sugerencias de reorden para una tienda específica.
     * Agrupa por proveedor default de cada producto.
     *
     * Fórmula:
     *   demanda_mensual = ventas_12m / 12
     *   meses_objetivo  = A:3, B:2, C:1
     *   safety          = A:1.15, B:1.0, C:1.0
     *   stock_objetivo  = demanda_mensual × meses_objetivo × safety
     *   necesidad       = stock_objetivo - stock_actual - en_tránsito
     */
    public function getReorderSuggestions($storeId) {
        date_default_timezone_set("America/Bogota");
        $dateFrom = date('Y-m-d', strtotime('-12 months'));

        // 1. Ventas por producto en esta tienda (12 meses)
        $sales = $this->db->query(
            "SELECT id.productId, SUM(id.quantity) as units_sold
             FROM invoice_details id
             JOIN invoices i ON i.idInvoice = id.invoiceId
             WHERE i.date >= ? AND i.deleted = 0 AND i.storeId = ?
             GROUP BY id.productId",
            array($dateFrom, $storeId)
        )->result();

        $salesMap = array();
        foreach ($sales as $s) {
            $salesMap[$s->productId] = (int) $s->units_sold;
        }

        // 2. Stock actual en esta tienda
        $stocks = $this->db->query(
            "SELECT idProduct, stock FROM inventory WHERE idStore = ?",
            array($storeId)
        )->result();

        $stockMap = array();
        foreach ($stocks as $s) {
            $stockMap[$s->idProduct] = (int) $s->stock;
        }

        // 3. En tránsito para esta tienda
        $this->load->model('supplierorders_model');
        $transitItems = $this->supplierorders_model->getInTransitByStore($storeId);
        $transitMap = array();
        foreach ($transitItems as $t) {
            $transitMap[$t->productId] = (int) $t->in_transit;
        }

        // 4. Productos con ABC y proveedor default
        $products = $this->db->query(
            "SELECT p.idProduct, p.description, p.abc_type, p.cost_cop,
                    pf.name as family_name,
                    COALESCE(pp.providerId, p.provider) as providerId,
                    COALESCE(prov.name, prov2.name) as provider_name,
                    COALESCE(pp.cost, p.cost_cop) as unit_cost,
                    COALESCE(pp.leadTimeDays, 30) as lead_time,
                    COALESCE(pp.minOrderQty, 1) as min_order_qty
             FROM products p
             LEFT JOIN product_providers pp ON pp.productId = p.idProduct AND pp.isDefault = 1 AND pp.is_active = 1
             LEFT JOIN providers prov ON prov.idProvider = pp.providerId
             LEFT JOIN providers prov2 ON prov2.idProvider = p.provider
             LEFT JOIN product_families pf ON pf.idFamily = p.family
             WHERE p.deleted = 0 AND p.abc_type IN ('A','B','C')"
        )->result();

        // 5. Calcular necesidad
        $config = array(
            'A' => array('months' => 3, 'safety' => 1.15),
            'B' => array('months' => 2, 'safety' => 1.0),
            'C' => array('months' => 1, 'safety' => 1.0)
        );

        $suggestions = array();

        foreach ($products as $p) {
            $unitsSold = isset($salesMap[$p->idProduct]) ? $salesMap[$p->idProduct] : 0;
            $demandMonthly = round($unitsSold / 12);

            // C con demanda < 5/mes → saltar (bajo pedido)
            if ($p->abc_type == 'C' && $demandMonthly < 5) continue;

            // Sin demanda → saltar
            if ($demandMonthly <= 0) continue;

            $cfg = $config[$p->abc_type];
            $stockTarget = ceil($demandMonthly * $cfg['months'] * $cfg['safety']);
            $currentStock = isset($stockMap[$p->idProduct]) ? $stockMap[$p->idProduct] : 0;
            $inTransit = isset($transitMap[$p->idProduct]) ? $transitMap[$p->idProduct] : 0;

            $need = $stockTarget - $currentStock - $inTransit;

            if ($need <= 0) continue;
            if ($need < $p->min_order_qty) $need = $p->min_order_qty;

            $providerId = (int) $p->providerId;

            if (!isset($suggestions[$providerId])) {
                $suggestions[$providerId] = array(
                    'providerId' => $providerId,
                    'provider_name' => $p->provider_name ?: 'Sin proveedor',
                    'items' => array(),
                    'total' => 0
                );
            }

            $subtotal = $need * (float) $p->unit_cost;

            $suggestions[$providerId]['items'][] = array(
                'productId' => $p->idProduct,
                'description' => $p->description,
                'family_name' => $p->family_name,
                'abc_type' => $p->abc_type,
                'demand_monthly' => $demandMonthly,
                'stock_target' => $stockTarget,
                'stock_actual' => $currentStock,
                'in_transit' => $inTransit,
                'need' => $need,
                'unit_cost' => (float) $p->unit_cost,
                'subtotal' => $subtotal,
                'lead_time' => $p->lead_time
            );

            $suggestions[$providerId]['total'] += $subtotal;
        }

        // Ordenar items dentro de cada proveedor por ABC (A primero) y luego por necesidad
        // Limitar a 30 items por proveedor para evitar problemas de memoria
        $abcOrder = array('A' => 1, 'B' => 2, 'C' => 3);
        foreach ($suggestions as &$group) {
            usort($group['items'], function($a, $b) use ($abcOrder) {
                $cmp = $abcOrder[$a['abc_type']] - $abcOrder[$b['abc_type']];
                if ($cmp !== 0) return $cmp;
                return $b['need'] - $a['need'];
            });
            $group['items'] = array_slice($group['items'], 0, 30);
            // Recalcular total con items limitados
            $group['total'] = 0;
            foreach ($group['items'] as $item) {
                $group['total'] += $item['subtotal'];
            }
        }
        unset($group);

        return $suggestions;
    }

}