<?php

class Backend_lib {

	private $CI;
	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function control($roles = [])
	{
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
}


?>