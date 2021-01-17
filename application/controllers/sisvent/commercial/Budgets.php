<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budgets extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control();
        $this->load->model("budgets_model");
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
		$valor = $this->input->post("valor");
		$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		echo json_encode($products);
	}

	public function getProduct(){
		$producto = $this->inventory_model->getStoreProduct($this->input->post("orstr"),$this->input->post("ref"));
		echo json_encode($producto);
	}

	public function getVendorClients()
	{
		$vendors = $this->clients_model->getVendorClients($this->input->post("vendor"));
		echo json_encode($vendors);
	}

	public function getClient()
	{
		$client = $this->clients_model->getClient($this->input->post("client"));
		echo json_encode($client);
	}

	public function store(){
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
			$this->load->view("sisvent/commercial/budgets/add",$data);
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
		$idBudget = $this->input->post("id");
		$data  = array(
			'budget' => $this->budgets_model->getBudget($idBudget), 
			'details' => $this->budgets_model->getDetails($idBudget),
		);
		$this->load->view("sisvent/commercial/budgets/view",$data);
	}

	public function views($idBudget){
		//$idBudget = $this->input->post("id");
		$data  = array(
			'budget' => $this->budgets_model->getBudget($idBudget), 
			'details' => $this->budgets_model->getDetails($idBudget),
		);
		$this->load->view("sisvent/commercial/budgets/view",$data);
	}

}