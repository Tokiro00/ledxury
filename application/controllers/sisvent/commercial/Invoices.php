<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Invoices extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control();
        $this->load->model("payments_model");
        $this->load->model("budgets_model");
        $this->load->model("invoices_model");
        $this->load->model("stores_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("users_model");
    }

	public function index()
	{
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');

		$limit = 50;
		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$total = $this->invoices_model->getTotal($store, $vendor, $state, $client, $iva, $user->admin_store_arr);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'vendors' => $this->vendors_model->getVendors(),
			'clients' => $this->clients_model->getClients(),
			'total' => $total,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'page' => $page,
			'limit' => $limit,
			'invoices' => $this->invoices_model->getInvoices($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit)
		);
		$this->load->view("sisvent/commercial/invoices/list",$data);
		
	}

	public function printed(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$id = $this->input->post("id");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$res = $this->invoices_model->printed($id);
		echo json_encode($res);
	}

	public function edit($invoice_id){

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');

		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';

		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($invoice_id), 
			'details' => $this->invoices_model->getDetails($invoice_id),
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'page' => $page,
		);
		$this->load->view("sisvent/commercial/invoices/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			date_default_timezone_set("America/Bogota");

		$idInvoice = $this->input->post("id");
		$total = $this->input->post("total");
		$hasIva = $this->input->post("hasIva");
		$e_commerce = $this->input->post("e_commerce");
		$comments = $this->input->post("comments");
		
		$products = $this->input->post("refs");
		$budget_bases = $this->input->post("price_base");
		$budget_rates = $this->input->post("budget-rates");
		$if_id = $this->input->post("if_id");
		$quantities = $this->input->post("budget-quantities");
		$reviewed = $this->input->post("reviewed");
		$budget_subtotal = $this->input->post("budget-subtotal");
		$discount = $this->input->post("discount");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$iva = $this->input->get('i');

		/*for ($i=0; $i < count($products); $i++) { 

			$data  = array(
				//'quantity' =>$quantities[$i],
				//'unit' => $rates[$i],
				'reviewed' => in_array($i, $reviewed),
				'base' => $price_base[$i],
				//'total' =>$subtotal[$i]
			);
			print_r($data);
			echo "<br>";
		}*/
		/*foreach ( $this->input->post('reviewed',true) as $category)
		{
		print_r($category);
		echo "<br>";
		}*/
		//print_r($reviewed);
		if(!$page)
			$page = 1;
		if(!$pstore)
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pclient)
			$pclient = 'all';
		if(is_null($iva))
			$iva = 'all';

		$acum = $this->payments_model->getInvoicePayment($idInvoice);

		$data  = array(
			'total' => $total,
			'if_id' => $if_id,
			'discount' => $discount,
			'e_commerce' => $e_commerce == "on",
			'hasIva' => $hasIva ?? 0,
			'state' => ($acum->payment + $discount) >= $total ? 2 : ($acum->payment == 0 ? 0 : 1),
			'comments' => $comments,
		);


		if ($this->invoices_model->update($idInvoice,$data)) {
			$this->invoices_model->removeDetails($idInvoice);
			$this->_save_detail($products,$idInvoice,$quantities,$budget_rates,$budget_bases,$budget_subtotal,$reviewed);
			redirect(base_url()."sisvent/commercial/invoices".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva ));
		}
		else{
			$data  = array(
				'invoice' => $this->invoices_model->getInvoice($idInvoice), 
				'details' => $this->invoices_model->getDetails($idInvoice),
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pclient' => $pclient,
				'piva' => $iva,
				'page' => $page,
			);
			$this->session->set_flashdata("error","No se pudo guardar la información");
			$this->load->view("sisvent/commercial/invoices/edit",$data);
		}
			
	}

	function _update_detail($products,$idInvoice,$quantities,$rates,$price_base,$subtotal,$reviewed){
		
		for ($i=0; $i < count($products); $i++) { 

			$data  = array(
				'quantity' =>$quantities[$i],
				'unit' => $rates[$i],
				'reviewed' => in_array($i, $reviewed),
				'base' => $price_base[$i],
				'total' =>$subtotal[$i]
			);
			
			$this->invoices_model->update_detail($idInvoice,$products[$i],$data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	function _save_detail($products,$idInvoice,$quantities,$rates,$price_base,$subtotal,$reviewed){
		
		for ($i=0; $i < count($products); $i++) { 

			$data  = array(
				'invoiceId' =>$idInvoice,
				'productId' =>$products[$i],
				'quantity' =>$quantities[$i],
				'unit' => $rates[$i],
				'reviewed' => empty(in_array($i, $reviewed)) ? 0 : in_array($i, $reviewed),
				'base' => $price_base[$i],
				'total' =>$subtotal[$i]
			);
			
			$this->invoices_model->save_detail($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function refund($invoice_id){

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');

		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';

		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($invoice_id), 
			'details' => $this->invoices_model->getDetails($invoice_id),
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'page' => $page,
		);
		$this->load->view("sisvent/commercial/invoices/refund",$data);
	}

	public function saveRefund(){
		$idInvoice = $this->input->post("id");
		$total = $this->input->post("total");
		$comments = $this->input->post("comments");
		
		$store = $this->input->post("store");
		$products = $this->input->post("refs");
		$budget_bases = $this->input->post("price_base");
		$budget_rates = $this->input->post("budget-rates");
		$quantities = $this->input->post("budget-quantities");
		$n_quantities = $this->input->post("budget-n-quantities");
		$budget_subtotal = $this->input->post("budget-subtotal");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$iva = $this->input->get('i');

		if(!$page)
			$page = 1;
		if(!$pstore)
			$pstore = 'all';
		if(!$pvendor)
			$pvendor = 'all';
		if(is_null($pstate))
			$pstate = 'all';
		if(!$pclient)
			$pclient = 'all';
		if(is_null($iva))
			$iva = 'all';

		$acum = $this->payments_model->getInvoicePayment($idInvoice);
		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'total' => $invoice->total - $total,
			'state' => ($invoice->payment + $invoice->discount) >= $invoice->total - $total ? 2 : ($invoice->payment == 0 ? 0 : 1),
		);

		if ($this->invoices_model->update($idInvoice,$data)) {

			$data  = array(
				'invoiceId' => $idInvoice,
				'total' => $total,
				'date' => date('Y-m-d H:i:s'),
				'comments' => $comments,
			);
			$this->invoices_model->saveRefund($data);
			$idRefund = $this->budgets_model->lastID();
			$this->_update_detail_after_refund($products,$store,$idRefund,$idInvoice,$quantities,$n_quantities,$budget_rates,$budget_bases,$budget_subtotal);
			redirect(base_url()."sisvent/commercial/invoices".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva ));
		}
		else{
			$data  = array(
				'invoice' => $this->invoices_model->getInvoice($idInvoice), 
				'details' => $this->invoices_model->getDetails($idInvoice),
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pclient' => $pclient,
				'piva' => $iva,
				'page' => $page,
			);
			$this->session->set_flashdata("error","No se pudo guardar la información");
			$this->load->view("sisvent/commercial/invoices/refund",$data);
		}
			
	}

	function _update_detail_after_refund($products,$store,$idRefund,$idInvoice,$quantities,$n_quantities,$rates,$price_base,$subtotal){
		
		for ($i=0; $i < count($products); $i++) { 
			if($n_quantities[$i] != 0){
				$data  = array(
					'quantity' =>$quantities[$i] - $n_quantities[$i],
					'total' => ($quantities[$i] - $n_quantities[$i]) * $rates[$i]
				);
				
				$this->invoices_model->update_detail($idInvoice,$products[$i],$data);

				$productoActual = $this->inventory_model->getStoreProduct($store,$products[$i]);
				
				$data = array(
					'stock' => $productoActual->stock + $n_quantities[$i]
				);
				$this->inventory_model->update($store,$products[$i],$data);

				$data  = array(
					'refundId' =>$idRefund,
					'productId' =>$products[$i],
					'quantity' =>$n_quantities[$i],
					'unit' => $rates[$i],
					'base' => $price_base[$i],
					'total' =>$subtotal[$i]
				);
				$this->invoices_model->save_refund_detail($data);
				//$this->updateProduct($products[$i],$quantities[$i]);
			}
		}
	}
	
	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("id");
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'details' => $this->invoices_model->getDetails($idInvoice),
		);
		$this->load->view("sisvent/commercial/invoices/view",$data);
	}

	public function search($term){
		
		$term = str_replace("%20", " ", $term);

		/*$data  = array(
			'term' => $term, 
		);		

		print_r($data);*/
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$iva = $this->input->get('i');

		$limit = 50;
		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$vendor)
			$vendor = 'all';
		if(is_null($state))
			$state = 'all';
		if(!$client)
			$client = 'all';
		if(is_null($iva))
			$iva = 'all';

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$total = $this->invoices_model->getTotalSearch($term,$store, $vendor, $state, $client, $iva, $user->admin_store_arr);
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'vendors' => $this->vendors_model->getVendors(),
			'clients' => $this->clients_model->getClients(),
			'total' => $total,
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'page' => $pag,
			'limit' => $limit,
			'invoices' => $this->invoices_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit)
		);
		$this->load->view("sisvent/commercial/invoices/list",$data);
	}
	
	public function delete($idInvoice){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			
		$invoice = $this->invoices_model->getInvoice($idInvoice);

		if($invoice->state == 0)
		{
			$data  = array(
				'state' => 0,
			);

			$this->budgets_model->update($invoice->budgetId,$data);
		}

		$this->invoices_model->remove($idInvoice);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/commercial/invoices";
	}

	public function payment(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("id");
		$params = $this->input->post("params");
		
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'vendors' => $this->vendors_model->getVendors(), 
			'methods' => $this->payments_model->getPaymentMethods(), 
			'params' => $params
		);
		$this->load->view("sisvent/commercial/invoices/payment",$data);
	}

	public function makepayment(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("id");
		$method = $this->input->post("method");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comment");
		$date = $this->input->post("date");
		if(!$date)
			$date = date('Y-m-d H:i:s');
		$params = $this->input->post("params");

		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'invoiceId' =>$idInvoice,
			'clientId' =>$invoice->clientId,
			'vendorId' =>$invoice->vendorId,
			'paymentMethod' =>$method,
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			'payment' =>$payment,
			'comments' =>$comment
		);

		$this->payments_model->save($data);

		$acum = $this->payments_model->getInvoicePayment($idInvoice);

		$data  = array(
			'payment' => $acum->payment,
			'state' => $acum->payment + $invoice->discount >= $invoice->total ? 2 : 1,
		);

		$this->invoices_model->update($idInvoice,$data);

		echo base_url()."sisvent/commercial/invoices".$params;
	}

	public function export(){
		$data  = array(
			'stores' => $this->stores_model->getStores(),  
		);
		$this->load->view("sisvent/commercial/invoices/export",$data);
	}

	public function createExcelFac() {

		$this->load->helper("file");
		
		$store = $this->input->post("store");
		$from = $this->input->post("from");
		$until = $this->input->post("until");

		$from = str_replace("%20", " ", $from);
		$until = str_replace("%20", " ", $until);
		
		/*$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all', -1, 50, $from, $until);

		echo ($from)."<br>";
		echo strtotime($from)."<br>";
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */

		$dat = uniqid('MAMFacs', true);

		$fileName = 'FAC-'.$dat.'.xlsx';  
		$fileNameDetails = 'LFA-'.$dat.'.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all',  'all', '', -1, 50, $from, $until);
		$spreadsheet = new Spreadsheet();
		$spreadsheetDetails = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetDetails = $spreadsheetDetails->getActiveSheet();
       	$sheet->setCellValue('A1', 'Tipo de documento');
        $sheet->setCellValue('B1', 'Número de documento');
        $sheet->setCellValue('C1', 'Referencia');
        $sheet->setCellValue('D1', 'Fecha');
        $sheet->setCellValue('E1', 'Estado');
        $sheet->setCellValue('F1', 'Almacén');       

		$sheet->setCellValue('G1', 'Agente');

        $sheet->setCellValue('I1', 'Código de cliente');       
        $sheet->setCellValue('J1', 'Nombre del cliente');       
        $sheet->setCellValue('K1', 'Domicilio del cliente');       
        $sheet->setCellValue('O1', 'N.I.F.');       
        $sheet->setCellValue('P1', 'Tipo de IVA');       
        $sheet->setCellValue('R1', 'Teléfono del cliente');       
        $sheet->setCellValue('S1', 'Total');       

        //$sheet->setCellValue('Q1', 'Almacén');       
        $sheet->setCellValue('BK1', 'Total');       
        $sheet->setCellValue('BL1', 'Forma de pago');       
        $sheet->setCellValue('BO1', 'Comentarios'); 

        $sheetDetails->setCellValue('A1', 'Tipo de documento');
        $sheetDetails->setCellValue('B1', 'Número de documento');
        $sheetDetails->setCellValue('C1', 'Posición de la línea');
        $sheetDetails->setCellValue('D1', 'Artículo');
        $sheetDetails->setCellValue('E1', 'Descripción');       
        $sheetDetails->setCellValue('F1', 'Cantidad');       
        $sheetDetails->setCellValue('J1', 'Precio del artículo');       
        $sheetDetails->setCellValue('L1', 'Tipo de IVA');       
        $sheetDetails->setCellValue('K1', 'Total');       
        $sheetDetails->setCellValue('P1', 'Costo del artículo');       

        $rows = 2;
       	$rowsDetails = 2;
        foreach ($invoices as $val){
        	//echo $val->idInvoice."  ".$val->date." ".$val->clientFId." ".$val->client_name."<br>";
       		$rd = 2;
            $sheet->setCellValue('A' . $rows, date("Y")-(($val->storeId == 3) ? 2020 : 2018));
            $sheet->setCellValue('B' . $rows, $val->idInvoice);
            $sheet->setCellValue('C' . $rows, substr($val->comments, 0, 50));
            $sheet->setCellValue('D' . $rows, date('Y-m-d H:i:s')/*$val->date*/);
	        $sheet->setCellValue('E' . $rows, '0');
	        switch ($val->storeId) {
	        	case 1:
            		$sheet->setCellValue('F' . $rows, 'GEN');
	        		break;
	        	case 3:
            		$sheet->setCellValue('F' . $rows, 0);
	        		break;
	        	default:
	        		# code...
            		$sheet->setCellValue('F' . $rows, $val->storeId);
	        		break;
	        }
	    	$sheet->setCellValue('G' . $rows, $val->vendorFId);

            $sheet->setCellValue('I' . $rows, $val->clientFId);
            $sheet->setCellValue('J' . $rows, $val->client_name);
            //$sheet->setCellValue('BS' . /*$rows, $val->state*/"0");
            $sheet->setCellValue('K' . $rows, $val->client_address);
            $sheet->setCellValue('O' . $rows, $val->client_idNum);
            $sheet->setCellValue('P' . $rows, $val->hasIva ? "0" : "1");
	        $sheet->setCellValue('R' . $rows, empty($val->client_phone) ? $val->client_phone : $val->client_cellphone);       
	        $sheet->setCellValue('S' . $rows, $val->total);       
	        $sheet->setCellValue('BK' . $rows, $val->total);       
	        $sheet->setCellValue('BL' . $rows, '0'); 
	        //$sheet->setCellValue('BO' . $rows, $val->comments); 

	        $details = $this->invoices_model->getDetails($val->idInvoice);
	        //echo $this->db->last_query()."<br>";
	        //echo sizeof($details)."<br>";
	        //foreach ($details as $det){
	        for($i = 0; $i < sizeof($details); $i++){
	        	$det = $details[$i];
	        	//echo "   ".$i;
        		//echo "      ". $det->productId."  ".$det->quantity." ".$det->unit." ".$det->subtotal."<br>";
	            $sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-(($val->storeId == 3) ? 2020 : 2018));
	            $sheetDetails->setCellValue('B' . $rowsDetails, $val->idInvoice);
	            $sheetDetails->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetDetails->setCellValue('D' . $rowsDetails, $det->productId);
	            $sheetDetails->setCellValue('E' . $rowsDetails, $det->description);
	            $sheetDetails->setCellValue('F' . $rowsDetails, $det->quantity);
	            $sheetDetails->setCellValue('J' . $rowsDetails, $det->unit);
	            ////$sheetDetails->setCellValue('N' . $rowsDetails, $det->hasIva);
	            $sheetDetails->setCellValue('K' . $rowsDetails, $det->subtotal);
		        $sheetDetails->setCellValue('P' . $rowsDetails, $det->base);

	            $rowsDetails++;
	            $rd++;
	        } 

            $rows++;
        } 

        if (!is_dir('./public/fac/')) {
			//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
        	mkdir('./public/fac/', 0777, true);
    	}
    	
    	delete_files('./public/fac/');

        $writer = new Xlsx($spreadsheet);
		$writer->save("public/fac/".$fileName);

		$writerDetails = new Xlsx($spreadsheetDetails);
		$writerDetails->save("public/fac/".$fileNameDetails);

		$data  = array(
				'fac' => "public/fac/".$fileName,
				'facdet' => "public/fac/".$fileNameDetails,
			);

		echo json_encode($data);
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

    public function createExcelFacDate($store, $from = "", $until = "") {

		$this->load->helper("file");

		$from = str_replace("%20", " ", $from);
		$until = str_replace("%20", " ", $until);
		
		/*$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all', -1, 50, $from, $until);

		echo ($from)."<br>";
		echo strtotime($from)."<br>";
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */

		$dat = uniqid('MAMFacs', true);

		$fileName = 'FAC-'.$dat.'.xlsx';  
		$fileNameDetails = 'LFA-'.$dat.'.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all',  'all', '', -1, 50, $from, $until);
		$spreadsheet = new Spreadsheet();
		$spreadsheetDetails = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetDetails = $spreadsheetDetails->getActiveSheet();
       	$sheet->setCellValue('A1', 'Tipo de documento');
        $sheet->setCellValue('B1', 'Número de documento');
        $sheet->setCellValue('C1', 'Referencia');
        $sheet->setCellValue('D1', 'Fecha');
        $sheet->setCellValue('E1', 'Estado');
        $sheet->setCellValue('F1', 'Almacén');       

		$sheet->setCellValue('G1', 'Agente');

        $sheet->setCellValue('I1', 'Código de cliente');       
        $sheet->setCellValue('J1', 'Nombre del cliente');       
        $sheet->setCellValue('K1', 'Domicilio del cliente');       
        $sheet->setCellValue('O1', 'N.I.F.');       
        $sheet->setCellValue('P1', 'Tipo de IVA');       
        $sheet->setCellValue('R1', 'Teléfono del cliente');       
        $sheet->setCellValue('S1', 'Total');       

        //$sheet->setCellValue('Q1', 'Almacén');       
        $sheet->setCellValue('BK1', 'Total');       
        $sheet->setCellValue('BL1', 'Forma de pago');       
        $sheet->setCellValue('BO1', 'Comentarios'); 

        $sheetDetails->setCellValue('A1', 'Tipo de documento');
        $sheetDetails->setCellValue('B1', 'Número de documento');
        $sheetDetails->setCellValue('C1', 'Posición de la línea');
        $sheetDetails->setCellValue('D1', 'Artículo');
        $sheetDetails->setCellValue('E1', 'Descripción');       
        $sheetDetails->setCellValue('F1', 'Cantidad');       
        $sheetDetails->setCellValue('J1', 'Precio del artículo');       
        $sheetDetails->setCellValue('L1', 'Tipo de IVA');       
        $sheetDetails->setCellValue('K1', 'Total');       
        $sheetDetails->setCellValue('P1', 'Costo del artículo');       

        $rows = 2;
       	$rowsDetails = 2;
        foreach ($invoices as $val){
        	//echo $val->idInvoice."  ".$val->date." ".$val->clientFId." ".$val->client_name."<br>";
       		$rd = 2;
            $sheet->setCellValue('A' . $rows, date("Y")-(($val->storeId == 3) ? 2020 : 2018));
            $sheet->setCellValue('B' . $rows, $val->idInvoice);
            $sheet->setCellValue('C' . $rows, substr($val->comments, 0, 50));
            $sheet->setCellValue('D' . $rows, date('Y-m-d H:i:s')/*$val->date*/);
	        $sheet->setCellValue('E' . $rows, '0');
	        switch ($val->storeId) {
	        	case 1:
            		$sheet->setCellValue('F' . $rows, 'GEN');
	        		break;
	        	case 3:
            		$sheet->setCellValue('F' . $rows, 0);
	        		break;
	        	default:
	        		# code...
            		$sheet->setCellValue('F' . $rows, $val->storeId);
	        		break;
	        }
	    	$sheet->setCellValue('G' . $rows, $val->vendorFId);

            $sheet->setCellValue('I' . $rows, $val->clientFId);
            $sheet->setCellValue('J' . $rows, $val->client_name);
            //$sheet->setCellValue('BS' . /*$rows, $val->state*/"0");
            $sheet->setCellValue('K' . $rows, $val->client_address);
            $sheet->setCellValue('O' . $rows, $val->client_idNum);
            $sheet->setCellValue('P' . $rows, $val->hasIva ? "0" : "1");
	        $sheet->setCellValue('R' . $rows, empty($val->client_phone) ? $val->client_phone : $val->client_cellphone);       
	        $sheet->setCellValue('S' . $rows, $val->total);       
	        $sheet->setCellValue('BK' . $rows, $val->total);       
	        $sheet->setCellValue('BL' . $rows, '0'); 
	        //$sheet->setCellValue('BO' . $rows, $val->comments); 

	        $details = $this->invoices_model->getDetails($val->idInvoice);
	        //echo $this->db->last_query()."<br>";
	        //echo sizeof($details)."<br>";
	        //foreach ($details as $det){
	        for($i = 0; $i < sizeof($details); $i++){
	        	$det = $details[$i];
	        	//echo "   ".$i;
        		//echo "      ". $det->productId."  ".$det->quantity." ".$det->unit." ".$det->subtotal."<br>";
	            $sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-(($val->storeId == 3) ? 2020 : 2018));
	            $sheetDetails->setCellValue('B' . $rowsDetails, $val->idInvoice);
	            $sheetDetails->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetDetails->setCellValue('D' . $rowsDetails, $det->productId);
	            $sheetDetails->setCellValue('E' . $rowsDetails, $det->description);
	            $sheetDetails->setCellValue('F' . $rowsDetails, $det->quantity);
	            $sheetDetails->setCellValue('J' . $rowsDetails, $det->unit);
	            ////$sheetDetails->setCellValue('N' . $rowsDetails, $det->hasIva);
	            $sheetDetails->setCellValue('K' . $rowsDetails, $det->subtotal);
		        $sheetDetails->setCellValue('P' . $rowsDetails, $det->base);

	            $rowsDetails++;
	            $rd++;
	        } 

            $rows++;
        } 

        if (!is_dir('./public/fac/')) {
			//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
        	mkdir('./public/fac/', 0777, true);
    	}
    	
    	delete_files('./public/fac/');

        $writer = new Xlsx($spreadsheet);
		$writer->save("public/fac/".$fileName);

		$writerDetails = new Xlsx($spreadsheetDetails);
		$writerDetails->save("public/fac/".$fileNameDetails);

		header("Content-Type: application/vnd.ms-excel");
        redirect(base_url()."/public/fac/".$fileName); 
    }

	public function createExcel($store, $from = "", $until = "") {
		$from = str_replace("%20", " ", $from);
		$until = str_replace("%20", " ", $until);
		
		/*$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all', -1, 50, $from, $until);

		echo ($from)."<br>";
		echo strtotime($from)."<br>";
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */

		
		$fileName = 'PRE.xlsx';  
		$fileNameDetails = 'LPS.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all',  'all', '', -1, 50, $from, $until);
		$spreadsheet = new Spreadsheet();
		$spreadsheetDetails = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetDetails = $spreadsheetDetails->getActiveSheet();
       	$sheet->setCellValue('A1', 'Tipo de documento');
        $sheet->setCellValue('B1', 'Número de documento');
        $sheet->setCellValue('C1', 'Referencia');
        $sheet->setCellValue('D1', 'Fecha');
		$sheet->setCellValue('E1', 'Agente');
        $sheet->setCellValue('F1', 'Código del proveedor');       
        $sheet->setCellValue('G1', 'Código de cliente');       
        $sheet->setCellValue('H1', 'Nombre del cliente');       
        $sheet->setCellValue('I1', 'Domicilio del cliente');       
        $sheet->setCellValue('N1', 'Tipo de IVA');       
        $sheet->setCellValue('P1', 'Teléfono del cliente');       
        $sheet->setCellValue('Q1', 'Almacén');       
        $sheet->setCellValue('BJ1', 'Total');       
        $sheet->setCellValue('BK1', 'Forma de pago');       
        $sheet->setCellValue('BS1', 'Estado del presupuesto');       
        $sheet->setCellValue('BZ1', 'Comentarios'); 

        $sheetDetails->setCellValue('A1', 'Tipo de documento');
        $sheetDetails->setCellValue('B1', 'Número de documento');
        $sheetDetails->setCellValue('C1', 'Posición de la línea');
        $sheetDetails->setCellValue('D1', 'Artículo');
        $sheetDetails->setCellValue('E1', 'Descripción');       
        $sheetDetails->setCellValue('F1', 'Cantidad');       
        $sheetDetails->setCellValue('J1', 'Precio del artículo');       
        $sheetDetails->setCellValue('S1', 'Tipo de IVA');       
        $sheetDetails->setCellValue('K1', 'Total');       
        $sheetDetails->setCellValue('V1', 'Costo del artículo');       

        $rows = 2;
       	$rowsDetails = 2;
        foreach ($invoices as $val){
        	echo $val->idInvoice."  ".$val->date." ".$val->clientFId." ".$val->client_name."<br>";
       		$rd = 2;
            $sheet->setCellValue('A' . $rows, '1');
            $sheet->setCellValue('B' . $rows, $val->idInvoice);
            //$sheet->setCellValue('C' . $rows, $val->store_name);
            $sheet->setCellValue('D' . $rows, $val->date);
	    	$sheet->setCellValue('E' . $rows, $val->vendorFId);
            $sheet->setCellValue('G' . $rows, $val->clientFId);
            $sheet->setCellValue('H' . $rows, $val->client_name);
            $sheet->setCellValue('BS' . $rows, $val->state);
            $sheet->setCellValue('I' . $rows, $val->client_address);
            $sheet->setCellValue('N' . $rows, $val->hasIva ? "0" : "1");
	        $sheet->setCellValue('P' . $rows, empty($val->client_phone) ? $val->client_phone : $val->client_cellphone);       
            //$sheet->setCellValue('Q' . $rows, $val->storeId);
	        $sheet->setCellValue('BJ' . $rows, $val->total);       
	        $sheet->setCellValue('BK' . $rows, '0'); 
	        $sheet->setCellValue('BS' . $rows, '0');       
	        $sheet->setCellValue('BZ' . $rows, $val->comments); 

	        $details = $this->invoices_model->getDetails($val->idInvoice);
	        //echo $this->db->last_query()."<br>";
	        echo sizeof($details)."<br>";
	        //foreach ($details as $det){
	        for($i = 0; $i < sizeof($details); $i++){
	        	$det = $details[$i];
	        	//echo "   ".$i;
        		//echo "      ". $det->productId."  ".$det->quantity." ".$det->unit." ".$det->subtotal."<br>";
	            $sheetDetails->setCellValue('A' . $rowsDetails, '1');
	            $sheetDetails->setCellValue('B' . $rowsDetails, $val->idInvoice);
	            $sheetDetails->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetDetails->setCellValue('D' . $rowsDetails, $det->productId);
	            $sheetDetails->setCellValue('E' . $rowsDetails, $det->description);
	            $sheetDetails->setCellValue('F' . $rowsDetails, $det->quantity);
	            $sheetDetails->setCellValue('J' . $rowsDetails, $det->unit);
	            ////$sheetDetails->setCellValue('N' . $rowsDetails, $det->hasIva);
	            $sheetDetails->setCellValue('K' . $rowsDetails, $det->subtotal);
		        $sheetDetails->setCellValue('V' . $rowsDetails, $det->base);

	            $rowsDetails++;
	            $rd++;
	        } 

            $rows++;
        } 
        $writer = new Xlsx($spreadsheet);
		$writer->save("public/".$fileName);

		$writerDetails = new Xlsx($spreadsheetDetails);
		$writerDetails->save("public/".$fileNameDetails);


		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

    public function sendMail()
    {

    	$config = array();
		$config['protocol'] = 'smtp';
		$config['smtp_host'] = 'ssl://smtp.googlemail.com';
		//$config['smtp_crypto'] = 'ssl';
		$config['smtp_port'] = 465;
		$config['smtp_user'] = 'cdga777@gmail.com';
		$config['smtp_pass'] = '';
		$config['mailtype'] = 'html';
		//$config['charset'] = 'iso-8859-1';
		$config['charset'] = 'utf-8';
		//$config['wordwrap'] = 'TRUE';
		$config['newline'] = "\r\n";
		//$config['crlf'] = "\r\n";
		$this->email->initialize($config);

    	$this->email->from('cdga777@gmail.com', 'Pollo');
	    $this->email->to('cdga777@gmail.com');
	    $this->email->subject('Send Email Codeigniter '.date('Y-m-d H:i:s'));
	    $this->email->message('The email send using codeigniter library '.date('Y-m-d H:i:s'));
	    $res = $this->email->send();
	    echo $this->email->print_debugger();
	    echo "<br> -- RES -- <br>";
	    echo $res;
    }
}