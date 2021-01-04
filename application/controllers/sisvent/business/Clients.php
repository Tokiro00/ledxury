<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("clients_model");
        $this->load->model("vendors_model");
    }

	public function index()
	{
		$data  = array(
			'clients' => $this->clients_model->getClients(), 
		);
		$this->load->view("sisvent/business/clients/list",$data);
		
	}

	public function add(){

		$data =array( 
			'vendors' => $this->vendors_model->getVendors()
		);
		$this->load->view("sisvent/business/clients/add", $data);
	}

	public function store(){
		$client_id = $this->input->post("client_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$vendor = $this->input->post("vendor");
		$rate = $this->input->post("rate");

		$this->form_validation->set_rules("client_id","Cédula/NIT","required|is_unique[clients.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");

		if ($this->form_validation->run()) {
			$data  = array(
				'idNum' => $client_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address,
				'vendor' => $vendor,
				'rate' => $rate
			);

			if ($this->clients_model->save($data)) {
				redirect(base_url()."sisvent/business/clients");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->add();
				//redirect(base_url()."sisvent/business/clients/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($client_id){
		$data =array( 
			'client' => $this->clients_model->getClient($client_id), 
			'vendors' => $this->vendors_model->getVendors()
		);
		//print_r($data);
		$this->load->view("sisvent/business/clients/edit",$data);
	}

	public function update(){

		$id = $this->input->post("id");
		$client_id = $this->input->post("client_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$vendor = $this->input->post("vendor");
		$rate = $this->input->post("rate");

		//$this->form_validation->set_rules("client_id","Cédula/NIT","required|is_unique[clients.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'idNum' => $client_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address,
				'vendor' => $vendor,
				'rate' => $rate
			);

			if ($this->clients_model->update($id,$data)) {
				redirect(base_url()."sisvent/business/clients");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				//redirect(base_url()."sisvent/business/clients/edit/".$client_id);
				$this->edit($id);
			}
		}
		else{
			$this->edit($id);
		}
	}

	public function delete($client_id){
		$this->clients_model->remove($client_id);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/business/clients";
	}
	
}