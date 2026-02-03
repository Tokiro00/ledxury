<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Purchases extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control();
        $this->load->model("purchases_model");
        $this->load->model("invoices_model");
        $this->load->model("noinvoices_model");
        $this->load->model("stores_model");
        $this->load->model("providers_model");
        $this->load->model("users_model");
        $this->load->model("inventory_model");
        $this->load->library("accounting_lib");
    }

	public function index()
	{
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$buyer = $this->input->get('c');
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
		if(!$buyer)
			$buyer = 'all';
		if(is_null($iva))
			$iva = 'all';
		if(!$rls)
			$rls = 0;

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$total = $this->purchases_model->getTotal($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $buyer, $iva, $user->admin_store_arr);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'providers' => $this->providers_model->getProviders(),
			'buyers' => $this->users_model->getUsers(),
			'total' => $total,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pbuyer' => $buyer,
			'piva' => $iva,
			'page' => $page,
			'limit' => $limit,
				'strname' => $store != 'all' ? $this->stores_model->getStore($store)->name : '',
			'removels' => $rls == 1,
			'purchases' => $this->purchases_model->getPurchases($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $buyer, $iva, $user->admin_store_arr, $page, $limit)
		);
		$this->load->view("sisvent/commercial/purchases/list",$data);	
	}

	public function add(){
		$default = $this->inventory_model->getProduct('FLETE');
			$default->stock = 5;
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
			'providers' => $this->providers_model->getProviders(), 
			'buyers' => $this->users_model->getUsers(), 
			'default' => $default, 
		);
		$this->load->view("sisvent/commercial/purchases/add",$data);
	}
	
	public function printed(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$id = $this->input->post("id");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$res = $this->purchases_model->printed($id);
		echo json_encode($res);
	}

	public function getProducts(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$valor = $this->input->post("valor");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$products = $this->inventory_model->getProducts($valor);
		foreach ($products as $key => $product) {
			$last_prod_inv = $this->invoices_model->getProductLastPrice($product->idProduct,$this->input->post("vendor"),$this->input->post("buyer"));
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

		$last_prod_inv = $this->invoices_model->getProductLastPrice($ref,$this->input->post("vendor"),$this->input->post("buyer"));
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

		$providers = $this->providers_model->getVendor($this->input->post("vendor"));
		echo json_encode($providers);
	}

	public function getVendorClients()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$providers = $this->users_model->getVendorClients($this->input->post("vendor"));
		echo json_encode($providers);
	}



	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			date_default_timezone_set("America/Bogota");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pbuyer = $this->input->get('c');
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
		if(!$pbuyer)
			$pbuyer = 'all';
		if(is_null($piva))
			$piva = 'all';
		

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$ptotal = $this->purchases_model->getTotal($this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pbuyer, $piva, $user->admin_store_arr);
		$last       = ceil( $ptotal / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;


		$vendor = $this->input->post("vendor");
		$buyer = $this->input->post("buyer");
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
		$purchase_bases = $this->input->post("price_base");
		$purchase_rates = $this->input->post("purchase-rates");
		$quantities = $this->input->post("purchase-quantities");
		$purchase_subtotal = $this->input->post("purchase-subtotal");
				
		if($products && count($products) > 0)
		{
			$buyerDat = $this->users_model->getClient($buyer);
			$debt = $this->invoices_model->getClientDebt($buyer);
			$oldestInvioce = $this->invoices_model->oldestNonPaidInvioce($buyer);

			$debt2020 = $this->noinvoices_model->getClientDebt($buyer);
			$oldestInvioce2020 = $this->noinvoices_model->oldestNonPaidInvioce($buyer);

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
		
			if($debt->debt + $debt2020->debt > $buyerDat->maximum_debt)
			{
				sendEmail("cdga777@gmail.com,".(!empty($storeadmins) ? $storeadmins : ""),"Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$buyerDat->name.", quien debe $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $debt->debt)), 2));
				////sendEmail("cdga777@gmail.com,lasolucionfinal88@gmail.com,alex.alzate@gmail.com,elkfer870@gmail.com".($store == 3 ? ",julian.andres.alz@gmail.com" : "").",romant1ezer@icloud.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$buyerDat->name.", quien debe $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $debt->debt)), 2));
			}elseif($oldestInvioceDate < $todayMin3M)
			{
				////sendEmail("cdga777@gmail.com,lasolucionfinal88@gmail.com,alex.alzate@gmail.com,elkfer870@gmail.com".($store == 3 ? ",julian.andres.alz@gmail.com" : "").",romant1ezer@icloud.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$buyerDat->name.", quien debe una factura de ".$oldestInvioce->date);
				sendEmail("cdga777@gmail.com,".(!empty($storeadmins) ? $storeadmins : ""),"Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$buyerDat->name.", quien debe una factura de ".$oldestInvioce->date);
			}elseif($oldestInvioceDate2020 < $todayMin3M)
			{
				////sendEmail("cdga777@gmail.com,lasolucionfinal88@gmail.com,alex.alzate@gmail.com,elkfer870@gmail.com".($store == 3 ? ",julian.andres.alz@gmail.com" : "").",romant1ezer@icloud.com","Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$buyerDat->name.", quien debe una factura de ".$oldestInvioce->date);
				sendEmail("cdga777@gmail.com,".(!empty($storeadmins) ? $storeadmins : ""),"Alerta de Presupuesto a Moroso ".date('Y-m-d H:i:s'),$this->session->userdata('user_data')['name']." creó un presupuesto a ".$buyerDat->name.", quien debe una factura de ".$oldestInvioce2020->date);
			}

			$data  = array(
				'buyerId' => $buyer,
				'vendorId' => $vendor,
				'storeId' => $store,
				'total' => $total,
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'comments' => $comments,
			);

			//print_r($data);

			if ($this->purchases_model->save($data)) {
				$idPurchase = $this->purchases_model->lastID();
				$this->_save_detail($products,$idPurchase,$quantities,$purchase_rates,$purchase_bases,$purchase_subtotal);

				/*if($buyerDat->check_can_bill && $buyerDat->can_bill)
				{
					$data  = array(
						'can_bill' => 0
					);

					$this->users_model->update($buyerDat->idClient,$data);
				}*/

				redirect(base_url()."sisvent/commercial/purchases".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pbuyer, $piva ).'&rls=1');
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
					'providers' => $this->providers_model->getProviders(), 
					'buyers' => $this->users_model->getUsers(), 
				);
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->load->view("sisvent/commercial/purchases/add",$data);
			}
			
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'providers' => $this->providers_model->getProviders(), 
				'buyers' => $this->users_model->getUsers(), 
			);
			$this->session->set_flashdata("error","Debe ingresar al menos un producto");
			$this->load->view("sisvent/commercial/purchases/add",$data);
			//$this->add();
		}
		
	}

	function _save_detail($products,$idPurchase,$quantities,$rates,$price_base,$subtotal){
		
		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		for ($i=0; $i < count($products); $i++) { 
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$products[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($products[$i], $per_packages) === FALSE)." + " .array_search($products[$i], $per_packages). "' );</script>";

			$data  = array(
				'purchaseId' =>$idPurchase,
				'productId' =>$products[$i],
				'quantity' =>$quantities[$i],
				'unit' => $rates[$i],
				'base' => $price_base[$i],
				'total' =>$subtotal[$i]
			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			$this->purchases_model->save_detail($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function edit($purchase_id){

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$buyer = $this->input->get('c');
		$iva = $this->input->get('i');

		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$buyer)
			$buyer = 'all';
		if(is_null($iva))
			$iva = 'all';

		$purchase = $this->purchases_model->getPurchase($purchase_id);
		$details = $this->purchases_model->getDetails($purchase_id);
		foreach ($details as $key => $detail) {
			$producto = $this->inventory_model->getStoreProduct($purchase->storeId, $detail->productId);
			$detail->stock = empty($producto) ? 0 : $producto->stock;
		}

		$data  = array(
			'stores' => $this->stores_model->getStores(), 
			'purchase' => $purchase, 
			'providers' => $this->providers_model->getProviders(), 
			'buyers' => $this->users_model->getUsers(), 
			'details' => $details,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pbuyer' => $buyer,
			'piva' => $iva,
			'page' => $page,
		);
		$this->load->view("sisvent/commercial/purchases/edit",$data);
	}

	public function update(){
		$idPurchase = $this->input->post("id");
		$buyer = $this->input->post("buyer");
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
		$purchase_rates = $this->input->post("purchase-rates");
		$purchase_bases = $this->input->post("price_base");
		$quantities = $this->input->post("purchase-quantities");
		$purchase_subtotal = $this->input->post("purchase-subtotal");
		$comments = $this->input->post("comments");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pbuyer = $this->input->get('c');
		$iva = $this->input->get('i');


		if(!$page)
			$page = 1;
		if(!$pstore)
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pbuyer)
			$pbuyer = 'all';
		if(is_null($iva))
			$iva = 'all';
		
		date_default_timezone_set("America/Bogota");
		$data  = array(
			'buyerId' => $buyer,
			'total' => $total,
			'storeId' => $store,
			'vendorId' => $vendor,
			'comments' => $comments,
		);

		//print_r($data);

		if ($this->purchases_model->update($idPurchase,$data)) {
			$this->purchases_model->removeDetails($idPurchase);

			$this->_save_detail($products,$idPurchase,$quantities,$purchase_rates,$purchase_bases,$purchase_subtotal);
			//$this->_update_detail($products,$idPurchase,$quantities,$purchase_subtotal);
		    $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha editado presupuesto ".$idPurchase);
			redirect(base_url()."sisvent/commercial/purchases".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pbuyer, $iva ));
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'purchase' => $this->purchases_model->getPurchase($idPurchase), 
				'providers' => $this->providers_model->getProviders(), 
				'details' => $this->purchases_model->getDetails($idPurchase),
				'buyers' => $this->users_model->getUsers(), 
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pbuyer' => $pbuyer,
				'piva' => $iva,
				'page' => $page,
			);
			$this->session->set_flashdata("error","No se pudo guardar la información");
			$this->load->view("sisvent/commercial/purchases/edit",$data);
		}
			
	}

	function _update_detail($products,$idPurchase,$quantities,$subtotal){
		
		for ($i=0; $i < count($products); $i++) { 

			$data  = array(
				'quantity' =>$quantities[$i],
				'total' =>$subtotal[$i]
			);
			
			$this->purchases_model->update_detail($idPurchase,$products[$i],$data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idPurchase = $this->input->post("id");
		$data  = array(
			'purchase' => $this->purchases_model->getPurchase($idPurchase), 
			'details' => $this->purchases_model->getDetails($idPurchase),
		);
		$this->load->view("sisvent/commercial/purchases/view",$data);
	}

	public function search($term){
		
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$buyer = $this->input->get('c');
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
		if(!$buyer)
			$buyer = 'all';
		if(is_null($iva))
			$iva = 'all';

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$total = $this->purchases_model->getTotalSearch($term,$store, $vendor, $state, $buyer, $iva, $user->admin_store_arr);
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'providers' => $this->providers_model->getProviders(),
			'buyers' => $this->users_model->getUsers(),
			'total' => $total,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pbuyer' => $buyer,
			'piva' => $iva,
			'page' => $pag,
			'limit' => $limit,
			'purchases' => $this->purchases_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $buyer, $iva, $user->admin_store_arr, $page, $limit)
		);

		$this->load->view("sisvent/commercial/purchases/list",$data);	
		//$this->purchases_model->searchByWord($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $buyer, $page, $limit);
		//redirect(base_url()."sisvent/business/buyers");
		//echo base_url()."sisvent/commercial/purchases";
	}

	public function unblock(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idClient = $this->input->post("id");
		$buyer = $this->users_model->getClient($idClient);
		echo json_encode($buyer);
	}

	public function delete($idPurchase){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->purchases_model->remove($idPurchase);
	    $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha eliminado presupuesto ".$idPurchase);
	//redirect(base_url()."sisvent/business/buyers");
		echo base_url()."sisvent/commercial/purchases";
	}

	public function approve($idPurchase){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pbuyer = $this->input->get('c');
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
		if(!$pbuyer)
			$pbuyer = 'all';
		if(is_null($iva))
			$iva = 'all';

		$purchase = $this->purchases_model->getPurchase($idPurchase);


		$buyer = $this->users_model->getClient($purchase->buyerId);
		$debt = $this->invoices_model->getClientDebt($purchase->buyerId);
		$oldestInvioce = $this->invoices_model->oldestNonPaidInvioce($purchase->buyerId);

		$debt2020 = $this->noinvoices_model->getClientDebt($purchase->buyerId);
		$oldestInvioce2020 = $this->noinvoices_model->oldestNonPaidInvioce($purchase->buyerId);

		$buyerDebt = $debt->debt + $debt2020->debt;


		if($oldestInvioce)
		{
			$buyerOldestInvioce = $oldestInvioce->date;
			$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
		}else if($oldestInvioce2020)
		{
			$buyerOldestInvioce = $oldestInvioce2020->date;
			$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce2020->date));
		}
		else{
			$buyerOldestInvioce = date( "Y-m-d H:i:s");
			$oldestInvioceDate = date( "Y-m-d H:i:s");
		}

		//$oldestInvioceDate = date( "Y-m-d H:i:s", strtotime($oldestInvioce->date));
        $todayMin3M = date( "Y-m-d H:i:s", strtotime('-2 months'));
		$isClientDefaulter = $oldestInvioceDate < $todayMin3M;

		if($buyerDebt > $buyer->maximum_debt && !$buyer->can_bill)
        {
        	$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
			if(!empty($user->admin_store))
				$user->admin_store_arr = explode(',', $user->admin_store);
			else
				$user->admin_store_arr = array();

            //showModal("Este buyere está moroso, debe $"+data.debt);
            $total = $this->purchases_model->getTotal($this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pbuyer, $iva, $user->admin_store_arr);
			$last = ceil( $total / $limit );

			if($page > $last)
				$page = $last;

			if($page <= 0)
				$page = 1;

        	/*$data  = array(
				'stores' => $this->stores_model->getStores(),
				'providers' => $this->providers_model->getProviders(),
				'buyers' => $this->users_model->getUsers(),
				'total' => $total,
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pbuyer' => $pbuyer,
				'piva' => $iva,
				'page' => $page,
				'limit' => $limit,
				'purchases' => $this->purchases_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pbuyer, $iva, $user->admin_store_arr, $page, $limit)
			);*/

			$this->session->set_flashdata("error","Este buyere está moroso, debe $".$buyerDebt);
			//$this->load->view("sisvent/commercial/purchases/list",$data);
			echo base_url()."sisvent/commercial/purchases".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pbuyer, $iva );
        }else if($isClientDefaulter && !$buyer->can_bill)
        {
          //showModal("Este buyere no ha pagado facturas vencidas, debe una de "+data.oldestInvioce);
          $user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
			if(!empty($user->admin_store))
				$user->admin_store_arr = explode(',', $user->admin_store);
			else
				$user->admin_store_arr = array();
		
            $total = $this->purchases_model->getTotal($this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pbuyer, $iva, $user->admin_store_arr);
			$last = ceil( $total / $limit );

			if($page > $last)
				$page = $last;

			if($page <= 0)
				$page = 1;

        	/*$data  = array(
				'stores' => $this->stores_model->getStores(),
				'providers' => $this->providers_model->getProviders(),
				'buyers' => $this->users_model->getUsers(),
				'total' => $total,
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pbuyer' => $pbuyer,
				'piva' => $iva,
				'page' => $page,
				'limit' => $limit,
				'purchases' => $this->purchases_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $pstore, $pvendor, $pstate, $pbuyer, $iva, $user->admin_store_arr, $page, $limit)
			);*/

			$this->session->set_flashdata("error","Este buyere no ha pagado facturas vencidas, debe una de ".$buyerOldestInvioce);
			//$this->load->view("sisvent/commercial/purchases/list",$data);
			echo base_url()."sisvent/commercial/purchases".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pbuyer, $iva );
        }else{
		
			$data  = array(
				'state' => 1,
			);

			$this->purchases_model->update($idPurchase,$data);

			$details = $this->purchases_model->getDetails($idPurchase);

			date_default_timezone_set("America/Bogota");
			$data  = array(
				'purchaseId' => $purchase->idPurchase,
				'buyerId' => $purchase->buyerId,
				'vendorId' => $purchase->vendorId,
				'storeId' => $purchase->storeId,
				'total' => $purchase->total,
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'payment' => 0,
				'comments' => $purchase->comments,
			);

			//print_r($data);

			if ($this->invoices_model->save($data)) {
				$idInvoice = $this->invoices_model->lastID();

				if($buyer->check_can_bill && $buyer->can_bill)
				{
					$data  = array(
						'can_bill' => 0
					);

					$this->users_model->update($buyer->idClient,$data);
				}
				
				foreach($details as $detail) {
					
					$this->updateProduct($purchase->storeId,$detail->productId,$detail->quantity);

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

				// Registrar asiento contable de la factura
				$this->accounting_lib->recordInvoice(
					$idInvoice,
					$purchase->buyerId,
					$purchase->storeId,
					$purchase->total,
					$this->session->userdata('user_data')['uname']
				);

	        	$this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha aprobado presupuesto ".$idPurchase." a factura ".$idInvoice);
				echo base_url()."sisvent/commercial/purchases".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pbuyer, $iva );
			}else
			{
				echo base_url()."sisvent/commercial/purchases".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pbuyer, $iva );
			}
		}

		//$this->purchases_model->remove($idPurchase);
		//redirect(base_url()."sisvent/business/buyers");
		//$data  = array(
		//	'stores' => $this->stores_model->getStores(), 
		//	'providers' => $this->providers_model->getProviders(), 
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

	public function duplicate($purchase_id){
		$this->backend_lib->control([1]);

		$limit = 50;

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$buyer = $this->input->get('c');
		$iva = $this->input->get('i');


		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$buyer)
			$buyer = 'all';
		if(is_null($iva))
			$iva = 'all';

		$purchase = $this->purchases_model->getPurchase($purchase_id);
		$details = $this->purchases_model->getDetails($purchase_id);
		
		date_default_timezone_set("America/Bogota");
		$data  = array(
			'buyerId' => $purchase->buyerId,
			'vendorId' => $purchase->vendorId,
			'storeId' => $purchase->storeId,
			'total' => $purchase->total,
			'date' => date('Y-m-d H:i:s'),
			'state' => 0,
			'comments' => "",
		);

		//print_r($data);

		//if ($this->purchases_model->save($data)) {
			//$idPurchase = $this->purchases_model->lastID();
			/*foreach ($details as $key => $detail) {
				
				$data  = array(
					'purchaseId' =>$idPurchase,
					'productId' =>$detail->productId,
					'quantity' =>$detail->quantity,
					'unit' => $detail->unit,
					'base' => $detail->base,
					'total' =>$detail->total
				);
					
				$this->purchases_model->save_detail($data);					
			}*/
			//redirect(base_url()."sisvent/commercial/purchases");

			//$purchaseNew = $this->purchases_model->getPurchase($purchase_id);
			//$detailsNew = $this->purchases_model->getDetails($purchase_id);
			foreach ($details as $key => $detail) {
				$producto = $this->inventory_model->getStoreProduct($purchase->storeId, $detail->productId);
				$detail->stock = empty($producto) ? 0 : $producto->stock;
			}

			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'purchase' => $purchase, 
				'providers' => $this->providers_model->getProviders(), 
				'buyers' => $this->users_model->getUsers(), 
				'details' => $details,
				'params' => createFullParamsLinks($page, $store, $vendor, $state, $buyer, $iva),
			);
			$this->load->view("sisvent/commercial/purchases/duplicate",$data);
		/*}else
		{
			//echo base_url()."sisvent/commercial/purchases".createFullParamsLinks($page, $store, $vendor, $state, $buyer );
			$total = $this->purchases_model->getTotal($store, $vendor, $state, $buyer);
			$last       = ceil( $total / $limit );

			if($page > $last)
				$page = $last;

			if($page <= 0)
				$page = 1;

			$data  = array(
				'stores' => $this->stores_model->getStores(),
				'providers' => $this->providers_model->getProviders(),
				'buyers' => $this->users_model->getUsers(),
				'total' => $total,
				'pstore' => $store,
				'pvendor' => $vendor,
				'pstate' => $state,
				'pbuyer' => $buyer,
				'page' => $page,
				'limit' => $limit,
				'purchases' => $this->purchases_model->getPurchases($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $buyer, $page, $limit)
			);
			$this->load->view("sisvent/commercial/purchases/list",$data);
		}*/
		
	}

	public function createExcel() {
		$fileName = 'purchases.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$purchases = $this->purchases_model->getPurchases(true,  'all',  'all',  'all',  'all', -1, "");
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
        foreach ($purchases as $val){
            $sheet->setCellValue('A' . $rows, $val->buyer_name);
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