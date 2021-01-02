<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->backend_lib->control();
	}

	public function index()
	{
		$data = array(
			//'numOrders' =>  $this->backend_model->rowCount('orders',$this->permissions->read_other_per),
			//'numUsers' =>  $this->backend_model->rowCount('users'),
			//'numProducts' =>  $this->backend_model->rowCount('products'),
			//'lowInventory' =>  $this->productos_model->getLowInventoryProducts(),
			//'soldOut' =>  $this->productos_model->getSoldOutProducts(),
			//'u_permissions' => $this->permissions 
			);
		
		$this->load->view("sisvent/dashboard", $data);
		//$this->load->view("layouts/footer");

	}

}