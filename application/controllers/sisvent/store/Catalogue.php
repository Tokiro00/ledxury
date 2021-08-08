<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Catalogue extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        //$this->load->library('form_validation');
        $this->load->model("inventory_model");
        $this->load->model("stores_model");
    }

	public function index()
	{
		$this->load->view("sisvent/store/catalogue/index");
		
	}

	public function view($store)
	{
		$page = $this->input->get('p');
		
		$limit = 48;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getCurrentInventoryCount($store);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$products = $this->inventory_model->getCurrentInventory($store,$page,$limit);
		$data  = array(
			'store' => $this->stores_model->getStore($store), 
			'products' => $products,
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
		);
		$this->load->view("sisvent/store/catalogue/view",$data);
	}
}
