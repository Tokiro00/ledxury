<?php 

function is_logged_in() {
    // Get current CodeIgniter instance
    $CI =& get_instance();
        $CI->load->model("users_model");
    // We need to use $CI->session instead of $this->session
    $user = $CI->session->userdata('user_data');
    if (!isset($user)) { return false; } else 
    { 
    	$userLog = $CI->users_model->getAnyUser($CI->session->userdata('user_data')['uname']);
    	if (!isset($userLog)) { return false; } else { return true; }
    }
}
