<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
	$this->backend_lib->control();
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("stores_model");
    }

	public function index()
	{

		$stores = $this->stores_model->getStores();

		$salesbyvendor = $this->invoices_model->getStoreSalesByVendor(-1, date("Y"));

		$salesbystore = array();
		foreach ($stores as $str) {
			$idStore = $str->idStore;
			$storesales = array_filter($salesbyvendor, function($v) use ($idStore) {
	                    return $v->storeId == $idStore;
	                });

	                if(!empty($storesales)) {
	                	
	                	array_push($salesbystore, ["store" => $str->idStore, "storename" => $str->name, "salesbyvendor" => array_values($storesales)]);
			}
		}
                


		$data  = array(
			'vendors' => $this->vendors_model->getVendors(),
			//'salesbyvendor' => $this->invoices_model->getStoreSalesByVendor(-1, date("Y")),
			'salesbystore' => $salesbystore
		);
		$this->load->view("sisvent/admin/reports/index",$data);


		
	}

	function getUnivFunc($storeId){
	    return create_function('$a','return $a["storeId"] == "' . $storeId . '";');
	}

	public function getUSerData()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$user_id = $this->input->post("user");
		$year = $this->input->post("year");

		$salesByMonth =  $this->invoices_model->getVendorSalesByMonth($user_id, $year);
		$goal_sales = $this->invoices_model->getVendorSalesYearGoal($user_id, $year);//[30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 80000000, 80000000];
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

	    	$stores = $this->stores_model->getStores();

		$salesbyvendor = $this->invoices_model->getStoreSalesByVendor(-1, $year);

		$salesbystore = array();
		foreach ($stores as $str) {
			$idStore = $str->idStore;
			$storesales = array_filter($salesbyvendor, function($v) use ($idStore) {
	                    return $v->storeId == $idStore;
	                });

	                if(!empty($storesales)) {
	                	
	                	array_push($salesbystore, ["store" => $str->idStore, "storename" => $str->name, "salesbyvendor" => array_values($storesales)]);
			}
		}


	    	$data = array(
	    		'chart' => $graph_data_g,
	    		'table' => $table,
			'salesbystore' => $salesbystore
	    	);
	    	echo json_encode($data);
	}

	public function reportscallcenter()
	{
		
		$this->load->view("sisvent/admin/reports/callcenter");
		
	}

	public function createCheckDeliveryCSVData() {

   		//$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
		
		//echo "<h2>SIASS</h2>";
		//echo "<br>";
		//echo $this->db->last_query();
		//echo "<br>";
		//echo "<pre>";
        //echo (count($invoices));
        //echo "</pre><br>";

		//echo "<pre>";
        //echo print_r($invoices);
        //echo "</pre><br>";

		/*
		$line = "";
		foreach ($invoices as $val){
        	$line .=
        	$val->client_name." - ".
			$val->idInvoice." - ".
			$val->client_idNum." - ".
			$val->store_name." - ".
			$val->client_phone." - ".
			$val->client_cellphone."<br>";
        } 

        echo "<pre>";
        echo $line;
        echo "</pre>";*/
		
		$this->load->helper("file");
		
		$filename = 'clientes_check_recibido_'.date('Ymd').'.csv'; 
	   header("Content-Description: File Transfer"); 
	   header("Content-Disposition: attachment; filename=$filename"); 
	   header("Content-Type: application/csv; ");       

        $header = array(
		"Nombre",
		"Nit",
		"Telefono",
		"Celular");

		$file = fopen('php://output', 'w');
   		fputcsv($file, $header);

   		$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
        $ids = array_column($invoices, 'idInvoice');
        //if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        foreach ($invoices as $val){
        	$line = array(
        	$val->client_name,
			$val->client_idNum,
			$val->idInvoice,
			$val->client_phone,
			$val->client_cellphone);
			fputcsv($file,$line);

        } 

        if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        fclose($file); 
		exit;
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

    public function createCloseToExpireCSVData() {

   		/*//$invoices = $this->invoices_model->getInvoicesCloseToExpire();
   		$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
		
		echo "<h2>SIASS</h2>";
		echo "<br>";
		echo $this->db->last_query();
		echo "<br>";
		echo "<pre>";
        echo (count($invoices));
        echo "</pre><br>";

		//echo "<pre>";
        //echo print_r($invoices);
        //echo "</pre><br>";

        $ids = array_column($invoices, 'idInvoice');

        if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        echo "<pre>";
        echo print_r($ids);
        echo "</pre><br>";
		
		$line = "";
		foreach ($invoices as $val){
        	$line .=
        	$val->client_name." - ".
			$val->idInvoice." - ".
			$val->client_idNum." - ".
			$val->store_name." - ".
			$val->client_phone." - ".
			$val->client_cellphone."<br>";
        } 

        echo "<pre>";
        echo $line;
        echo "</pre>";*/
		
		$this->load->helper("file");
		
		$filename = 'clientes_fact_cerca_a_vencer_'.date('Ymd').'.csv'; 
	   header("Content-Description: File Transfer"); 
	   header("Content-Disposition: attachment; filename=$filename"); 
	   header("Content-Type: application/csv; ");       

        $header = array(
		"Nombre",
		"Nit",
		"Telefono",
		"Celular");

		$file = fopen('php://output', 'w');
   		fputcsv($file, $header);

   		$invoices = $this->invoices_model->getInvoicesCloseToExpire();
 		$ids = array_column($invoices, 'idInvoice');
        //if(!empty($ids)) $this->invoices_model->updateInvoicesCloseToExpire($ids);

        foreach ($invoices as $val){
        	$line = array(
        	$val->client_name,
			$val->client_idNum,
			$val->idInvoice,
			$val->client_phone,
			$val->client_cellphone);
			fputcsv($file,$line);

        } 

        fclose($file); 
		exit;
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

     public function createExpiredCSVData() {

   		/*//$invoices = $this->invoices_model->getInvoicesCloseToExpire();
   		$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
		
		echo "<h2>SIASS</h2>";
		echo "<br>";
		echo $this->db->last_query();
		echo "<br>";
		echo "<pre>";
        echo (count($invoices));
        echo "</pre><br>";

		//echo "<pre>";
        //echo print_r($invoices);
        //echo "</pre><br>";

        $ids = array_column($invoices, 'idInvoice');

        if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        echo "<pre>";
        echo print_r($ids);
        echo "</pre><br>";
		
		$line = "";
		foreach ($invoices as $val){
        	$line .=
        	$val->client_name." - ".
			$val->idInvoice." - ".
			$val->client_idNum." - ".
			$val->store_name." - ".
			$val->client_phone." - ".
			$val->client_cellphone."<br>";
        } 

        echo "<pre>";
        echo $line;
        echo "</pre>";*/
		
		$this->load->helper("file");
		
		$filename = 'clientes_fact_vencidas_'.date('Ymd').'.csv'; 
	   header("Content-Description: File Transfer"); 
	   header("Content-Disposition: attachment; filename=$filename"); 
	   header("Content-Type: application/csv; ");       

        $header = array(
		"Nombre",
		"Nit",
		"Telefono",
		"Celular");

		$file = fopen('php://output', 'w');
   		fputcsv($file, $header);

   		$invoices = $this->invoices_model->getInvoicesExpired();
 		$ids = array_column($invoices, 'idInvoice');
        //if(!empty($ids)) $this->invoices_model->updateInvoicesExpired($ids);

        foreach ($invoices as $val){
        	$line = array(
        	$val->client_name,
			$val->client_idNum,
			$val->idInvoice,
			$val->client_phone,
			$val->client_cellphone);
			fputcsv($file,$line);

        } 

        fclose($file); 
		exit;
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

}