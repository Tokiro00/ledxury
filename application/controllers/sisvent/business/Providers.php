<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Providers extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("providers_model");
    }

	public function index()
	{
		$data  = array(
			'providers' => $this->providers_model->getProviders(), 
		);
		$this->load->view("sisvent/business/providers/list",$data);
		
	}

	public function add(){

		$this->load->view("sisvent/business/providers/add");
	}

	public function store(){
		$provider_id = $this->input->post("provider_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$vendor = $this->input->post("vendor");
		$rate = $this->input->post("rate");

		$this->form_validation->set_rules("provider_id","Cédula/NIT","required|is_unique[providers.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");

		if ($this->form_validation->run()) {
			$data  = array(
				'idNum' => $provider_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address
			);

			if ($this->providers_model->save($data)) {
				redirect(base_url()."sisvent/business/providers");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->add();
				//redirect(base_url()."sisvent/business/providers/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($provider_id){
		$data =array( 
			'provider' => $this->providers_model->getProvider($provider_id)
		);
		//print_r($data);
		$this->load->view("sisvent/business/providers/edit",$data);
	}

	public function update(){

		$id = $this->input->post("id");
		$provider_id = $this->input->post("provider_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		

		//$this->form_validation->set_rules("provider_id","Cédula/NIT","required|is_unique[providers.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'idNum' => $provider_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address
			);

			if ($this->providers_model->update($id,$data)) {
				redirect(base_url()."sisvent/business/providers");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				//redirect(base_url()."sisvent/business/providers/edit/".$provider_id);
				$this->edit($id);
			}
		}
		else{
			$this->edit($id);
		}
	}

	public function delete($provider_id){
		$this->providers_model->remove($provider_id);
		//redirect(base_url()."sisvent/business/providers");
		echo base_url()."sisvent/business/providers";
	}
	
}