<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounts extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("account_model");
    }

	public function index()
	{
		$data  = array(
			'accounts' => $this->account_model->getAccounts(), 
		);
		$this->load->view("sisvent/accounting/accounts/list",$data);
		
	}

	public function add(){

		/*$data =array( 
			"roles" => $this->account_model->getRoles()
		);*/
		$this->load->view("sisvent/accounting/accounts/add");
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

			if ($this->account_model->save($data)) {
				redirect(base_url()."sisvent/accounting/accounts");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/accounting/accounts/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($store_id){
		$data =array( 
			'store' => $this->account_model->getStore($store_id)
		);
		//print_r($data);
		$this->load->view("sisvent/accounting/accounts/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$store_id = $this->input->post("store_id");
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'name' => $name
			);

			if ($this->account_model->update($store_id,$data)) {
				redirect(base_url()."sisvent/accounting/accounts");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/accounting/accounts/edit/".$store_id);
			}
		}
		else{
			$this->edit($store_id);
		}
	}

	public function delete($store_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->account_model->remove($store_id);
		//redirect(base_url()."sisvent/accounting/accounts");
		echo base_url()."sisvent/accounting/accounts";
	}
	
}