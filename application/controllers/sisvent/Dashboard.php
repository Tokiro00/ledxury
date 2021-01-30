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
	}

	public function index()
	{
		$userId = $this->session->userdata('user_data')['uname'];
		$data = array(
			'settlement' => getVendorSettlement($userId)->total,
			'settlementiva' => getVendorSettlement($userId)->totaliva,
			'settlementnoiva' => getVendorSettlement($userId)->totalnoiva,
			'numClients' =>  $this->clients_model->clientCount($this->session->userdata('user_data')['role'] != 3),
			//'numClientsquery' =>  $this->db->last_query(),
			'paidInvoices' =>  $this->invoices_model->paidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			//'paidInvoicesquery' =>  $this->db->last_query(),
			'nonPaidInvoices' =>  $this->invoices_model->nonPaidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
		);
		
		$this->load->view("sisvent/dashboard", $data);
		//$this->load->view("layouts/footer");

	}

}