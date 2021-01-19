<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("payments_model");
        $this->load->model("invoices_model");
        $this->load->model("stores_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
    }

	public function index()
	{
		$data  = array(
			'invoices' => $this->invoices_model->getInvoices($this->session->userdata('user_data')['role'] != 3)
		);
		$this->load->view("sisvent/commercial/invoices/list",$data);
		
	}

	/*public function add(){
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
			'vendors' => $this->vendors_model->getVendors(), 
		);
		$this->load->view("sisvent/commercial/invoices/add",$data);
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
		$invoice_rates = $this->input->post("invoice-rates");
		$quantities = $this->input->post("invoice-quantities");
		$invoice_subtotal = $this->input->post("invoice-subtotal");
				
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

			if ($this->invoices_model->save($data)) {
				$idInvoice = $this->invoices_model->lastID();
				$this->_save_detail($products,$idInvoice,$quantities,$invoice_rates,$invoice_subtotal);
				redirect(base_url()."sisvent/commercial/invoices");
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
					'vendors' => $this->vendors_model->getVendors(), 
				);
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->load->view("sisvent/commercial/invoices/add",$data);
			}
			
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'vendors' => $this->vendors_model->getVendors(), 
			);
			$this->session->set_flashdata("error","Debe ingresar al menos un producto");
			$this->load->view("sisvent/commercial/invoices/add",$data);
			//$this->add();
		}
		
	}

	function _save_detail($products,$idInvoice,$quantities,$rates,$subtotal){
		
		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		for ($i=0; $i < count($products); $i++) { 
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$products[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($products[$i], $per_packages) === FALSE)." + " .array_search($products[$i], $per_packages). "' );</script>";

			$data  = array(
				'invoiceId' =>$idInvoice,
				'productId' =>$products[$i],
				'quantity' =>$quantities[$i],
				'unit' => $rates[$i],
				'total' =>$subtotal[$i]
			);
				
			$this->invoices_model->save_detail($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}*/

	public function edit($invoice_id){
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($invoice_id), 
			'details' => $this->invoices_model->getDetails($invoice_id),
		);
		$this->load->view("sisvent/commercial/invoices/edit",$data);
	}

	public function update(){
		$idInvoice = $this->input->post("id");
		$comments = $this->input->post("comments");
		
		$data  = array(
			'comments' => $comments,
		);

		if ($this->invoices_model->update($idInvoice,$data)) {
			redirect(base_url()."sisvent/commercial/invoices");
		}
		else{
			$data  = array(
				'invoice' => $this->invoices_model->getInvoice($idInvoice), 
				'details' => $this->invoices_model->getDetails($idInvoice),
			);
			$this->session->set_flashdata("error","No se pudo guardar la información");
			$this->load->view("sisvent/commercial/invoices/edit",$data);
		}
			
	}

	
	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("id");
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'details' => $this->invoices_model->getDetails($idInvoice),
		);
		$this->load->view("sisvent/commercial/invoices/view",$data);
	}

	public function delete($idInvoice){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->invoices_model->remove($idInvoice);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/commercial/invoices";
	}

	public function payment(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("id");
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'vendors' => $this->vendors_model->getVendors(), 
			'methods' => $this->payments_model->getPaymentMethods(), 
		);
		$this->load->view("sisvent/commercial/invoices/payment",$data);
	}

	public function makepayment(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("id");
		$method = $this->input->post("method");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comment");

		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'invoiceId' =>$idInvoice,
			'clientId' =>$invoice->clientId,
			'vendorId' =>$invoice->vendorId,
			'paymentMethod' =>$method,
			'payment' =>$payment,
			'comments' =>$comment
		);

		$this->payments_model->save($data);

		$acum = $this->payments_model->getInvoicePayment($idInvoice);

		$data  = array(
			'payment' => $acum->payment,
			'state' => $acum->payment >= $invoice->total ? 2 : 1,
		);

		$this->invoices_model->update($idInvoice,$data);

		echo base_url()."sisvent/commercial/invoices";
	}

}