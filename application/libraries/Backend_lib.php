<?php

class Backend_lib {

	private $CI;
	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function control($roles = [])
	{
		// Limpiar dato de sesión corrupto por PHP 8.2 (se puede eliminar después)
		if($this->CI->session->userdata('error') === 'El usuario y/o contraseña son incorrectos') {
			$this->CI->session->unset_userdata('error');
		}

		if(!is_logged_in())
		{
			//print_r("not logged in");
			redirect(base_url().'sisvent/login');
		}
		
		if(!empty($roles) && !in_array($this->CI->session->userdata('user_data')['role'],$roles))
		{
			//print_r("not permissions");
			redirect(base_url()."sisvent/dashboard");
		}
	}

	public function controlModule($module_key)
	{
		if(!is_logged_in())
		{
			redirect(base_url().'sisvent/login');
		}

		$role = $this->CI->session->userdata('user_data')['role'];
		// Superadmin siempre tiene acceso
		if ($role == 1) return;

		$permissions = $this->CI->session->userdata('permissions');
		if (empty($permissions) || !in_array($module_key, $permissions))
		{
			redirect(base_url()."sisvent/dashboard");
		}
	}
}


?>