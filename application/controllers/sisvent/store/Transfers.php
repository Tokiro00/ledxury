<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transfers extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('traspasos');
        $this->load->model("inventory_model");
        $this->load->model("stores_model");
        $this->load->model("transfers_model");
    }

	public function index()
	{
		$data  = array(
			'transfers' => $this->transfers_model->getTransfers()
		);
		$this->load->view("sisvent/store/transfers/index",$data);
		
	}

	public function add()
	{
		$data  = array(
			'stores' => $this->stores_model->getStores()
		);
		$this->load->view("sisvent/store/transfers/add",$data);
		
	}

	/*public function add(){
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
		);
		$this->load->view("sisvent/store/transfers/add",$data);
	}*/

	public function getProductss($valor,$orstr){
		//$valor = $this->input->post("valor");
		//$orstr = $this->input->post("orstr");
		$products = $this->inventory_model->getStoreProducts($valor,$orstr);
		echo json_encode($products);
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

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$origin_store = $this->input->post("origin-store");
		$destination_store = $this->input->post("destination-store");
		$products = $this->input->post("refs");
		$quantities = $this->input->post("trfr-quantities");
		$stock = $this->input->post("stock");
		$comments = $this->input->post("comments");
		
		if($origin_store != $destination_store)
		{
			if($products && count($products) > 0)
			{
				date_default_timezone_set("America/Bogota");
				$data  = array(
					'userId' => $this->session->userdata('user_data')['uname'],
					'originId' => $origin_store,
					'destinationId' => $destination_store,
					'comments' => $comments,
					'date' => date('Y-m-d H:i:s')
				);
				$this->transfers_model->save($data);
				$idTrasnfers = $this->transfers_model->lastID();

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

					$data  = array(
						'idTransfer' => $idTrasnfers, 
						'idProduct' => $products[$i],
						'quantity' => $quantities[$i]
					);
					$this->transfers_model->save_detail($data);
					
				}
				redirect(base_url()."sisvent/store/transfers");
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
				);
				$this->session->set_flashdata("error","Debe ingresar al menos un producto");
				$this->load->view("sisvent/store/transfers/index",$data);
				//$this->add();
			}
		}else
		{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
			);
			$this->session->set_flashdata("error","El origen y el destino deben ser diferentes");
			$this->load->view("sisvent/store/transfers/index",$data);
			//$this->add();
		}
	}

	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idTransfer = $this->input->post("id");

		$data  = array(
			'transfer' => $this->transfers_model->getTransfer($idTransfer), 
			'details' => $this->transfers_model->getDetails($idTransfer),
		);
		//echo $data;
		$this->load->view("sisvent/store/transfers/view",$data);
	}

	public function delete($idTransfer){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->transfers_model->remove($idTransfer);
		echo base_url()."sisvent/store/transfers";
	}

}