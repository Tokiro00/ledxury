<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounts extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("account_model");
		$this->load->model("accountgroup_model");
    }

	public function index()
	{
		$data  = array(
			'accounts' => $this->account_model->getAccounts(), 
		);
		$this->load->view("sisvent/accounting/accounts/list",$data);
		
	}

	public function add(){

		$data =array( 
			'groups' => $this->accountgroup_model->getGroups()
		);
		$this->load->view("sisvent/accounting/accounts/add", $data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$account_id = $this->input->post("account_id");
		$group_id = $this->input->post("group_id");
		$name = $this->input->post("name");
		$description = $this->input->post("description");
		
		$this->form_validation->set_rules("account_id","Nombre","is_unique[accounts_accounts.accountID]|required");
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'accountID' => $account_id,
				'groupID' => $group_id,
				'accountName' => $name,
				'accountDescription' => $description
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

	public function edit($account_id){
		$data =array( 
			'account' => $this->account_model->getAccount($account_id),
			'groups' => $this->accountgroup_model->getGroups()
		);
		//print_r($data);
		$this->load->view("sisvent/accounting/accounts/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$account_id = $this->input->post("account_id");
		$group_id = $this->input->post("group_id");
		$description = $this->input->post("description");
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'groupID' => $group_id,
				'accountName' => $name,
				'accountDescription' => $description
			);

			if ($this->account_model->update($account_id,$data)) {
				redirect(base_url()."sisvent/accounting/accounts");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/accounting/accounts/edit/".$account_id);
			}
		}
		else{
			$this->edit($account_id);
		}
	}

	public function delete($account_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->account_model->remove($account_id);
		//redirect(base_url()."sisvent/accounting/accounts");
		echo base_url()."sisvent/accounting/accounts";
	}
	
}