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

		// Actualizar last_activity para el chat (máximo cada 60s)
		$lastPing = $this->CI->session->userdata('last_activity_ping');
		if (!$lastPing || time() - $lastPing > 60) {
			$uid = $this->CI->session->userdata('user_data')['uname'];
			if ($uid) {
				$this->CI->db->where('idUser', $uid)->update('users', array('last_activity' => date('Y-m-d H:i:s')));
				$this->CI->session->set_userdata('last_activity_ping', time());
			}
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