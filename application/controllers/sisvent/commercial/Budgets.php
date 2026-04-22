<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Budgets extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('presupuestos');
        $this->load->model("budgets_model");
        $this->load->model("invoices_model");
        $this->load->model("noinvoices_model");
        $this->load->model("stores_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("users_model");
    }

	public function index()
	{
		// Si viene ?q= redirigir a search internamente
		$q = $this->input->get('q');
		if (!empty($q)) {
			return $this->search($q);
		}

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');
		$rls = $this->input->get('rls');
		$type = $this->input->get('type');

		$limit = 50;
		if(!$page)
			$page = 1;
		if(!($store))
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';
		if(!$rls)
			$rls = 0;
		if(!$type)
			$type = 'all';

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']);
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$total = $this->budgets_model->getTotal($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $type);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		// Title based on type
		$typeLabels = array('devolucion' => 'Devoluciones', 'garantia' => 'Garantias', 'venta' => 'Presupuestos');
		$pageTitle = isset($typeLabels[$type]) ? $typeLabels[$type] : 'Presupuestos';

		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'vendors' => $this->vendors_model->getVendors(),
			'clients' => $this->clients_model->getClients(),
			'total' => $total,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'ptype' => $type,
			'pageTitle' => $pageTitle,
			'page' => $page,
			'limit' => $limit,
				'strname' => $store != 'all' ? $this->stores_model->getStore($store)->name : '',
			'removels' => $rls == 1,
			'budgets' => $this->budgets_model->getBudgets(!in_array($this->session->userdata('user_data')['role'], [3, 4]), $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit, $type)
		);
		$this->load->view("sisvent/commercial/budgets/list",$data);
	}

	public function add(){
		$default = $this->inventory_model->getProduct('FLETE');
			$default->stock = 5;
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
			'vendors' => $this->vendors_model->getVendors(), 
			'clients' => $this->clients_model->getClients(), 
			'default' => $default, 
		);
		$this->load->view("sisvent/commercial/budgets/add",$data);
	}
	
	public function printed(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$id = $this->input->post("id");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$res = $this->budgets_model->printed($id);
		echo json_encode($res);
	}

	public function getProducts(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$valor = $this->input->post("valor");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$products = $this->inventory_model->getProducts($valor);
		foreach ($products as $key => $product) {
			$last_prod_inv = $this->invoices_model->getProductLastPrice($product->idProduct,$this->input->post("vendor"),$this->input->post("client"));
			//$product->last_query = $this->db->last_query();
			if(!empty($last_prod_inv)){
				$product->last_price = $last_prod_inv[0]->unit;
			}
		}
		echo json_encode($products);
	}

	public function getProduct(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$producto = $this->inventory_model->getStoreProduct($this->input->post("orstr"),$this->input->post("ref"));
		
		if(empty($producto)){
			$producto = $this->inventory_model->getProduct($this->input->post("ref"));
			$producto->stock = 0;
		}

		$last_prod_inv = $this->invoices_model->getProductLastPrice($ref,$this->input->post("vendor"),$this->input->post("client"));
		if(!empty($last_prod_inv)){
			$producto->last_price = $last_prod_inv[0]->unit;
		}
		$producto->isadusr = in_array($this->session->userdata('user_data')['role'], [1]);
		echo json_encode($producto);
	}

	public function getProductt($orstr,$ref,$vendor){
		
		$producto = $this->inventory_model->getStoreProduct($orstr,$ref);
		
		if(empty($producto)){
			$producto = $this->inventory_model->getProduct($ref);
			$producto->stock = 0;
		}

		$last_prod_inv = $this->invoices_model->getProductLastPrice($ref,$vendor);
		if(!empty($last_prod_inv)){
			echo "<pre>";
			echo print_r($last_prod_inv);
			echo "</pre>";
			$producto->last_price = $last_prod_inv[0]->unit;
		}else
		{
			//echo "<pre>";
			//echo "Primera Venta de ese producto";
			//echo "</pre>";
		}

		echo "<pre>";
		echo print_r($producto);
		echo "</pre>";
	}

	public function getVendor()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendors = $this->vendors_model->getVendor($this->input->post("vendor"));
		echo json_encode($vendors);
	}

	public function getVendorClients()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendors = $this->clients_model->getVendorClients($this->input->post("vendor"));
		echo json_encode($vendors);
	}

	public function getClients(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$valor = $this->input->post("valor");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$clients = $this->clients_model->getClientsByWord($valor);
		echo json_encode($clients);
	}

	public function getClientss($valor){
		//$this->outh_model->CSRFVerify();
//
		//if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
//
		//$valor = $this->input->post("valor");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$clients = $this->clients_model->getClientsByWord($valor);
		echo json_encode($clients);
	}

	public function getClient()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$client = $this->clients_model->getClient($this->input->post("client"));
		$debt = $this->invoices_model->getClientDebt($this->input->post("client"));
		$oldestInvioce = $this->invoices_model->oldestNonPaidInvioce($this->input->post("client"));
		$last_query = $this->db->last_query();
		$client->last_query = $last_query;

		$debt2020 = $this->noinvoices_model->getClientDebt($this->input->post("client"));
		$oldestInvioce2020 = $this->noinvoices_model->oldestNonPaidInvioce($this->input->post("client"));

		$client->debt = $debt->debt + $debt2020->debt;
		if($oldestInvioce)
		{
			$client->oldestInvioce = $oldestInvioce->date;
			$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
		}else if($oldestInvioce2020)
		{
			$client->oldestInvioce = $oldestInvioce2020->date;
			$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce2020->date));
		}
		else{
			$client->oldestInvioce = date( "Y-m-d H:i:s");
			$oldestInvioceDate = date( "Y-m-d H:i:s");
		}

		//$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
        $todayMin3M = date( "Y-m-d H:i:s", strtotime('-2 months'));
		$client->defaulter = $oldestInvioceDate < $todayMin3M;

		/*if($debt->debt > $client->maximum_debt)
		{
			sendEmail("cdga777@gmail.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe ".$debt->debt);
		}elseif($client->defaulter)
		{
			sendEmail("cdga777@gmail.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe una factura de ".$oldestInvioce->date);
		}*/

		echo json_encode($client);
	}

	public function debt($pclient)
	{
		$client = $this->clients_model->getClient($pclient);
		$debt = $this->invoices_model->getClientDebt($pclient);
		$oldestInvioce = $this->invoices_model->oldestNonPaidInvioce($pclient);

		$debt2020 = $this->noinvoices_model->getClientDebt($pclient);
		//echo $this->db->last_query()."<br>";
		$oldestInvioce2020 = $this->noinvoices_model->oldestNonPaidInvioce($pclient);

		if($oldestInvioce)
			$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
		else
			$oldestInvioceDate = date( "Y-m-d H:i:s");

		if($oldestInvioce2020)
			$oldestInvioceDate2020 = date( "Y-m-d H:i:s", strtotime($oldestInvioce2020->date));
		else
			$oldestInvioceDate2020 = date( "Y-m-d H:i:s");

		echo $debt->debt."<br>";
		echo $debt2020->debt."<br>";
		print_r($debt2020);
		echo "<br>";
		echo ($debt->debt + $debt2020->debt)."<br>";
		if($oldestInvioce)
			echo $oldestInvioce->date."<br>";
		echo $oldestInvioceDate."<br>";
		if($oldestInvioce2020)
			echo $oldestInvioce2020->date."<br>";
		echo $oldestInvioceDate2020."<br>";

        //$todayMin3M = strtotime('-1 months', date( "Y-m-d H:i:s"));
        $todayMin3M = date( "Y-m-d H:i:s", strtotime('-2 months'));
		echo $todayMin3M."<br>";

		$admins = $this->users_model->getUsersByRole(1);
        $storeadmins = "";
        foreach($admins as $admin){
        	$admin_store_arr = explode(',', $admin->admin_store);
        	if(in_array($client->store, $admin_store_arr) && !empty($admin->email)){
        		$storeadmins .= empty($storeadmins) ? $admin->email : ",".$admin->email ;
        	}
        }

		echo "correos::".$storeadmins."<br>";


		if($debt->debt + $debt2020->debt > $client->maximum_debt)
		{
			echo $this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe ".$debt->debt;
			//sendEmail("cdga777@gmail.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe ".$debt);
		}elseif($oldestInvioceDate < $todayMin3M)
		{
			echo $this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe una factura de ".$oldestInvioce->date;
			//sendEmail("cdga777@gmail.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe una factura de ".$oldestInvioce->date);
		}elseif($oldestInvioceDate2020 < $todayMin3M)
		{
			echo $this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe una factura de ".$oldestInvioce2020->date;
			//sendEmail("cdga777@gmail.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." está creando un presupuesto a ".$client->name.", quien debe una factura de ".$oldestInvioce->date);
		}


		//echo $this->db->last_query();
		echo "<br>";
		print_r(json_encode($debt));
		echo "<br>";
	    echo $this->email->print_debugger();
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			date_default_timezone_set("America/Bogota");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$piva = $this->input->get('i');
		$limit = 50;
		if(!$page)
			$page = 1;
		if(!($pstore))
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pclient)
			$pclient = 'all';
		if(is_null($piva))
			$piva = 'all';
		

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$ptotal = $this->budgets_model->getTotal($this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pclient, $piva, $user->admin_store_arr);
		$last       = ceil( $ptotal / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;


		$vendor = $this->input->post("vendor");
		$client = $this->input->post("client");
		$rate = $this->input->post("rate");
		$store = $this->input->post("store");
		$hasIva = $this->input->post("hasIva");
		$e_commerce = $this->input->post("e_commerce");
		$list_price = $this->input->post("list_price");
		$total = $this->input->post("total");
		$iva = 8;
		$comments = $this->input->post("comments");
        /*if(in_array($this->session->userdata('user_data')['role'], [1])):
			$iva = $this->input->post("iva");
        endif;*/

        if(!in_array($this->session->userdata('user_data')['role'], [1])){
        	$e_commerce = $user->e_commerce ? 'on' : 'off';
        }

		$products = $this->input->post("refs");
		$stock = $this->input->post("stock");
		$budget_bases = $this->input->post("price_base");
		$budget_rates = $this->input->post("budget-rates");
		$quantities = $this->input->post("budget-quantities");
		$budget_subtotal = $this->input->post("budget-subtotal");
				
		if($products && count($products) > 0)
		{
			$clientDat = $this->clients_model->getClient($client);
			$debt = $this->invoices_model->getClientDebt($client);
			$oldestInvioce = $this->invoices_model->oldestNonPaidInvioce($client);

			$debt2020 = $this->noinvoices_model->getClientDebt($client);
			$oldestInvioce2020 = $this->noinvoices_model->oldestNonPaidInvioce($client);

			if($oldestInvioce)
				$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
			else
				$oldestInvioceDate = date( "Y-m-d H:i:s");

			if($oldestInvioce2020)
				$oldestInvioceDate2020 = date( "Y-m-d H:i:s", strtotime($oldestInvioce2020->date));
			else
				$oldestInvioceDate2020 = date( "Y-m-d H:i:s");

			//$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
	        $todayMin3M = date( "Y-m-d H:i:s", strtotime('-2 months'));

	        $admins = $this->users_model->getUsersByRole(1);
	        $storeadmins = "";
	        foreach($admins as $admin){
	        	$admin_store_arr = explode(',', $admin->admin_store);
	        	if(in_array($store, $admin_store_arr) && !empty($admin->email)){
	        		$storeadmins .= empty($storeadmins) ? $admin->email : ",".$admin->email ;
	        	}
	        }

			//echo "correos::".$storeadmins."<br>";
		
			if($debt->debt + $debt2020->debt > $clientDat->maximum_debt)
			{
				sendEmail("cdga777@gmail.com,".(!empty($storeadmins) ? $storeadmins : ""),"Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$clientDat->name.", quien debe $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $debt->debt)), 2));
				////sendEmail("cdga777@gmail.com,lasolucionfinal88@gmail.com,alex.alzate@gmail.com,elkfer870@gmail.com".($store == 3 ? ",julian.andres.alz@gmail.com" : "").",romant1ezer@icloud.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$clientDat->name.", quien debe $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $debt->debt)), 2));
			}elseif($oldestInvioceDate < $todayMin3M)
			{
				////sendEmail("cdga777@gmail.com,lasolucionfinal88@gmail.com,alex.alzate@gmail.com,elkfer870@gmail.com".($store == 3 ? ",julian.andres.alz@gmail.com" : "").",romant1ezer@icloud.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$clientDat->name.", quien debe una factura de ".$oldestInvioce->date);
				sendEmail("cdga777@gmail.com,".(!empty($storeadmins) ? $storeadmins : ""),"Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$clientDat->name.", quien debe una factura de ".$oldestInvioce->date);
			}elseif($oldestInvioceDate2020 < $todayMin3M)
			{
				////sendEmail("cdga777@gmail.com,lasolucionfinal88@gmail.com,alex.alzate@gmail.com,elkfer870@gmail.com".($store == 3 ? ",julian.andres.alz@gmail.com" : "").",romant1ezer@icloud.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$clientDat->name.", quien debe una factura de ".$oldestInvioce->date);
				sendEmail("cdga777@gmail.com,".(!empty($storeadmins) ? $storeadmins : ""),"Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$clientDat->name.", quien debe una factura de ".$oldestInvioce2020->date);
			}

			$budget_type = $this->input->post("budget_type") ?: 'venta';

			$data  = array(
				'clientId' => $client,
				'vendorId' => $vendor,
				'storeId' => $store,
				'budget_type' => $budget_type,
				'total' => $total,
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'e_commerce' => $e_commerce == "on",
				'list_price' => $list_price == "on",
				'hasIva' => $hasIva ?? 0,
				'iva' => $iva,
				'comments' => $comments,
			);

			//print_r($data);

			if ($this->budgets_model->save($data)) {
				$idBudget = $this->budgets_model->lastID();
				$this->_save_detail($products,$idBudget,$quantities,$budget_rates,$budget_bases,$budget_subtotal);

				/*if($clientDat->check_can_bill && $clientDat->can_bill)
				{
					$data  = array(
						'can_bill' => 0
					);

					$this->clients_model->update($clientDat->idClient,$data);
				}*/

				redirect(base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $piva ).'&rls=1');
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
					'vendors' => $this->vendors_model->getVendors(), 
					'clients' => $this->clients_model->getClients(), 
				);
				$this->session->set_flashdata("budget_error","No se pudo guardar la información");
				$this->load->view("sisvent/commercial/budgets/add",$data);
			}
			
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'vendors' => $this->vendors_model->getVendors(), 
				'clients' => $this->clients_model->getClients(), 
			);
			$this->session->set_flashdata("budget_error","Debe ingresar al menos un producto");
			$this->load->view("sisvent/commercial/budgets/add",$data);
			//$this->add();
		}
		
	}

	function _save_detail($products,$idBudget,$quantities,$rates,$price_base,$subtotal){
		
		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		for ($i=0; $i < count($products); $i++) { 
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$products[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($products[$i], $per_packages) === FALSE)." + " .array_search($products[$i], $per_packages). "' );</script>";

			$data  = array(
				'budgetId' =>$idBudget,
				'productId' =>$products[$i],
				'quantity' =>$quantities[$i],
				'unit' => $rates[$i],
				'base' => $price_base[$i],
				'total' =>$subtotal[$i]
			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			$this->budgets_model->save_detail($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function edit($budget_id){

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');

		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';

		$budget = $this->budgets_model->getBudget($budget_id);
		$details = $this->budgets_model->getDetails($budget_id);
		foreach ($details as $key => $detail) {
			$producto = $this->inventory_model->getStoreProduct($budget->storeId, $detail->productId);
			$detail->stock = empty($producto) ? 0 : $producto->stock;
		}

		$data  = array(
			'stores' => $this->stores_model->getStores(), 
			'budget' => $budget, 
			'vendors' => $this->vendors_model->getVendors(), 
			'clients' => $this->clients_model->getClients(), 
			'details' => $details,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'page' => $page,
		);
		$this->load->view("sisvent/commercial/budgets/edit",$data);
	}

	public function update(){
		$idBudget = $this->input->post("id");
		$client = $this->input->post("client");
		$total = $this->input->post("total");
		$store = $this->input->post("store");
		$hasIva = $this->input->post("hasIva");
		$e_commerce = $this->input->post("e_commerce");
		$list_price = $this->input->post("list_price");
		$vendor = $this->input->post("vendor");
		/*if(in_array($this->session->userdata('user_data')['role'], [1])):
			$iva = $this->input->post("iva");
        endif;*/

		$products = $this->input->post("refs");
		$budget_rates = $this->input->post("budget-rates");
		$budget_bases = $this->input->post("price_base");
		$quantities = $this->input->post("budget-quantities");
		$budget_subtotal = $this->input->post("budget-subtotal");
		$comments = $this->input->post("comments");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$iva = $this->input->get('i');


		if(!$page)
			$page = 1;
		if(!$pstore)
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pclient)
			$pclient = 'all';
		if(is_null($iva))
			$iva = 'all';
		
		date_default_timezone_set("America/Bogota");
		$data  = array(
			'clientId' => $client,
			'total' => $total,
			'storeId' => $store,
			'vendorId' => $vendor,
			'e_commerce' => $e_commerce == "on",
			'list_price' => $list_price == "on",
			'hasIva' => $hasIva ?? 0,
			'comments' => $comments,
		);

		//print_r($data);

		if ($this->budgets_model->update($idBudget,$data)) {
			$this->budgets_model->removeDetails($idBudget);

			$this->_save_detail($products,$idBudget,$quantities,$budget_rates,$budget_bases,$budget_subtotal);
			//$this->_update_detail($products,$idBudget,$quantities,$budget_subtotal);
		    $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha editado presupuesto ".$idBudget);
			redirect(base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva ));
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'budget' => $this->budgets_model->getBudget($idBudget), 
				'vendors' => $this->vendors_model->getVendors(), 
				'details' => $this->budgets_model->getDetails($idBudget),
				'clients' => $this->clients_model->getClients(), 
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pclient' => $pclient,
				'piva' => $iva,
				'page' => $page,
			);
			$this->session->set_flashdata("budget_error","No se pudo guardar la información");
			$this->load->view("sisvent/commercial/budgets/edit",$data);
		}
			
	}

	function _update_detail($products,$idBudget,$quantities,$subtotal){
		
		for ($i=0; $i < count($products); $i++) { 

			$data  = array(
				'quantity' =>$quantities[$i],
				'total' =>$subtotal[$i]
			);
			
			$this->budgets_model->update_detail($idBudget,$products[$i],$data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idBudget = $this->input->post("id");
		$data  = array(
			'budget' => $this->budgets_model->getBudget($idBudget), 
			'details' => $this->budgets_model->getDetails($idBudget),
		);
		$this->load->view("sisvent/commercial/budgets/view",$data);
	}

	public function search($term){
		
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');

		$limit = 50;
		if(!$page)
			$page = 1;
		if(!($store))
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$total = $this->budgets_model->getTotalSearch($term,$store, $vendor, $state, $client, $iva, $user->admin_store_arr);
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'vendors' => $this->vendors_model->getVendors(),
			'clients' => $this->clients_model->getClients(),
			'total' => $total,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'page' => $pag,
			'limit' => $limit,
			'budgets' => $this->budgets_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit)
		);

		$this->load->view("sisvent/commercial/budgets/list",$data);	
		//$this->budgets_model->searchByWord($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $page, $limit);
		//redirect(base_url()."sisvent/business/clients");
		//echo base_url()."sisvent/commercial/budgets";
	}

	public function unblock(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idClient = $this->input->post("id");
		$client = $this->clients_model->getClient($idClient);
		echo json_encode($client);
	}

	public function delete($idBudget){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->budgets_model->remove($idBudget);
	    $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha eliminado presupuesto ".$idBudget);
	//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/commercial/budgets";
	}

	/**
	 * Asignar presupuesto a un almacenista (Jefe de Logistica)
	 */
	public function asignar($idBudget){
		$this->backend_lib->controlModule('asignar_pedidos');

		$almacenista = $this->input->post('almacenista');
		if (empty($almacenista)) {
			$this->session->set_flashdata('error_budget', 'Selecciona un almacenista.');
			redirect('sisvent/commercial/budgets?' . $this->input->server('QUERY_STRING'));
			return;
		}

		$budget = $this->budgets_model->getBudget($idBudget);
		if (!$budget || $budget->state != 1) {
			$this->session->set_flashdata('error_budget', 'Solo se pueden asignar presupuestos aprobados (facturados).');
			redirect('sisvent/commercial/budgets');
			return;
		}

		$user = $this->session->userdata('user_data')['uname'];
		$this->budgets_model->update($idBudget, array(
			'asignado_a' => $almacenista,
			'asignado_at' => date('Y-m-d H:i:s'),
			'asignado_por' => $user
		));

		$this->db->select('name')->from('users')->where('idUser', $almacenista);
		$almName = $this->db->get()->row();

		$this->session->set_flashdata('success', 'Presupuesto #' . $idBudget . ' asignado a ' . ($almName ? $almName->name : $almacenista));
		redirect('sisvent/commercial/budgets?' . $this->input->server('QUERY_STRING'));
	}

	/**
	 * Marcar presupuesto como embalado (bodegueros)
	 */
	public function embalar($idBudget){
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$this->backend_lib->controlModule('embalar_pedidos');

		$budget = $this->budgets_model->getBudget($idBudget);
		if (!$budget || $budget->state != 1) {
			$this->session->set_flashdata('error_budget', 'Solo se puede embalar después de aprobar (facturar) el pedido.');
			echo base_url() . 'sisvent/commercial/budgets';
			return;
		}

		$user = $this->session->userdata('user_data')['uname'];
		$this->budgets_model->update($idBudget, array(
			'embalado' => 1,
			'embalado_by' => $user,
			'embalado_at' => date('Y-m-d H:i:s')
		));

		$this->session->set_flashdata('success', 'Presupuesto #' . $idBudget . ' marcado como embalado.');
		echo base_url() . 'sisvent/commercial/budgets';
	}

	public function approve($idBudget){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		// Solo usuarios con permiso 'facturar' pueden aprobar
		$this->backend_lib->controlModule('facturar');

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$iva = $this->input->get('i');


		$limit = 50;
		if(!$page)
			$page = 1;
		if(!$pstore)
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pclient)
			$pclient = 'all';
		if(is_null($iva))
			$iva = 'all';

		$budget = $this->budgets_model->getBudget($idBudget);

		if (!$budget || (int)$budget->state !== 2) {
			$this->session->set_flashdata("budget_error", "El presupuesto debe estar Revisado antes de facturar.");
			echo base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva );
			return;
		}

		$client = $this->clients_model->getClient($budget->clientId);
		$debt = $this->invoices_model->getClientDebt($budget->clientId);
		$oldestInvioce = $this->invoices_model->oldestNonPaidInvioce($budget->clientId);

		$debt2020 = $this->noinvoices_model->getClientDebt($budget->clientId);
		$oldestInvioce2020 = $this->noinvoices_model->oldestNonPaidInvioce($budget->clientId);

		$clientDebt = $debt->debt + $debt2020->debt;


		if($oldestInvioce)
		{
			$clientOldestInvioce = $oldestInvioce->date;
			$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
		}else if($oldestInvioce2020)
		{
			$clientOldestInvioce = $oldestInvioce2020->date;
			$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce2020->date));
		}
		else{
			$clientOldestInvioce = date( "Y-m-d H:i:s");
			$oldestInvioceDate = date( "Y-m-d H:i:s");
		}

		//$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
        $todayMin3M = date( "Y-m-d H:i:s", strtotime('-2 months'));
		$isClientDefaulter = $oldestInvioceDate < $todayMin3M;

		if($clientDebt > $client->maximum_debt && !$client->can_bill)
        {
        	$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
			if(!empty($user->admin_store))
				$user->admin_store_arr = explode(',', $user->admin_store);
			else
				$user->admin_store_arr = array();

            //showModal("Este cliente está moroso, debe $"+data.debt);
            $total = $this->budgets_model->getTotal($this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pclient, $iva, $user->admin_store_arr);
			$last = ceil( $total / $limit );

			if($page > $last)
				$page = $last;

			if($page <= 0)
				$page = 1;

        	/*$data  = array(
				'stores' => $this->stores_model->getStores(),
				'vendors' => $this->vendors_model->getVendors(),
				'clients' => $this->clients_model->getClients(),
				'total' => $total,
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pclient' => $pclient,
				'piva' => $iva,
				'page' => $page,
				'limit' => $limit,
				'budgets' => $this->budgets_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pclient, $iva, $user->admin_store_arr, $page, $limit)
			);*/

			$this->session->set_flashdata("budget_error","Este cliente está moroso, debe $".$clientDebt);
			//$this->load->view("sisvent/commercial/budgets/list",$data);
			echo base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva );
        }else if($isClientDefaulter && !$client->can_bill)
        {
          //showModal("Este cliente no ha pagado facturas vencidas, debe una de "+data.oldestInvioce);
          $user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
			if(!empty($user->admin_store))
				$user->admin_store_arr = explode(',', $user->admin_store);
			else
				$user->admin_store_arr = array();
		
            $total = $this->budgets_model->getTotal($this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pclient, $iva, $user->admin_store_arr);
			$last = ceil( $total / $limit );

			if($page > $last)
				$page = $last;

			if($page <= 0)
				$page = 1;

        	/*$data  = array(
				'stores' => $this->stores_model->getStores(),
				'vendors' => $this->vendors_model->getVendors(),
				'clients' => $this->clients_model->getClients(),
				'total' => $total,
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pclient' => $pclient,
				'piva' => $iva,
				'page' => $page,
				'limit' => $limit,
				'budgets' => $this->budgets_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pclient, $iva, $user->admin_store_arr, $page, $limit)
			);*/

			$this->session->set_flashdata("budget_error","Este cliente no ha pagado facturas vencidas, debe una de ".$clientOldestInvioce);
			//$this->load->view("sisvent/commercial/budgets/list",$data);
			echo base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva );
        }else{
		
			$data  = array(
				'state' => 1,
			);

			$this->budgets_model->update($idBudget,$data);

			$details = $this->budgets_model->getDetails($idBudget);

			date_default_timezone_set("America/Bogota");
			$data  = array(
				'budgetId' => $budget->idBudget,
				'clientId' => $budget->clientId,
				'vendorId' => $budget->vendorId,
				'storeId' => $budget->storeId,
				'total' => $budget->total,
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'e_commerce' => $budget->e_commerce,
				'list_price' => $budget->list_price,
				'hasIva' => $budget->hasIva,
				'iva' => $budget->iva,
				'payment' => 0,
				'comments' => $budget->comments,
			);

			//print_r($data);

			if ($this->invoices_model->save($data)) {
				$idInvoice = $this->invoices_model->lastID();

				if($client->check_can_bill && $client->can_bill)
				{
					$data  = array(
						'can_bill' => 0
					);

					$this->clients_model->update($client->idClient,$data);
				}
				
				foreach($details as $detail) {
					
					$this->updateProduct($budget->storeId,$detail->productId,$detail->quantity);

					$data  = array(
						'invoiceId' =>$idInvoice,
						'productId' =>$detail->productId,
						'quantity' =>$detail->quantity,
						'unit' => $detail->unit,
						'base' => $detail->base,
						'total' =>$detail->subtotal
					);

					$this->invoices_model->save_detail($data);
				}

	        	$this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha aprobado presupuesto ".$idBudget." a factura ".$idInvoice);
	        	$this->session->set_flashdata('success_invoice', 'Factura #'.$idInvoice.' creada desde presupuesto #'.$idBudget.'. Abre la factura para asignar transportadora.');
				echo base_url()."sisvent/commercial/invoices";
			}else
			{
				echo base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva );
			}
		}

		//$this->budgets_model->remove($idBudget);
		//redirect(base_url()."sisvent/business/clients");
		//$data  = array(
		//	'stores' => $this->stores_model->getStores(), 
		//	'vendors' => $this->vendors_model->getVendors(), 
		//);
		//$this->load->view("sisvent/commercial/invoices/add",$data);
	}

	public function updateProducts($store,$idproducto,$cantidad){
		//$inve = $this->inventory_model->getStoreProduct($products[$i]);
		$productoActual = $this->inventory_model->getStoreProduct($store,$idproducto);
		
		print_r("OOEE");
		print_r("<pre>");
		print_r($productoActual);
		print_r("</pre>");

		if(empty($productoActual)){
			print_r("NONAS");
			$data  = array(
				'idStore' => $store, 
				'idProduct' => $idproducto,
				'stock' => -$cantidad
			);
			//$this->inventory_model->save($data);
		}else
		{
			print_r("SISAS");
			$data = array(
				'stock' => $productoActual->stock - $cantidad
			);
			//$this->inventory_model->update($store,$idproducto,$data);
		}

		print_r("====");
		print_r("<pre>");
		print_r($data);
		print_r("</pre>");
		
	}

	protected function updateProduct($store,$idproducto,$cantidad){
		$productoActual = $this->inventory_model->getStoreProduct($store,$idproducto);
		//$data = array(
		//	'stock' => $productoActual->stock - $cantidad
		//);
		//$this->inventory_model->update($store,$idproducto,$data);
		if(empty($productoActual)){
			$data  = array(
				'idStore' => $store, 
				'idProduct' => $idproducto,
				'stock' => -$cantidad
			);
			$this->inventory_model->save($data);
		}else
		{
			$data = array(
				'stock' => $productoActual->stock - $cantidad
			);
			$this->inventory_model->update($store,$idproducto,$data);
		}
	}

	public function duplicate($budget_id){
		$this->backend_lib->control([1, 10]);

		$limit = 50;

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');


		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';

		$budget = $this->budgets_model->getBudget($budget_id);
		$details = $this->budgets_model->getDetails($budget_id);
		
		date_default_timezone_set("America/Bogota");
		$data  = array(
			'clientId' => $budget->clientId,
			'vendorId' => $budget->vendorId,
			'storeId' => $budget->storeId,
			'total' => $budget->total,
			'date' => date('Y-m-d H:i:s'),
			'state' => 0,
			'e_commerce' => $budget->e_commerce ?? 0,
			'list_price' => $budget->list_price ?? 0,
			'hasIva' => $budget->hasIva ?? 0,
			'iva' => $budget->iva,
			'comments' => "",
		);

		//print_r($data);

		//if ($this->budgets_model->save($data)) {
			//$idBudget = $this->budgets_model->lastID();
			/*foreach ($details as $key => $detail) {
				
				$data  = array(
					'budgetId' =>$idBudget,
					'productId' =>$detail->productId,
					'quantity' =>$detail->quantity,
					'unit' => $detail->unit,
					'base' => $detail->base,
					'total' =>$detail->total
				);
					
				$this->budgets_model->save_detail($data);					
			}*/
			//redirect(base_url()."sisvent/commercial/budgets");

			//$budgetNew = $this->budgets_model->getBudget($budget_id);
			//$detailsNew = $this->budgets_model->getDetails($budget_id);
			foreach ($details as $key => $detail) {
				$producto = $this->inventory_model->getStoreProduct($budget->storeId, $detail->productId);
				$detail->stock = empty($producto) ? 0 : $producto->stock;
			}

			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'budget' => $budget, 
				'vendors' => $this->vendors_model->getVendors(), 
				'clients' => $this->clients_model->getClients(), 
				'details' => $details,
				'params' => createFullParamsLinks($page, $store, $vendor, $state, $client, $iva),
			);
			$this->load->view("sisvent/commercial/budgets/duplicate",$data);
		/*}else
		{
			//echo base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $store, $vendor, $state, $client );
			$total = $this->budgets_model->getTotal($store, $vendor, $state, $client);
			$last       = ceil( $total / $limit );

			if($page > $last)
				$page = $last;

			if($page <= 0)
				$page = 1;

			$data  = array(
				'stores' => $this->stores_model->getStores(),
				'vendors' => $this->vendors_model->getVendors(),
				'clients' => $this->clients_model->getClients(),
				'total' => $total,
				'pstore' => $store,
				'pvendor' => $vendor,
				'pstate' => $state,
				'pclient' => $client,
				'page' => $page,
				'limit' => $limit,
				'budgets' => $this->budgets_model->getBudgets($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $page, $limit)
			);
			$this->load->view("sisvent/commercial/budgets/list",$data);
		}*/
		
	}

	public function revisar($budget_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$iva = $this->input->get('i');

		if(!$page) $page = 1;
		if(!$pstore) $pstore = 'all';
		if(!$pvendor) $pvendor = 'all';
		if(is_null($pstate)) $pstate = 'all';
		if(!$pclient) $pclient = 'all';
		if(is_null($iva)) $iva = 'all';

		$budget = $this->budgets_model->getBudget($budget_id);
		if ($budget && (int)$budget->state === 0) {
			$this->budgets_model->update($budget_id, array('state' => 2));
		}

		echo base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva );
	}

	public function agotado($budget_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			header('Content-Type: application/json');
			echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
			return;
		}

		$budget = $this->budgets_model->getBudget($budget_id);
		if (!$budget) {
			header('Content-Type: application/json');
			echo json_encode(['ok' => false, 'msg' => 'Presupuesto no encontrado']);
			return;
		}

		$client = $this->clients_model->getClient($budget->clientId);

		$raw_phone = '';
		if ($client) {
			if (!empty($client->cellphone)) {
				$raw_phone = $client->cellphone;
			} elseif (!empty($client->phone)) {
				$raw_phone = $client->phone;
			}
		}
		$digits = preg_replace('/[^0-9]/', '', $raw_phone);
		if (strlen($digits) == 10) {
			$digits = '57' . $digits;
		}

		$client_name = ($client && !empty($client->name)) ? $client->name : 'cliente';
		$first_name = trim(explode(' ', $client_name)[0]);
		$message = "Hola {$first_name}, lamentamos informarte que uno o más productos de tu pedido #{$budget->idBudget} están actualmente agotados. Un asesor se pondrá en contacto contigo para ofrecerte alternativas o coordinar la devolución. Gracias por tu comprensión.";
		$wa_url = !empty($digits)
			? 'https://wa.me/' . $digits . '?text=' . rawurlencode($message)
			: 'https://wa.me/?text=' . rawurlencode($message);

		date_default_timezone_set('America/Bogota');
		$uname = $this->session->userdata('user_data')['uname'] ?? 'sistema';
		$stamp = date('Y-m-d H:i');
		$log_line = "[AGOTADO - notificado por {$uname} el {$stamp}]";
		$new_comments = trim(($budget->comments ? $budget->comments . "\n" : '') . $log_line);

		$this->budgets_model->update($budget_id, array(
			'archived' => 1,
			'comments' => $new_comments,
		));

		header('Content-Type: application/json');
		echo json_encode(['ok' => true, 'wa_url' => $wa_url]);
	}

	public function archive($budget_id){

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$iva = $this->input->get('i');


		if(!$page)
			$page = 1;
		if(!$pstore)
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pclient)
			$pclient = 'all';
		if(is_null($iva))
			$iva = 'all';

		$data  = array(
			'archived' => 1,
		);

		$this->budgets_model->update($budget_id,$data);
		
		redirect(base_url()."sisvent/commercial/budgets".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva ));
		
	}

	public function unarchive($budget_id){

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$iva = $this->input->get('i');


		if(!$page)
			$page = 1;
		if(!$pstore)
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pclient)
			$pclient = 'all';
		if(is_null($iva))
			$iva = 'all';

		$data  = array(
			'archived' => 0
		);

		$this->budgets_model->update($budget_id,$data);
		
		redirect(base_url()."sisvent/commercial/budgets/archived".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva ));
		
	}

	public function archived()
	{
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');
		$rls = $this->input->get('rls');

		$limit = 50;
		if(!$page)
			$page = 1;
		if(!($store))
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';
		if(!$rls)
			$rls = 0;

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$total = $this->budgets_model->getTotalArchived($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'vendors' => $this->vendors_model->getVendors(),
			'clients' => $this->clients_model->getClients(),
			'total' => $total,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'page' => $page,
			'limit' => $limit,
				'strname' => $store != 'all' ? $this->stores_model->getStore($store)->name : '',
			'removels' => $rls == 1,
			'budgets' => $this->budgets_model->getArchivedBudgets($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit)
		);
		$this->load->view("sisvent/commercial/budgets/archived",$data);	
	}

	public function createExcel() {
		$fileName = 'budgets.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$budgets = $this->budgets_model->getBudgets(true,  'all',  'all',  'all',  'all', -1, "");
		$spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
       	$sheet->setCellValue('A1', 'Cliente');
        $sheet->setCellValue('B1', 'Vendedor');
        $sheet->setCellValue('C1', 'Almacén');
        $sheet->setCellValue('D1', 'Valor');
		$sheet->setCellValue('E1', 'Estado');
        $sheet->setCellValue('F1', 'IVA');       
        $sheet->setCellValue('G1', 'Fecha');       
        $sheet->setCellValue('H1', 'Observ');       
        $rows = 2;
        foreach ($budgets as $val){
            $sheet->setCellValue('A' . $rows, $val->client_name);
            $sheet->setCellValue('B' . $rows, $val->vendor_name);
            $sheet->setCellValue('C' . $rows, $val->store_name);
            $sheet->setCellValue('D' . $rows, $val->total);
	    	$sheet->setCellValue('E' . $rows, $val->state);
            $sheet->setCellValue('F' . $rows, $val->hasIva);
            $sheet->setCellValue('G' . $rows, $val->date);
            $sheet->setCellValue('H' . $rows, $val->comments);
            $rows++;
        } 
        $writer = new Xlsx($spreadsheet);
		$writer->save("public/".$fileName);
		header("Content-Type: application/vnd.ms-excel");
        redirect(base_url()."/public/".$fileName);              
    }    

}