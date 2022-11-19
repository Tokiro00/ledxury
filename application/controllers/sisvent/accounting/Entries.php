<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Entries extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("entry_model");
    }

    public function index()
	{
		$data  = array(
			'entries' => $this->entry_model->getEntries(), 
		);
		$this->load->view("sisvent/accounting/entries/list",$data);
		
	}

}