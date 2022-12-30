<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		//$this->backend_lib->control([1]);
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
		$goal_sales = $this->invoices_model->getVendorSalesYearGoal($user_id, date("Y"));//[30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 80000000, 80000000];
	    $month_names = ['Enero','Febrero','Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

	    if(empty($goal_sales))
        {
        	$goal_sales["m1"] = 30000000;
        	$goal_sales["m2"] = 31000000;
        	$goal_sales["m3"] = 30000000;
        	$goal_sales["m4"] = 30000000;
        	$goal_sales["m5"] = 30000000;
        	$goal_sales["m6"] = 30000000;
        	$goal_sales["m7"] = 30000000;
        	$goal_sales["m8"] = 30000000;
        	$goal_sales["m9"] = 30000000;
        	$goal_sales["m10"] = 30000000;
        	$goal_sales["m11"] = 80000000;
        	$goal_sales["m12"] = 80000000;
        }

        $month_row = '<td class="px-4 py-3 text-xs whitespace-normal">
                        Mes
                      </td>';
        $month_goal = '<td class="px-4 py-3 text-xs whitespace-normal">
                        Ventas Objetivo
                      </td>';
        $month_achieved = '<td class="px-4 py-3 text-xs whitespace-normal">
                        Ventas Reales
                      </td>';

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
	      array_push($arr, (int)$goal_sales["m".$value->month]);
	      array_push($arr, (int)$value->total);
	      array_push($graph_data_g,$arr);
	      $month_row .= '<td class="px-4 py-3">'.$month_names[$value->month-1].'</td>';
	      $month_goal .= '<td class="px-4 py-3">$'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal_sales["m".$value->month])), 2).'</td>';
	      $month_achieved .= '<td class="px-4 py-3">$'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $value->total)), 2).'</td>';
    	}

    	$table = '<tr class="text-gray-700">'.$month_row.'</tr><tr class="text-gray-700">'.$month_goal.'</tr><tr class="text-gray-700">'.$month_achieved.'</tr>';

    	$data = array(
    		'chart' => $graph_data_g,
    		'table' => $table
    	);
    	echo json_encode($data);
	}

}