<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paymentmethods extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('metodos_pago');
        $this->load->model("payments_model");
    }

	public function index()
	{
		$data  = array(
			'methods' => $this->payments_model->getPaymentMethods(), 
		);
		$this->load->view("sisvent/admin/payment_methods/list",$data);
		
	}

	public function add(){

		/*$data =array( 
			"roles" => $this->payments_model->getRoles()
		);*/
		$this->load->view("sisvent/admin/payment_methods/add");
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'name' => $name
			);

			if ($this->payments_model->saveMethod($data)) {
				redirect(base_url()."sisvent/admin/paymentmethods");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/admin/paymentmethods/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($method_id){
		$data =array( 
			'method' => $this->payments_model->getPaymentMethod($method_id)
		);
		//print_r($data);
		$this->load->view("sisvent/admin/payment_methods/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$method_id = $this->input->post("method_id");
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'name' => $name
			);

			if ($this->payments_model->updateMethod($method_id,$data)) {
				redirect(base_url()."sisvent/admin/paymentmethods");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/admin/paymentmethods/edit/".$method_id);
			}
		}
		else{
			$this->edit($method_id);
		}
	}

	public function delete($method_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->payments_model->removeMethod($method_id);
		//redirect(base_url()."sisvent/admin/payment_methods");
		echo base_url()."sisvent/admin/paymentmethods";
	}
	
}
