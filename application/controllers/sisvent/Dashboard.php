<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->backend_lib->control();
		$this->load->model("products_model");
		$this->load->model("expenses_model");
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("users_model");
        $this->load->model("cashboxes_model");
        $this->load->model("cashmovements_model");
        $this->load->model("bankaccounts_model");
	}

	public function index()
	{
		$userId = $this->session->userdata('user_data')['uname'];
		$user = $this->users_model->getAnyUser($userId);

		$page = $this->input->get('p');
		$page2 = $this->input->get('p2');
		

		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getTotal($user->store);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		if(!$page2)
			$page2 = 1;
		
		$total2 = $this->inventory_model->getTotalNoInve($user->store);
		$last2       = ceil( $total2 / $limit );

		if($page2 > $last2)
			$page2 = $last2;

		if($page2 <= 0)
			$page2 = 1;

		$data = array(
			'settlement' => getVendorSettlement($userId)->total,
			'settlementiva' => getVendorSettlement($userId)->totaliva,
			'settlementnoiva' => getVendorSettlement($userId)->totalnoiva,
			'numClients' =>  $this->clients_model->clientCount($this->session->userdata('user_data')['role'] != 3),
			'lostInvoices' =>  $this->invoices_model->getTotalVendorLegalColletionInvoices($userId),
			'salesByMonth' =>  $this->invoices_model->getVendorSalesByMonth($userId, date("Y")),
			//'numClientsquery' =>  $this->db->last_query(),
			'paidInvoices' =>  $this->invoices_model->paidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			'lowInventory' =>  $this->inventory_model->getLowInventoryProducts($user->store, $page, $limit),
			'noInventory' =>  $this->inventory_model->getNoInventoryProducts($user->store, $page2, $limit),
			//'paidInvoicesquery' =>  $this->db->last_query(),
			'nonPaidInvoices' =>  $this->invoices_model->nonPaidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
			'page' => $page,
			'page2' => $page2,
			'total' => $total,
			'total2' => $total2,
			'limit' => $limit,
		);

		// Panel Caja y Bancos (solo admin)
		$role = $this->session->userdata('user_data')['role'];
		if ($role == 1) {
			date_default_timezone_set("America/Bogota");
			$todayStart = date('Y-m-d') . ' 00:00:00';
			$todayEnd = date('Y-m-d') . ' 23:59:59';

			$openCashboxes = $this->cashboxes_model->getActiveCashboxes();
			foreach ($openCashboxes as $cb) {
				$totals = $this->cashmovements_model->getTotalsBySource('cashbox', $cb->idCashbox, $todayStart, $todayEnd);
				$cb->todayIngress = $totals->totalIngress ?: 0;
				$cb->todayEgress = $totals->totalEgress ?: 0;
			}
			$data['openCashboxes'] = $openCashboxes;

			$activeBanks = $this->bankaccounts_model->getActiveBankAccounts();
			foreach ($activeBanks as $bank) {
				$totals = $this->cashmovements_model->getTotalsBySource('bank', $bank->idBankAccount, $todayStart, $todayEnd);
				$bank->todayIngress = $totals->totalIngress ?: 0;
				$bank->todayEgress = $totals->totalEgress ?: 0;
			}
			$data['activeBanks'] = $activeBanks;
		}

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

	public function viewunattclients(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'clients' => $this->clients_model->getUnattendedClients($vendor, date( "Y-m-d H:i:s", strtotime('-3 months'))), 
			'neverclients' => $this->clients_model->getNeverAttendedClients($vendor),
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/business/clients/unattendedview",$data);
	}

	public function viewlostinvoices(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'lostInvoices' => $this->invoices_model->getVendorLegalColletionInvoices($vendor), 
			'lq' => $this->db->last_query(),
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/commercial/invoices/lostinvoices",$data);
	}

	public function getUnattendedClients()
	{
		/*$data = array(
			'low' =>  $this->inventory_model->getLowInventoryProducts($store),
			
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
		);*/
		
		//$this->load->view("layouts/footer");
		echo "<pre>";
		print_r($this->clients_model->getUnattendedClients($this->session->userdata('user_data')['uname'], date( "Y-m-d H:i:s", strtotime('-3 months'))));
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
	}

	public function getNeverAttendedClients()
	{
		/*$data = array(
			'low' =>  $this->inventory_model->getLowInventoryProducts($store),
			
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
		);*/
		
		//$this->load->view("layouts/footer");
		echo "<pre>";
		print_r($this->clients_model->getNeverAttendedClients($this->session->userdata('user_data')['uname']));
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
	}

	public function blacklisted($client_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$data  = array(
				'blacklisted' => 1,
			);

		$this->clients_model->update($client_id,$data);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/dashboard";
	}

	public function prodnofotos()
	{

		$data  = array(
			'products' => $this->products_model->getProducts(), 
		);
		$this->load->view("sisvent/prodnofotos",$data);
		
	}
}