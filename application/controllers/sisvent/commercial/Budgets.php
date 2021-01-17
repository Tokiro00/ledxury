<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budgets extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control();
        $this->load->model("budgets_model");
        $this->load->model("invoices_model");
        $this->load->model("stores_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
    }

	public function index()
	{
		$data  = array(
			'budgets' => $this->budgets_model->getBudgets($this->session->userdata('user_data')['role'] != 3)
		);
		$this->load->view("sisvent/commercial/budgets/list",$data);
		
	}

	public function add(){
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
			'vendors' => $this->vendors_model->getVendors(), 
		);
		$this->load->view("sisvent/commercial/budgets/add",$data);
	}

	public function getProducts(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$valor = $this->input->post("valor");
		$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		echo json_encode($products);
	}

	public function getProduct(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$producto = $this->inventory_model->getStoreProduct($this->input->post("orstr"),$this->input->post("ref"));
		echo json_encode($producto);
	}

	public function getVendorClients()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendors = $this->clients_model->getVendorClients($this->input->post("vendor"));
		echo json_encode($vendors);
	}

	public function getClient()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$client = $this->clients_model->getClient($this->input->post("client"));
		echo json_encode($client);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("vendor");
		$client = $this->input->post("client");
		$rate = $this->input->post("rate");
		$store = $this->input->post("store");
		$hasIva = $this->input->post("hasIva");
		$total = $this->input->post("total");
		$iva = 8;
        if(in_array($this->session->userdata('user_data')['role'], [1])):
			$iva = $this->input->post("iva");
        endif;

		$products = $this->input->post("refs");
		$stock = $this->input->post("stock");
		$budget_rates = $this->input->post("budget-rates");
		$quantities = $this->input->post("budget-quantities");
		$budget_subtotal = $this->input->post("budget-subtotal");
				
		if($products && count($products) > 0)
		{
			date_default_timezone_set("America/Bogota");
			$data  = array(
				'clientId' => $client,
				'vendorId' => $vendor,
				'storeId' => $store,
				'total' => $total,
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'hasIva' => $hasIva ?? 0,
				'iva' => $iva,
			);

			//print_r($data);

			if ($this->budgets_model->save($data)) {
				$idBudget = $this->budgets_model->lastID();
				$this->_save_detail($products,$idBudget,$quantities,$budget_rates,$budget_subtotal);
				redirect(base_url()."sisvent/commercial/budgets");
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
					'vendors' => $this->vendors_model->getVendors(), 
				);
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->load->view("sisvent/commercial/budgets/add",$data);
			}
			
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'vendors' => $this->vendors_model->getVendors(), 
			);
			$this->session->set_flashdata("error","Debe ingresar al menos un producto");
			$this->load->view("sisvent/commercial/budgets/add",$data);
			//$this->add();
		}
		
	}

	function _save_detail($products,$idBudget,$quantities,$rates,$subtotal){
		
		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		for ($i=0; $i < count($products); $i++) { 
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$products[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($products[$i], $per_packages) === FALSE)." + " .array_search($products[$i], $per_packages). "' );</script>";

			$data  = array(
				'budgetId' =>$idBudget,
				'productId' =>$products[$i],
				'quantity' =>$quantities[$i],
				'unit' => $rates[$i],
				'total' =>$subtotal[$i]
			);
				
			$this->budgets_model->save_detail($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function edit($budget_id){
		$data  = array(
			'budget' => $this->budgets_model->getBudget($budget_id), 
			'details' => $this->budgets_model->getDetails($budget_id),
		);
		$this->load->view("sisvent/commercial/budgets/edit",$data);
	}

	public function update(){
		$idBudget = $this->input->post("id");
		$total = $this->input->post("total");
		if(in_array($this->session->userdata('user_data')['role'], [1])):
			$iva = $this->input->post("iva");
        endif;

		$products = $this->input->post("refs");
		$budget_rates = $this->input->post("budget-rates");
		$quantities = $this->input->post("budget-quantities");
		$budget_subtotal = $this->input->post("budget-subtotal");
				
		
		date_default_timezone_set("America/Bogota");
		$data  = array(
			'total' => $total,
			'iva' => $iva,
		);

		//print_r($data);

		if ($this->budgets_model->update($idBudget,$data)) {
			$this->_update_detail($products,$idBudget,$quantities,$budget_subtotal);
			redirect(base_url()."sisvent/commercial/budgets");
		}
		else{
			$data  = array(
				'budget' => $this->budgets_model->getBudget($idBudget), 
				'details' => $this->budgets_model->getDetails($idBudget),
			);
			$this->session->set_flashdata("error","No se pudo guardar la información");
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

	public function delete($idBudget){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->budgets_model->remove($idBudget);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/commercial/budgets";
	}

	public function approve($idBudget){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$data  = array(
			'state' => 1,
		);

		$this->budgets_model->update($idBudget,$data);

		$budget = $this->budgets_model->getBudget($idBudget);
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
			'hasIva' => $budget->hasIva,
			'iva' => $budget->iva,
			'payment' => 0,
		);

		//print_r($data);

		if ($this->invoices_model->save($data)) {
			$idInvoice = $this->invoices_model->lastID();
			
			foreach($details as $detail) {
				
				$this->updateProduct($budget->storeId,$detail->productId,$detail->quantity);

				$data  = array(
					'invoiceId' =>$idInvoice,
					'productId' =>$detail->productId,
					'quantity' =>$detail->quantity,
					'unit' => $detail->unit,
					'total' =>$detail->subtotal
				);

				$this->invoices_model->save_detail($data);
			}

			echo base_url()."sisvent/commercial/invoices";
		}else
		{
			echo base_url()."sisvent/commercial/budgets";
		}

		//$this->budgets_model->remove($idBudget);
		//redirect(base_url()."sisvent/business/clients");
		//$data  = array(
		//	'stores' => $this->stores_model->getStores(), 
		//	'vendors' => $this->vendors_model->getVendors(), 
		//);
		//$this->load->view("sisvent/commercial/invoices/add",$data);
	}

	public function approves($idBudget){
		
		$data  = array(
			'state' => 1,
		);

		$this->budgets_model->update($idBudget,$data);

		$budget = $this->budgets_model->getBudget($idBudget);
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
			'hasIva' => $budget->hasIva,
			'iva' => $budget->iva,
			'payment' => 0,
		);

		//print_r($data);

		if ($this->invoices_model->save($data)) {
			$idInvoice = $this->invoices_model->lastID();
			
			foreach($details as $detail) {
				
				$this->updateProduct($budget->storeId,$detail->productId,$detail->quantity);

				$data  = array(
					'invoiceId' =>$idInvoice,
					'productId' =>$detail->productId,
					'quantity' =>$detail->quantity,
					'unit' => $detail->unit,
					'total' =>$detail->subtotal
				);

				$this->invoices_model->save_detail($data);
			}

			echo base_url()."sisvent/commercial/invoices";
		}else
		{
			echo base_url()."sisvent/commercial/budgets";
		}

		//$this->budgets_model->remove($idBudget);
		//redirect(base_url()."sisvent/business/clients");
		//$data  = array(
		//	'stores' => $this->stores_model->getStores(), 
		//	'vendors' => $this->vendors_model->getVendors(), 
		//);
		//$this->load->view("sisvent/commercial/invoices/add",$data);
	}

	protected function updateProduct($store,$idproducto,$cantidad){
		//$inve = $this->inventory_model->getStoreProduct($products[$i]);
		$productoActual = $this->inventory_model->getStoreProduct($store,$idproducto);
		$data = array(
			'stock' => $productoActual->stock - $cantidad
		);
		$this->inventory_model->update($store,$idproducto,$data);
		//$data  = array(
		//	'stock' => $stock[$i] - $quantities[$i]
		//);
		//$this->inventory_model->update($origin_store,$products[$i],$data);
	}

}