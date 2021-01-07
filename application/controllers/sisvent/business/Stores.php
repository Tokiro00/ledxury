<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stores extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("stores_model");
    }

	public function index()
	{
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
		);
		$this->load->view("sisvent/business/stores/list",$data);
		
	}

	public function add(){

		/*$data =array( 
			"roles" => $this->stores_model->getRoles()
		);*/
		$this->load->view("sisvent/business/stores/add");
	}

	public function store(){
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'name' => $name
			);

			if ($this->stores_model->save($data)) {
				redirect(base_url()."sisvent/business/stores");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/business/stores/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($store_id){
		$data =array( 
			'store' => $this->stores_model->getStore($store_id)
		);
		//print_r($data);
		$this->load->view("sisvent/business/stores/edit",$data);
	}

	public function update(){

		$store_id = $this->input->post("store_id");
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'name' => $name
			);

			if ($this->stores_model->update($store_id,$data)) {
				redirect(base_url()."sisvent/business/stores");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/business/stores/edit/".$store_id);
			}
		}
		else{
			$this->edit($store_id);
		}
	}

	public function delete($store_id){
		$this->stores_model->remove($store_id);
		//redirect(base_url()."sisvent/business/stores");
		echo base_url()."sisvent/business/stores";
	}
	
}