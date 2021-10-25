<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AccountClass extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("accountclass_model");
    }

	public function index()
	{
		$data  = array(
			'aclasses' => $this->accountclass_model->getClasses(), 
		);
		$this->load->view("sisvent/accounting/accountclass/list",$data);
		
	}

	public function add(){

		/*$data =array( 
			"roles" => $this->accountclass_model->getRoles()
		);*/
		$this->load->view("sisvent/accounting/accountclass/add");
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

			if ($this->accountclass_model->save($data)) {
				redirect(base_url()."sisvent/accounting/accountclass");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/accounting/accountclass/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($class_id){
		$data =array( 
			'aclass' => $this->accountclass_model->getClass($class_id)
		);
		//print_r($data);
		$this->load->view("sisvent/accounting/accountclass/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$class_id = $this->input->post("class_id");
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'name' => $name
			);

			if ($this->accountclass_model->update($class_id,$data)) {
				redirect(base_url()."sisvent/accounting/accountclass");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/accounting/accountclass/edit/".$class_id);
			}
		}
		else{
			$this->edit($class_id);
		}
	}

	public function delete($class_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->accountclass_model->remove($class_id);
		//redirect(base_url()."sisvent/accounting/accountclass");
		echo base_url()."sisvent/accounting/accountclass";
	}
	
}