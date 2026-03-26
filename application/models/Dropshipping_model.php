<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dropshipping_model extends CI_Model {

	public function getPromopacks($page = 1, $limit = 20){
		$this->db->select('promopacks.*');
        $this->db->from('promopacks');
		$this->db->where("promopacks.deleted",0);
		$this->db->order_by("promopacks.date", "asc");
        if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
		$resultados = $this->db->get();
		return $resultados->result();
	}

    public function getTotal() 
    {
        $this->db->from('promopacks');
    	$this->db->where("promopacks.deleted",0);
        return $this->db->count_all_results();
    }

	public function getPromopack($id){
		$this->db->select('promopacks.*, promopacks.quantity as promoquantity');
        $this->db->from('promopacks');
		$this->db->where("promopacks.idPromopack",$id);
		$this->db->where("promopacks.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getDelivery($id){
		$this->db->select('delivery_type.*');
        $this->db->from('delivery_type');
		$this->db->where("delivery_type.idDeliveryType",$id);
		$this->db->where("delivery_type.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $this->session->userdata('user_data')['uname'];
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("promopacks",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idPromopack",$id);
		return $this->db->update("promopacks",$data);
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
		return $this->db->insert("promopacks_details",$data);
	}

	public function update_detail($idBudget,$idProduct,$data){
		$this->db->where("promopackId",$idBudget);
		$this->db->where("productId",$idProduct);
		return $this->db->update("promopacks_details",$data);
	}

	public function getDetails($promopackId, $page = 1, $limit = 20){
		$this->db->select('promopacks_details.*, products.*, product_families.name as family_name');
        $this->db->join('products', 'products.idProduct = promopacks_details.productId');
        $this->db->join('product_families', 'product_families.idFamily = products.family');
        $this->db->from('promopacks_details');
		$this->db->where("promopacks_details.promopackId",$promopackId);
		$this->db->order_by("promopacks_details.display_order", "ASC");
        $this->db->order_by("products.family", "ASC");
        if($page != -1)
            $this->db->limit($limit, (($page-1) * $limit));
        $resultados = $this->db->get();
		return $resultados->result();
	}

    public function getDetailsCount($promopackId){
        $this->db->from('promopacks_details');
        $this->db->where("promopacks_details.promopackId",$promopackId);
        return $this->db->count_all_results();
    }

	public function removeDetails($promopackId){
		$this->db->where("promopacks_details.promopackId",$promopackId);
        $this->db->delete('promopacks_details');
	}    
    /*****************************/

    public function getPromopurchases(){
		$this->db->select('promopurchase.*');
        $this->db->from('promopurchase');
		$this->db->where("promopurchase.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getPromopurchase($id){
		$this->db->select('promopurchase.*');
        $this->db->from('promopurchase');
		$this->db->where("promopurchase.idPurchase",$id);
		$this->db->where("promopurchase.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function savePromopurchase($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("promopurchase",$data);
	}

	public function updatePromopurchase($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idPurchase",$id);
		return $this->db->update("promopurchase",$data);
	}
	public function removePromopurchase($id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($id,$data);
		//$this->db->where("idPurchase",$id);
		//return $this->db->delete("promopurchase");
	}
}