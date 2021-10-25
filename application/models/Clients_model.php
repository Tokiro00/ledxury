<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients_model extends CI_Model {

	public function getClients(){
		$this->db->select('clients.*,users.name as vendor_name, users.store');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor');
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getClientsPag($page = 1, $limit = 20){
		$this->db->select('clients.*,users.name as vendor_name, users.store');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor');
		$this->db->where("clients.deleted",0);
		$this->db->limit($limit, (($page-1) * $limit));
		$this->db->order_by("clients.created_at", "desc");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function clientCount($getOthers)
	{
        $this->db->from('clients');
		if(!$getOthers)
		{
			$this->db->where("clients.vendor",$this->session->userdata('user_data')['uname']);
		}
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->num_rows();
	}

	public function getTotalSearch($term, $page = 1, $limit = 20) 
    {
        $this->db->from('clients');
        $this->db->like('clients.name', $term);
     	$this->db->or_like('clients.idNum', $term);
    	$this->db->where("clients.deleted",0);
		$this->db->limit($limit, (($page-1) * $limit));
        return $this->db->count_all_results();
    }

	public function getVendorClients($vendor){
		$this->db->select('clients.*,users.name as vendor_name, users.store');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor');
		$this->db->where("clients.vendor",$vendor);
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getClientsByWord($valor, $page = -1, $limit = 20){
		$this->db->select('clients.*,users.name as vendor_name, users.store,
			clients.name AS label', FALSE);
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor');
        $this->db->or_like(array('clients.idNum' => $valor, 'clients.name' => $valor));
		$this->db->where("clients.deleted",0);
		 if($page != -1)
        {
			$this->db->limit($limit, (($page-1) * $limit));
		}
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getClient($id){
		$this->db->select('clients.*,users.name as vendor_name, users.store, users.f_id as userFId');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor');
		$this->db->where("clients.idClient",$id);
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getHighestClientFid(){
		$this->db->select('MAX(f_id) AS next_fid');
        $this->db->from('clients');
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getNeverAttendedClients($vendor){
		//SELECT * FROM   clients WHERE  NOT EXISTS (SELECT * FROM   invoices WHERE  invoices.clientId = clients.idClient)
		$this->db->select('clients.*');
        $this->db->from('clients');
		$this->db->where(" NOT EXISTS (SELECT * FROM invoices WHERE  invoices.clientId = clients.idClient) AND vendor='".$vendor."'");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getUnattendedClients($vendor, $date){
		//SELECT * FROM   clients WHERE  NOT EXISTS (SELECT * FROM   invoices WHERE  invoices.clientId = clients.idClient)
		$query = $this->db->query("SELECT vendorId, subquery.max_date, clients.* FROM (SELECT invoices.vendorId, invoices.idInvoice, invoices.clientId, MAX(date) as max_date FROM invoices GROUP BY invoices.clientId) as subquery  INNER JOIN clients ON clients.idClient = subquery.clientId WHERE subquery.max_date <= '".$date."' AND vendorId='".$vendor."'");
        //$resultados = $this->db->get();
		return $query->result();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_by'] = $this->session->userdata('user_data')['uname'];
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("clients",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idClient",$id);
		return $this->db->update("clients",$data);
	}
	public function remove($client_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted_by' => $this->session->userdata('user_data')['uname'],
					'deleted' => 1
				);
		return $this->update($client_id,$data);
		//$this->db->where("idClient",$client_id);
		//return $this->db->delete("clients");
	}
}