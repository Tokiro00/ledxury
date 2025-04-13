<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Count extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->load->helper('file');
        $this->load->model("inventory_model");
        $this->load->model("stores_model");
        $this->load->model("users_model");
    }

	public function index()
	{
		$this->backend_lib->control([1,2,4]);
		
		$page = $this->input->get('p');
		
		$limit = 10;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getTotalCount();
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'inventories' => $this->inventory_model->getCurrentInventories(), 
			'counts' => $this->inventory_model->getCounts($page, $limit), 
		);
		$this->load->view("sisvent/store/count/list",$data);
		
	}

	public function viewInventory()
	{
		$this->backend_lib->control([1,2,4]);
		$data  = array(
			'products' => $this->inventory_model->getCurrentInventory(-1), 
			'stores' => $this->stores_model->getStores()
		);
		$this->load->view("sisvent/store/count/index",$data);
		
	}

	public function addCount($store){
		$this->backend_lib->control([1,2,4]);
		$products = $this->inventory_model->getCurrentCount($store, round($this->inventory_model->getCurrentInventoryCount($store)/30));
		if(sizeof($products) <= 0)
		{
			$this->inventory_model->resetCount($store);
			$products = $this->inventory_model->getCurrentCount($store, round($this->inventory_model->getCurrentInventoryCount($store)/30));
		}
		$data  = array(
			'products' => $products, 
			//'count' => $this->inventory_model->getCurrentInventoryCount($store), 
			'users' => $this->users_model->getUsers(), 
			'stores' => $this->stores_model->getStores(),
			'store' => $store, 
		);
		$this->load->view("sisvent/store/count/addCount",$data);
	}

	public function storeCount(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		date_default_timezone_set("America/Bogota");

		$user = $this->input->post("user");
		$store = $this->input->post("store");
		$comment = $this->input->post("comments");
		$products = $this->input->post("refs");
		$quantities = $this->input->post("quantities");
		$stock = $this->input->post("stock");

		//$inve = $this->inventory_model->getStoreProduct($store,$products[$i]);

		$data  = array(
			'storeId' => $store, 
			'userId' => $user,
			'comments' => $comment,
			'state' => 0,
			'date' => date('Y-m-d H:i:s'),
		);
		$this->inventory_model->saveCount($data);
		$idCount = $this->inventory_model->lastID();

		for ($i=0; $i < count($products); $i++) { 
			
			$data  = array(
				'idCount' => $idCount, 
				'idProduct' => $products[$i],
				'quantity' => $quantities[$i],
				'stock' => $stock[$i]
			);
			$this->inventory_model->saveCountDetails($data);
			$data  = array(
				'counted' => 1
			);
			$this->inventory_model->update($store,$products[$i],$data);
			
		}
				
		redirect(base_url()."sisvent/store/count");
		
	}

	
	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idCount = $this->input->post("id");
		$data  = array(
			'count' => $this->inventory_model->getCount($idCount), 
			'details' => $this->inventory_model->getCountDetails($idCount),
		);
		$this->load->view("sisvent/store/count/view",$data);
	}

	public function views($idInventory){
		//$this->outh_model->CSRFVerify();

		//if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		//$idInventory = $this->input->post("id");
		$data  = array(
			'inventory' => $this->inventory_model->getInventory($idInventory), 
			'details' => $this->inventory_model->getFinal($idInventory),
		);
		$this->load->view("sisvent/store/count/view",$data);
		//echo "<pre>";
		//print_r($data);
		//echo "</pre>";
	}

	public function edit($count_id){
		$this->backend_lib->control([1,2,4]);
		$data =array( 
			'count' => $this->inventory_model->getCount($count_id),
			'products' => $this->inventory_model->getCountDetails($count_id)
		);
		//print_r($data);
		$this->load->view("sisvent/store/count/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$idCount = $this->input->post("idCount");
		$store = $this->input->post("store");
		$products = $this->input->post("refs");
		$quantities = $this->input->post("quantities");
		$stock = $this->input->post("stock");
		
		
		for ($i=0; $i < count($products); $i++) { 

			$data  = array(
				'quantity' => $quantities[$i]
			);
			$this->inventory_model->updateCountDetails($idCount, $products[$i], $data);
				
			
		}
		redirect(base_url()."sisvent/store/count");
		
		
	}
	
}