<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("users_model");
    }

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$data  = array(
			'users' => $this->users_model->getUsers(), 
		);
		$this->load->view("sisvent/business/users/list",$data);
		
	}

	public function add(){

		$data =array( 
			"roles" => $this->users_model->getRoles()
		);
		$this->load->view("sisvent/business/users/add", $data);
	}

	public function store(){
		$user_id = $this->input->post("user_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$password = $this->input->post("password");
		$passconf = $this->input->post("passconf");
		$role = $this->input->post("role");

		$this->form_validation->set_rules("user_id","Identificación","required|is_unique[users.idUser]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		$this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[8]');
		//if(!empty($passconf))
		$this->form_validation->set_rules('passconf', 'Confirmar Contraseña', 'required|matches[password]');

		if ($this->form_validation->run()) {
			$data  = array(
				'idUser' => $user_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address,
				'password' => password_hash($password, PASSWORD_BCRYPT),
				'role' => $role
			);

			if ($this->users_model->save($data)) {
				redirect(base_url()."sisvent/business/users");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/business/users/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($user_id){
		$data =array( 
			'user' => $this->users_model->getUser($user_id), 
			'roles' => $this->users_model->getRoles()
		);
		//print_r($data);
		$this->load->view("sisvent/business/users/edit",$data);
	}

	public function update(){

		$user_id = $this->input->post("user_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$password = $this->input->post("password");
		$passconf = $this->input->post("passconf");
		$role = $this->input->post("role");

		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		
		if(!empty($password))
		{
			$this->form_validation->set_rules('password', 'Contraseña', 'min_length[8]');
			$this->form_validation->set_rules('passconf', 'Confirmar Contraseña', 'required|matches[password]');
		}
		if ($this->form_validation->run()) {
			if(!empty($password))
			{
				$data  = array(
					'name' => $name,
					'email' => $email,
					'phone' => $phone,
					'address' => $address,
					'password' => password_hash($password, PASSWORD_BCRYPT),
					'role' => $role
				);
			}
			else
			{
				$data  = array(
					'name' => $name,
					'email' => $email,
					'phone' => $phone,
					'address' => $address,
					'role' => $role
				);
			}

			if ($this->users_model->update($user_id,$data)) {
				redirect(base_url()."sisvent/business/users");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/business/users/edit/".$user_id);
			}
		}
		else{
			$this->edit($user_id);
		}
	}

	public function delete($user_id){
		$this->users_model->remove($user_id);
		redirect(base_url()."sisvent/business/users");
	}
	
}