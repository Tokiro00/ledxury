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
	
public function getVentasStock($store, $page = -1, $limit = 20)
{
    // 🔄 Subconsulta: ventas por producto (filtra si se seleccionó tienda específica)
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

    // 🔄 Subconsulta: stock actual total + por tienda
    $stock_query = $this->db
        ->select("
            idProduct,
            SUM(stock) AS stock_total,
            MAX(CASE WHEN idStore = 1 THEN stock ELSE 0 END) AS stock_medellin,
            MAX(CASE WHEN idStore = 8 THEN stock ELSE 0 END) AS stock_medellin_bodega,
            MAX(CASE WHEN idStore = 3 THEN stock ELSE 0 END) AS stock_bogota,
            MAX(CASE WHEN idStore = 7 THEN stock ELSE 0 END) AS stock_barranquilla
        ")
        ->from('inventory')
        ->group_by('idProduct')
        ->get_compiled_select();

    // 🔗 Unión de productos + ventas + stock
    $this->db->select("
        p.idProduct,
        p.description AS nombre_producto,
        p.picture_url,
        COALESCE(s.stock_total, 0) AS stock,
        COALESCE(v.ventas, 0) AS ventas,
        FLOOR(COALESCE(v.ventas, 0) / 12) AS promedio_mensual,
        FLOOR(COALESCE(v.ventas, 0) / 12) AS inventario_optimo,
        GREATEST(FLOOR(COALESCE(v.ventas, 0) / 12) - COALESCE(s.stock_total, 0), 0) AS orden_sugerida,

        COALESCE(s.stock_medellin, 0) AS stock_medellin,
        COALESCE(s.stock_medellin_bodega, 0) AS stock_medellin_bodega,
        COALESCE(s.stock_bogota, 0) AS stock_bogota,
        COALESCE(s.stock_barranquilla, 0) AS stock_barranquilla
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


}