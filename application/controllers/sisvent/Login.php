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
		// Mensaje contextual si llegaste de un logout forzado por tenant mismatch / admin-only
		$err = $this->input->get('error');
		if ($err === 'tenant_mismatch') {
			$this->session->set_flashdata('login_error', 'Tu cuenta no pertenece a este sitio.');
		} else if ($err === 'admin_only') {
			$this->session->set_flashdata('login_error', 'Solo el administrador de plataforma puede acceder a este panel.');
		}

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
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			redirect(base_url().'sisvent/login');
		}
		$this->outh_model->CSRFVerify();

		if($this->login_model->login(test_input($this->input->post('uid')), $this->input->post('ups')))
		{
			date_default_timezone_set("America/Bogota");
			$uid = $this->session->userdata('user_data')['uname'];
			$this->db->insert('user_activity_log', array(
				'user_id' => $uid,
				'action' => 'login',
				'ip_address' => $this->input->ip_address(),
				'created_at' => date('Y-m-d H:i:s'),
			));
			$this->db->where('idUser', $uid)->update('users', array('last_activity' => date('Y-m-d H:i:s')));
			$this->session->set_userdata('germam_should_greet', true);
			$this->session->unset_userdata('germam_greeted');
			redirect(base_url().'sisvent/dashboard');
		}else
		{
			$this->session->set_flashdata("login_error","El usuario y/o contraseña son incorrectos");
			redirect(base_url().'sisvent/login');
		}
	}


	public function logout()
	{
		date_default_timezone_set("America/Bogota");
		$ud = $this->session->userdata('user_data');
		if ($ud && isset($ud['uname'])) {
			$this->db->insert('user_activity_log', array(
				'user_id' => $ud['uname'],
				'action' => 'logout',
				'ip_address' => $this->input->ip_address(),
				'created_at' => date('Y-m-d H:i:s'),
			));
		}
		$this->session->unset_userdata('user_data');
		$this->session->unset_userdata('site_lang');
		redirect(base_url());
	}
}
