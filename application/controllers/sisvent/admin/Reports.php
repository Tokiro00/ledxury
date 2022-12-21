<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
    }

	public function index()
	{
		$data  = array(
			'vendors' => $this->vendors_model->getVendors(),
		);
		$this->load->view("sisvent/admin/reports/index",$data);
		
	}

	public function getUSerData()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$user_id = $this->input->post("user");

		$salesByMonth =  $this->invoices_model->getVendorSalesByMonth($user_id, date("Y"));
		$goal_sales = [30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 80000000, 80000000];
	    $month_names = ['Enero','Febrero','Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

	    $graph_data_g = array();
	    $arr = array();
	      //array_push($arr, ["type" => 'string', "label" => 'Mes']);
	      array_push($arr, 'Mes');
	      //array_push($arr, ["type" => 'number', "label" => 'Ventas Objetivo']);
	      array_push($arr, 'Ventas Objetivo');
	      array_push($arr, 'Ventas Reales');
	      array_push($graph_data_g,$arr);
	    foreach ($salesByMonth as $key => $value) {
	      $arr = array();
	      array_push($arr, $month_names[$value->month-1]);
	      array_push($arr, $goal_sales[$value->month-1]);
	      array_push($arr, (int)$value->total);
	      array_push($graph_data_g,$arr);
    	}

    	echo json_encode($graph_data_g);
	}

}