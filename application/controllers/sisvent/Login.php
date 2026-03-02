<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        //$this->load->library('form_validation');
        $this->load->model('login_model'); 
        $this->load->model('users_model'); 
    }

	public function index()
	{
		/*$data  = array(
			'idUser' => "00000", 
			'name' => "Administrador",
			'email' => "",
			'password' => password_hash("4cC350r105m4m4dm1n", PASSWORD_BCRYPT),
			'role' => "1"
		);
		$this->users_model->save($data);*/
		if(is_logged_in())
		{
			redirect(base_url().'sisvent/dashboard');
		}else
		{
			$this->load->view('sisvent/login');
		}
	}

	public function validate()
	{
		$this->outh_model->CSRFVerify();
	
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		if($this->login_model->login(test_input($this->input->post('uid')), $this->input->post('ups')))
		{
			redirect(base_url().'sisvent/dashboard');
		}else
		{
			$this->session->set_flashdata("login_error","El usuario y/o contraseña son incorrectos");
			redirect(base_url().'sisvent/login');
		}
	}


	public function logout()
	{
		//$this->logs_model->logMessage("info","El usuario ".$this->session->userdata('user_data')['user_uname']." ha cerrado sesión del bingo ".get_set_name());
		$this->session->unset_userdata('user_data');
		$this->session->unset_userdata('site_lang');
		redirect(base_url());
	}
}
