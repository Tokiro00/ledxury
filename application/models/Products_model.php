<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products_model extends CI_Model {

	public function getProducts($from = "", $until = ""){
		$this->db->select('products.*,
			product_families.name as family_name,
			product_datasheets.name as datasheet_name,
			providers.name as provider_name');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
		$this->db->join('product_datasheets', 'product_datasheets.idDatasheet = products.datasheet', 'left');
        $this->db->from('products');
		$this->db->where("products.deleted",0);
		if(!empty($from))
        {
        	$this->db->where('products.created_at >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until) && $from != $until)
        {
			$this->db->where('products.created_at <=', date('Y-m-d H:i:s',strtotime($until)));
        }
		$this->db->order_by("products.created_at", "DESC");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getProductsPag($page = 1, $limit = 20){
		$this->db->select('products.*,
			product_families.name as family_name,
			product_datasheets.name as datasheet_name,
			providers.name as provider_name');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
		$this->db->join('product_datasheets', 'product_datasheets.idDatasheet = products.datasheet', 'left');
        $this->db->from('products');
		$this->db->where("products.deleted",0);
		$this->db->order_by("products.created_at", "DESC");
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
			product_datasheets.name as datasheet_name,
			providers.name as provider_name');
		$this->db->join('product_families', 'product_families.idFamily = products.family');
		$this->db->join('providers', 'providers.idProvider = products.provider');
   		$this->db->join('product_datasheets', 'product_datasheets.idDatasheet = products.datasheet', 'left');
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

	public function getDatasheets(){
		$this->db->select('product_datasheets.*');
        $this->db->from('product_datasheets');
		$this->db->where("product_datasheets.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getDatasheet($id){
		$this->db->select('product_datasheets.*');
        $this->db->from('product_datasheets');
		$this->db->where("product_datasheets.idDatasheet",$id);
		$this->db->where("product_datasheets.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}
	public function getDatasheetByName($name){
		$this->db->select('product_datasheets.*');
        $this->db->from('product_datasheets');
		$this->db->where("product_datasheets.name",$name);
		$this->db->where("product_datasheets.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function saveDatasheet($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("product_datasheets",$data);
	}

	public function updateDatasheet($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idDatasheet",$id);
		return $this->db->update("product_datasheets",$data);
	}
	public function removeDatasheet($datasheet_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'datasheet' => null
				);
		$this->db->where("datasheet",$datasheet_id);
		$this->db->update("products",$data);

		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted_by' => $this->session->userdata('user_data')['uname'],
					'deleted' => 1
				);
		return $this->updateDatasheet($datasheet_id,$data);
		//$this->db->where("idProduct",$Store_id);
		//return $this->db->delete("product_datasheets");
	}

	public function saveDatasheetsLabels($data){
		return $this->db->insert("datasheets_labels",$data);
	}
	public function getDatasheetsLabels($idDatasheet){
		$this->db->select('datasheets_labels.*');
		$this->db->where("datasheets_labels.idDatasheet",$idDatasheet);
        $this->db->from('datasheets_labels');
		$resultados = $this->db->get();
		return $resultados->result();
	}
	public function removeDatasheetsLabels($idDatasheet){
		$this->db->where("datasheets_labels.idDatasheet",$idDatasheet);
        $this->db->delete('datasheets_labels');
	}

	public function saveProductsLabelsValues($data){
		return $this->db->insert("products_labels_values",$data);
	}

	public function getProductsLabelsValues($idProduct,$idDatasheet){
		$this->db->select('products_labels_values.*');
		$this->db->where("products_labels_values.idProduct",$idProduct);
		$this->db->where("products_labels_values.idDatasheet",$idDatasheet);
        //$this->db->join('datasheets_labels', 'datasheets_labels.idDatasheet = products_labels_values.idDatasheet');
        $this->db->from('products_labels_values');
        $resultados = $this->db->get();
		return $resultados->result();
	}

	public function removeProductsLabelsValues($idProduct,$idDatasheet){
		$this->db->where("products_labels_values.idProduct",$idProduct);
		$this->db->where("products_labels_values.idDatasheet",$idDatasheet);
        $this->db->delete('products_labels_values');
	}

	public function lastID(){
		return $this->db->insert_id();
	}
}