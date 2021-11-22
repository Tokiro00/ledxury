<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AccountGroup extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("accountgroup_model");
        $this->load->model("accountclass_model");
    }

	public function index()
	{
		$data  = array(
			'agroups' => $this->accountgroup_model->getGroups(), 
		);
		$this->load->view("sisvent/accounting/accountgroup/list",$data);
		
	}

	public function add(){

		$data =array( 
			'classes' => $this->accountclass_model->getClasses()
		);
		$this->load->view("sisvent/accounting/accountgroup/add", $data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$group_id = $this->input->post("group_id");
		$class_id = $this->input->post("class_id");
		$name = $this->input->post("name");
		$description = $this->input->post("description");
		
		$this->form_validation->set_rules("group_id","Nombre","is_unique[accounts_group.groupID]|required");
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'groupID' => $group_id,
				'classID' => $class_id,
				'groupName' => $name,
				'groupDescription' => $description
			);

			if ($this->accountgroup_model->save($data)) {
				redirect(base_url()."sisvent/accounting/accountgroup");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/accounting/accountgroup/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($group_id){
		$data =array( 
			'agroup' => $this->accountgroup_model->getGroup($group_id),
			'classes' => $this->accountclass_model->getClasses()
		);
		//print_r($data);
		$this->load->view("sisvent/accounting/accountgroup/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$group_id = $this->input->post("group_id");
		$class_id = $this->input->post("class_id");
		$description = $this->input->post("description");
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'classID' => $class_id,
				'groupName' => $name,
				'groupDescription' => $description
			);

			if ($this->accountgroup_model->update($group_id,$data)) {
				redirect(base_url()."sisvent/accounting/accountgroup");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/accounting/accountgroup/edit/".$group_id);
			}
		}
		else{
			$this->edit($group_id);
		}
	}

	public function delete($group_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->accountgroup_model->remove($group_id);
		//redirect(base_url()."sisvent/accounting/accountgroup");
		echo base_url()."sisvent/accounting/accountgroup";
	}
	
}