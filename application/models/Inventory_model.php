<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_model extends CI_Model {

	public function getInventory($store){
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