<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Catalogues_model extends CI_Model {

	public function getCatalogues($page = 1, $limit = 20){
		$this->db->select('catalogues.*,
            catalogues.name as cat_name,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = catalogues.vendorId');
        $this->db->join('clients', 'clients.idClient = catalogues.clientId', 'left');
		$this->db->join('stores', 'catalogues.storeId = stores.idStore');
        $this->db->from('catalogues');
		$this->db->where("catalogues.deleted",0);
		$this->db->order_by("catalogues.date", "asc");
        if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

    public function getTotal() 
    {
        $this->db->from('catalogues');
    	$this->db->where("catalogues.deleted",0);
        return $this->db->count_all_results();
    }

	public function getCatalogue($id){
		$this->db->select('catalogues.*,
            catalogues.name as cat_name,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
            clients.name as client_name,
			clients.state as client_state,
			clients.*');
        $this->db->join('users', 'users.idUser = catalogues.vendorId');
        $this->db->join('clients', 'clients.idClient = catalogues.clientId', 'left');
		$this->db->join('stores', 'catalogues.storeId = stores.idStore');
        $this->db->from('catalogues');
		$this->db->where("catalogues.idCatalogue",$id);
		$this->db->where("catalogues.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $this->session->userdata('user_data')['uname'];
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("catalogues",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idCatalogue",$id);
		return $this->db->update("catalogues",$data);
	}
	public function remove($id){
		date_default_timezone_set("America/Bogota");

		$data  = array(
                    'deleted_at' => date('Y-m-d H:i:s'),
					'deleted_by' => $this->session->userdata('user_data')['uname'],
					'deleted' => 1
				);
		return $this->update($id,$data);
	}

	public function lastID(){
		return $this->db->insert_id();
	}

	public function save_detail($data){
		return $this->db->insert("catalogue_details",$data);
	}

	public function update_detail($idBudget,$idProduct,$data){
		$this->db->where("catalogueId",$idBudget);
		$this->db->where("productId",$idProduct);
		return $this->db->update("catalogue_details",$data);
	}

	public function getDetails($catalogueId, $page = 1, $limit = 20){
		$this->db->select('catalogue_details.*, products.*, product_families.name as family_name');
        $this->db->join('products', 'products.idProduct = catalogue_details.productId');
        $this->db->join('product_families', 'product_families.idFamily = products.family');
        $this->db->from('catalogue_details');
		$this->db->where("catalogue_details.catalogueId",$catalogueId);
		$this->db->order_by("catalogue_details.display_order", "ASC");
        $this->db->order_by("products.family", "ASC");
        if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
        $resultados = $this->db->get();
		return $resultados->result();
	}

    public function getDetailsCount($catalogueId){
        $this->db->from('catalogue_details');
        $this->db->where("catalogue_details.catalogueId",$catalogueId);
        return $this->db->count_all_results();
    }

	public function removeDetails($catalogueId){
		$this->db->where("catalogue_details.catalogueId",$catalogueId);
        $this->db->delete('catalogue_details');
	}    
    /*****************************/
}