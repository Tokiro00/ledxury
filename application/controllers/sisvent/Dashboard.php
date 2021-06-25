<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->backend_lib->control();
		$this->load->model("expenses_model");
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("users_model");
	}

	public function index()
	{
		$userId = $this->session->userdata('user_data')['uname'];
		$user = $this->users_model->getAnyUser($userId);

		$page = $this->input->get('p');
		

		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getTotal($user->store);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		

		$data = array(
			'settlement' => getVendorSettlement($userId)->total,
			'settlementiva' => getVendorSettlement($userId)->totaliva,
			'settlementnoiva' => getVendorSettlement($userId)->totalnoiva,
			'numClients' =>  $this->clients_model->clientCount($this->session->userdata('user_data')['role'] != 3),
			//'numClientsquery' =>  $this->db->last_query(),
			'paidInvoices' =>  $this->invoices_model->paidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			'lowInventory' =>  $this->inventory_model->getLowInventoryProducts($user->store, $page, $limit),
			//'paidInvoicesquery' =>  $this->db->last_query(),
			'nonPaidInvoices' =>  $this->invoices_model->nonPaidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
			'page' => $page,
			'total' => $total,
			'limit' => $limit,
		);
		
		$this->load->view("sisvent/dashboard", $data);
		//$this->load->view("layouts/footer");

	}

	
	public function getLowInventoryProducts($store)
	{
		/*$data = array(
			'low' =>  $this->inventory_model->getLowInventoryProducts($store),
			
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
		);*/
		
		//$this->load->view("layouts/footer");
		echo "<pre>";
		print_r($this->inventory_model->getLowInventoryProducts($store));
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
	}

}