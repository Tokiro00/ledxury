<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nopayments extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('metodos_pago');
        $this->load->model("noinvoices_model");
        $this->load->model("nopayments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
    }

	public function index()
	{
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;

		$total = $this->nopayments_model->getTotal();
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'page' => $page,
			'total' => $total,
			'limit' => $limit,
			'payments' => $this->nopayments_model->getPayments($page, $limit), 
		);
		$this->load->view("sisvent/admin/nopayments/list",$data);
		
	}

	public function search($term)
	{
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->nopayments_model->getTotalSearch($term);
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'total' => $total,
			'page' => $pag,
			'limit' => $limit,
			'payments' => $this->nopayments_model->searchByWord($term, $page, $limit), 
		);
		$this->load->view("sisvent/admin/nopayments/list",$data);
		
	}

	public function add(){

		$data =array( 
			'invoices' => $this->noinvoices_model->getNonPaidInvoices($this->session->userdata('user_data')['role'] != 3),
			'methods' => $this->nopayments_model->getPaymentMethods(), 
		);
		$this->load->view("sisvent/admin/nopayments/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("invoice-id");
		$method = $this->input->post("method");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comments");
		$date = $this->input->post("date");
		if(!$date)
			$date = date('Y-m-d H:i:s');
		
		$invoice = $this->noinvoices_model->getInvoice($idInvoice);

		$data  = array(
			'invoiceId' =>$idInvoice,
			'clientId' =>$invoice->clientId,
			'vendorId' =>$invoice->vendorId,
			'paymentMethod' =>$method,
			'payment' =>$payment,
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			'comments' =>$comment
		);

		$this->nopayments_model->save($data);

		$acum = $this->nopayments_model->getInvoicePayment($idInvoice);

		$data  = array(
			'payment' => $acum->payment,
			'state' => $acum->payment + $invoice->discount >= $invoice->total ? 2 : 1,
		);

		$this->noinvoices_model->update($idInvoice,$data);

		redirect(base_url()."sisvent/admin/nopayments");
		/*$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'name' => $name
			);

			if ($this->nopayments_model->save($data)) {
				redirect(base_url()."sisvent/admin/nopayments");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/admin/nopayments/add");
			}
		}
		else{
			$this->add();
		}*/
	}

	
	public function getInvoice()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$invoice = $this->noinvoices_model->getInvoice($this->input->post("inv"));
		echo json_encode($invoice);
	}
	/*public function getVendorClients()
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
	}*/

	public function edit($payment_id){
		$data =array( 
			'payment' => $this->nopayments_model->getPayment($payment_id)
		);
		//print_r($data);
		$this->load->view("sisvent/admin/nopayments/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$payment_id = $this->input->post("payment_id");
		$comment = $this->input->post("comments");
		$date = $this->input->post("date");
		
		$data  = array(
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			'comments' =>$comment
		);

		if ($this->nopayments_model->update($payment_id,$data)) {
			redirect(base_url()."sisvent/admin/nopayments");
		}
		else{
			$data =array( 
				'payment' => $this->nopayments_model->getPayment($payment_id)
			);
			$this->session->set_flashdata("error","No se pudo actualizar la información");
			$this->load->view("sisvent/admin/nopayments/edit",$data);
			//redirect(base_url()."sisvent/admin/nopayments/edit/".$payment_id);
		}
		
	}

	public function delete($payment_id){
		
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$payment = $this->nopayments_model->getPayment($payment_id);

		$invoice = $this->noinvoices_model->getInvoice($payment->invoiceId);

		$data  = array(
			'payment' => $invoice->payment - $payment->payment,
			'state' => ($invoice->payment - $payment->payment >= $invoice->total) ? 2 : (($invoice->payment - $payment->payment <= 0) ? 0 : 1),
		);

		$this->noinvoices_model->update($payment->invoiceId,$data);

		$this->nopayments_model->remove($payment_id);
		//redirect(base_url()."sisvent/admin/nopayments");
		echo base_url()."sisvent/admin/nopayments";
	}
	
}