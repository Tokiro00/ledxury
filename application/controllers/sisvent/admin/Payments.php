<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
    }

	public function index()
	{
		$data  = array(
			'payments' => $this->payments_model->getPayments(), 
		);
		$this->load->view("sisvent/admin/payments/list",$data);
		
	}

	public function add(){

		$data =array( 
			'invoices' => $this->invoices_model->getNonPaidInvoices($this->session->userdata('user_data')['role'] != 3),
			'methods' => $this->payments_model->getPaymentMethods(), 
		);
		$this->load->view("sisvent/admin/payments/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("invoice-id");
		$method = $this->input->post("method");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comments");
		$date = $this->input->post("date");

		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'invoiceId' =>$idInvoice,
			'clientId' =>$invoice->clientId,
			'vendorId' =>$invoice->vendorId,
			'paymentMethod' =>$method,
			'payment' =>$payment,
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			'comments' =>$comment
		);

		$this->payments_model->save($data);

		$acum = $this->payments_model->getInvoicePayment($idInvoice);

		$data  = array(
			'payment' => $acum->payment,
			'state' => $acum->payment >= $invoice->total ? 2 : 1,
		);

		$this->invoices_model->update($idInvoice,$data);

		redirect(base_url()."sisvent/admin/payments");
		/*$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'name' => $name
			);

			if ($this->payments_model->save($data)) {
				redirect(base_url()."sisvent/admin/payments");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/admin/payments/add");
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

		$invoice = $this->invoices_model->getInvoice($this->input->post("inv"));
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
			'payment' => $this->payments_model->getPayment($payment_id)
		);
		//print_r($data);
		$this->load->view("sisvent/admin/payments/edit",$data);
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

		if ($this->payments_model->update($payment_id,$data)) {
			redirect(base_url()."sisvent/admin/payments");
		}
		else{
			$data =array( 
				'payment' => $this->payments_model->getPayment($payment_id)
			);
			$this->session->set_flashdata("error","No se pudo actualizar la información");
			$this->load->view("sisvent/admin/payments/edit",$data);
			//redirect(base_url()."sisvent/admin/payments/edit/".$payment_id);
		}
		
	}

	public function delete($payment_id){
		
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$payment = $this->payments_model->getPayment($payment_id);

		$invoice = $this->invoices_model->getInvoice($payment->invoiceId);

		$data  = array(
			'payment' => $invoice->payment - $payment->payment,
			'state' => ($invoice->payment >= $invoice->total) ? 2 : (($invoice->total - $invoice->payment <= 0) ? 0 : 1),
		);

		$this->invoices_model->update($payment->invoiceId,$data);

		$this->payments_model->remove($payment_id);
		//redirect(base_url()."sisvent/admin/payments");
		echo base_url()."sisvent/admin/payments";
	}
	
}