<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AccountClass extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("accountclass_model");
        $this->load->model("stores_model");
    }

	public function index()
	{
		$data  = array(
			'aclasses' => $this->accountclass_model->getClasses(), 
		);
		$this->load->view("sisvent/accounting/accountclass/list",$data);
		
	}

	public function add(){

		$data =array( 
			"stores" => $this->stores_model->getStores(),
		);
		$this->load->view("sisvent/accounting/accountclass/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$class_id = $this->input->post("class_id");
		$name = $this->input->post("name");
		$description = $this->input->post("description");
		$store = $this->input->post("store");
		
		$this->form_validation->set_rules("class_id","Nombre","is_unique[accounts_class.classID]|required");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("store","Almacén","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'classID' => $class_id,
				'className' => $name,
				'classDescription' => $description,
				'store' => $store
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
			"stores" => $this->stores_model->getStores(),
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
		$description = $this->input->post("description");
		$store = $this->input->post("store");
		
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("store","Almacén","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'className' => $name,
				'classDescription' => $description,
				'store' => $store
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