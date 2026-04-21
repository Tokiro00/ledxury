<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Advertising extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1, 10]);
        $this->load->model("invoices_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("advertising_model");
    }

	public function expenses()
	{
		
		$data  = array(
			'vendors' => $this->vendors_model->getVendors(),
		);
		$this->load->view("sisvent/admin/advertising/expenses",$data);
		
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			date_default_timezone_set("America/Bogota");

		$vendor = $this->input->post("vendor");
		$expense = $this->input->post("expense");
		$date = $this->input->post("date");

		for ($i=0; $i < count($expense); $i++) { 
			$data  = array(
				'vendor' =>$vendor,
				'amount' =>$expense[$i],
				'date' =>date('Y-m-d H:i:s',strtotime($date[$i]))
			);
			$this->advertising_model->save($data);
		}
		$this->session->set_flashdata("success_msg","Gastos de publicidad guardados con éxito");
		$this->expenses();
	}
	
}