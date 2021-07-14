<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products_model extends CI_Model {

	public function getProducts(){
		$this->db->select('products.*,
			product_families.name as family_name,
			providers.name as provider_name');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
        $this->db->from('products');
		$this->db->where("products.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProductsPag($page = 1, $limit = 20){
		$this->db->select('products.*,
			product_families.name as family_name,
			providers.name as provider_name');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
        $this->db->from('products');
		$this->db->where("products.deleted",0);
		$this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getTotal() 
    {
        $this->db->from('products');
    	$this->db->where("products.deleted",0);
        return $this->db->count_all_results();
    }

    public function getTotalSearch($valor, $page = 1, $limit = 20) 
    {
        $this->db->select('products.*,
			product_families.name as family_name,
			providers.name as provider_name');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
        $this->db->from('products');
        $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
    	$this->db->where("products.deleted",0);
		$this->db->limit($limit, (($page-1) * $limit));
        return $this->db->count_all_results();
    }

	public function getProductsByWord($valor, $page = -1, $limit = 20){
		$this->db->select('products.*,
			product_families.name as family_name,
			providers.name as provider_name');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
        $this->db->from('products');
        $this->db->or_like(array('products.idProduct' => $valor, 'products.description' => $valor));
		$this->db->where("products.deleted",0);
		 if($page != -1)
        {
			$this->db->limit($limit, (($page-1) * $limit));
		}
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

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("products",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idProduct",$id);
		return $this->db->update("products",$data);
	}
	public function remove($product_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($product_id,$data);
		//$this->db->where("idProduct",$Store_id);
		//return $this->db->delete("products");
	}

	public function getFamilies(){
		$this->db->select('product_families.*');
        $this->db->from('product_families');
		$this->db->where("product_families.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getFamily($id){
		$this->db->select('product_families.*');
        $this->db->from('product_families');
		$this->db->where("product_families.idFamily",$id);
		$this->db->where("product_families.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}
	public function getFamilyByName($name){
		$this->db->select('product_families.*');
        $this->db->from('product_families');
		$this->db->where("product_families.name",$name);
		$this->db->where("product_families.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function saveFamily($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("product_families",$data);
	}

	public function updateFamily($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idFamily",$id);
		return $this->db->update("product_families",$data);
	}
	public function removeFamily($family_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'family' => 1
				);
		$this->db->where("family",$family_id);
		$this->db->update("products",$data);

		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->updateFamily($family_id,$data);
		//$this->db->where("idProduct",$Store_id);
		//return $this->db->delete("product_families");
	}
}