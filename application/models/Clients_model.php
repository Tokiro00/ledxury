<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients_model extends CI_Model {

	public function getClients(){
		$this->db->select('clients.*,users.name as vendor_name, users.store');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor', 'left');
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getClientsPag($page = 1, $limit = 20){
		$this->db->select('clients.*,users.name as vendor_name, users.store');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor', 'left');
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
		$this->db->group_start(); // Start of the bracketed group
        $this->db->like('clients.name', $term);
     	$this->db->or_like('clients.idNum', $term);
		$this->db->group_end(); // End of the bracketed group
    	$this->db->where("clients.deleted",0);
		$this->db->limit($limit, (($page-1) * $limit));
        return $this->db->count_all_results();
    }

	public function getVendorClients($vendor){
		$this->db->select('clients.*,users.name as vendor_name, users.store');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor', 'left');
		$this->db->where("clients.vendor",$vendor);
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getClientsByWord($valor, $page = -1, $limit = 20){
		$this->db->select('clients.*,users.name as vendor_name, users.store,
			clients.name AS label', FALSE);
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor', 'left');
		$this->db->group_start(); // Start of the bracketed group
        $this->db->or_like(array('clients.idNum' => $valor, 'clients.name' => $valor));
		$this->db->group_end(); // End of the bracketed group
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
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor', 'left');
		$this->db->where("clients.idClient",$id);
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getClientByIdNum($id){
		$this->db->select('clients.*,users.name as vendor_name, users.store, users.f_id as userFId');
        $this->db->from('clients')->join('users', 'users.idUser = clients.vendor', 'left');
		$this->db->where("clients.idNum",$id);
		$this->db->where("clients.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	/**
	 * Normaliza un número celular colombiano al formato canónico
	 * de Ledxury: 10 dígitos, sin prefijo +57 ni 57, sin espacios.
	 *
	 * Ej: '+57 300 123 4567' → '3001234567'
	 *     '573001234567'     → '3001234567'
	 *     '300-123-4567'     → '3001234567'
	 */
	public static function normalizePhone($phone)
	{
		if ($phone === null) return '';
		$digits = preg_replace('/[^0-9]/', '', (string) $phone);
		if (strlen($digits) === 12 && strpos($digits, '57') === 0) {
			$digits = substr($digits, 2);
		}
		return $digits;
	}

	/**
	 * Busca un cliente por su número de celular (llave principal en Ledxury).
	 * Compara contra cellphone y phone, con normalización en ambos lados.
	 * Si hay duplicados (datos legacy) devuelve el más reciente no eliminado.
	 */
	public function getClientByPhone($phone)
	{
		$normalized = self::normalizePhone($phone);
		if ($normalized === '') return null;

		$this->db->select('clients.*,users.name as vendor_name, users.store, users.f_id as userFId');
		$this->db->from('clients');
		$this->db->join('users', 'users.idUser = clients.vendor', 'left');
		$this->db->where("clients.deleted", 0);
		$this->db->group_start();
			$this->db->where("REGEXP_REPLACE(clients.cellphone, '[^0-9]', '') =", $normalized);
			$this->db->or_where("RIGHT(REGEXP_REPLACE(clients.cellphone, '[^0-9]', ''), 10) =", $normalized);
			$this->db->or_where("REGEXP_REPLACE(clients.phone, '[^0-9]', '') =", $normalized);
			$this->db->or_where("RIGHT(REGEXP_REPLACE(clients.phone, '[^0-9]', ''), 10) =", $normalized);
		$this->db->group_end();
		$this->db->order_by("clients.idClient", "desc");
		$this->db->limit(1);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getClientTotalSpent($client, $from = "", $until = ""){
		$this->db->select('SUM(invoices.payment) as total_spent,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("invoices.clientId",$client);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
		$this->db->where("invoices.deleted",0);
		if(!empty($from))
        {
        	$this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
			$this->db->where('invoices.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
		$this->db->order_by("invoices.updated_at", "asc");
        $this->db->limit(1);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getClientsTotalSpent($from = "", $until = ""){
		$this->db->select('invoices.*, SUM(invoices.payment) as total_spent,
			users.name as vendor_name,
			stores.name as store_name,
			clients.idNum as client_idNum,
			clients.name as client_name');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->join('clients', 'clients.idClient = invoices.clientId');
		$this->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->db->from('invoices');
        $this->db->where("(invoices.state = '2' OR invoices.state = '3')");
		$this->db->where("invoices.deleted",0);
		if(!empty($from))
        {
        	$this->db->where('invoices.date >=', date('Y-m-d H:i:s',strtotime($from)));
        }
        if(!empty($until))
        {
			$this->db->where('invoices.date <=', date('Y-m-d H:i:s',strtotime($until)));
        }
		$this->db->order_by("invoices.updated_at", "asc");
		$this->db->group_by("invoices.clientId");
		$resultados = $this->db->get();
		return $resultados->result();
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
		$this->db->where(" NOT EXISTS (SELECT * FROM invoices WHERE  invoices.clientId = clients.idClient) AND vendor='".$vendor."' AND clients.blacklisted='0'");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getUnattendedClients($vendor, $date){
		//SELECT * FROM   clients WHERE  NOT EXISTS (SELECT * FROM   invoices WHERE  invoices.clientId = clients.idClient)
		$query = $this->db->query("SELECT vendorId, subquery.max_date, clients.* FROM (SELECT invoices.vendorId, invoices.idInvoice, invoices.clientId, MAX(date) as max_date FROM invoices GROUP BY invoices.clientId) as subquery  INNER JOIN clients ON clients.idClient = subquery.clientId WHERE subquery.max_date <= '".$date."' AND vendorId='".$vendor."' AND clients.blacklisted='0'");
        //$resultados = $this->db->get();
		return $query->result();
	}

	public function getAllNeverAttendedClients(){
		$this->db->select('clients.*, users.name as vendor_name');
        $this->db->join('users', 'users.idUser = clients.vendor', 'left');
        $this->db->from('clients');
		$this->db->where(" NOT EXISTS (SELECT * FROM invoices WHERE invoices.clientId = clients.idClient) AND clients.blacklisted='0'");
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getAllUnattendedClients($date){
		//SELECT * FROM   clients WHERE  NOT EXISTS (SELECT * FROM   invoices WHERE  invoices.clientId = clients.idClient)
		$query = $this->db->query("SELECT vendorId, subquery.max_date, clients.*, users.name as vendor_name FROM (SELECT invoices.vendorId, invoices.idInvoice, invoices.clientId, MAX(date) as max_date FROM invoices GROUP BY invoices.clientId) as subquery INNER JOIN clients ON clients.idClient = subquery.clientId INNER JOIN users ON users.idUser = clients.vendor WHERE subquery.max_date <= '".$date."' AND clients.blacklisted='0'");
        //$resultados = $this->db->get();
		return $query->result();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$user_data = $this->session->userdata('user_data');
		$data['created_by'] = isset($user_data['uname']) ? $user_data['uname'] : (isset($data['created_by']) ? $data['created_by'] : 'cron');
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