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
		$budget_rates = $this->input->post("budget-rates");
		$budget_subtotal = $this->input->post("budget-subtotal");
		$products = $this->input->post("refs");
		$vendor = $this->input->post("vendor");
		$client = $this->input->post("client");
		$store = $this->input->post("store");
		$client = $this->input->post("client");
		$quantities = $this->input->post("budget-quantities");
		$stock = $this->input->post("stock");
		
		redirect(base_url()."sisvent/commercial/budgets");
		/*if($origin_store != $destination_store)
		{
			if($products && count($products) > 0)
			{
				for ($i=0; $i < count($products); $i++) { 
					$inve = $this->inventory_model->getStoreProduct($destination_store,$products[$i]);

					if(empty($inve))
					{
						$data  = array(
							'idStore' => $destination_store, 
							'idProduct' => $products[$i],
							'stock' => $quantities[$i]
						);
						$this->inventory_model->save($data);
					}else{
						$data  = array(
							'stock' => $inve->stock+$quantities[$i]
						);
						$this->inventory_model->update($destination_store,$products[$i],$data);
					}

					$data  = array(
						'stock' => $stock[$i] - $quantities[$i]
					);
					$this->inventory_model->update($origin_store,$products[$i],$data);
					
				}
				redirect(base_url()."sisvent/commercial/inventory");
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
				);
				$this->session->set_flashdata("error","Debe ingresar al menos un producto");
				$this->load->view("sisvent/commercial/budgets/index",$data);
				//$this->add();
			}
		}else
		{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
			);
			$this->session->set_flashdata("error","El origen y el destino deben ser diferentes");
			$this->load->view("sisvent/commercial/budgets/index",$data);
			//$this->add();
		}*/
	}

}