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
    }

	public function index()
	{
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
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

		$total = $this->invoices_model->getTotal($store, $vendor, $state, $client);
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
			'page' => $page,
			'limit' => $limit,
			'invoices' => $this->invoices_model->getInvoices($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $page, $limit)
		);
		$this->load->view("sisvent/commercial/invoices/list",$data);
		
	}

	public function edit($invoice_id){

		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');

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

		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($invoice_id), 
			'details' => $this->invoices_model->getDetails($invoice_id),
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'page' => $page,
		);
		$this->load->view("sisvent/commercial/invoices/edit",$data);
	}

	public function update(){
		$idInvoice = $this->input->post("id");
		$total = $this->input->post("total");
		$comments = $this->input->post("comments");
		
		$products = $this->input->post("refs");
		$budget_bases = $this->input->post("price_base");
		$budget_rates = $this->input->post("budget-rates");
		$quantities = $this->input->post("budget-quantities");
		$budget_subtotal = $this->input->post("budget-subtotal");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');

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

		$data  = array(
			'total' => $total,
			'comments' => $comments,
		);

		if ($this->invoices_model->update($idInvoice,$data)) {
			$this->_update_detail($products,$idInvoice,$quantities,$budget_rates,$budget_bases,$budget_subtotal);
			redirect(base_url()."sisvent/commercial/invoices".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient ));
		}
		else{
			$data  = array(
				'invoice' => $this->invoices_model->getInvoice($idInvoice), 
				'details' => $this->invoices_model->getDetails($idInvoice),
				'pstore' => $pstore,
				'pvendor' => $pvendor,
				'pstate' => $pstate,
				'pclient' => $pclient,
				'page' => $page,
			);
			$this->session->set_flashdata("error","No se pudo guardar la información");
			$this->load->view("sisvent/commercial/invoices/edit",$data);
		}
			
	}

	function _update_detail($products,$idInvoice,$quantities,$rates,$price_base,$subtotal){
		
		for ($i=0; $i < count($products); $i++) { 

			$data  = array(
				//'quantity' =>$quantities[$i],
				//'unit' => $rates[$i],
				'base' => $price_base[$i],
				//'total' =>$subtotal[$i]
			);
			
			$this->invoices_model->update_detail($idInvoice,$products[$i],$data);
			//$this->updateProduct($products[$i],$quantities[$i]);
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
		
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
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

		$total = $this->invoices_model->getTotalSearch($term,$store, $vendor, $state, $client);
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
			'page' => $pag,
			'limit' => $limit,
			'invoices' => $this->invoices_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $page, $limit)
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
		$date = $this->input->post("date");
		
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'vendors' => $this->vendors_model->getVendors(), 
			'methods' => $this->payments_model->getPaymentMethods(), 
			'date' => date('Y-m-d H:i:s',strtotime($date)),
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
			'state' => $acum->payment >= $invoice->total ? 2 : 1,
		);

		$this->invoices_model->update($idInvoice,$data);

		echo base_url()."sisvent/commercial/invoices";
	}

	public function createExcel($from = "", $until = "") {
		/*$invoices = $this->invoices_model->getInvoices(true,  'all',  'all',  'all',  'all', -1, 50, $from, $until);
		
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */

		$fileName = 'PRE.xls';  
		$fileNameDetails = 'LPS.xls';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$invoices = $this->invoices_model->getInvoices(true,  'all',  'all',  'all',  'all', -1, 50, $from, $until);
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
        $sheet->setCellValue('BI1', 'Total');       
        $sheet->setCellValue('BJ1', 'Forma de pago');       
        $sheet->setCellValue('BR1', 'Estado del presupuesto');       
        $sheet->setCellValue('BY1', 'Comentarios'); 

        $sheetDetails->setCellValue('A1', 'Tipo de documento');
        $sheetDetails->setCellValue('B1', 'Número de documento');
        $sheetDetails->setCellValue('C1', 'Posición de la línea');
        $sheetDetails->setCellValue('D1', 'Artículo');
        $sheetDetails->setCellValue('F1', 'Descripción');       
        $sheetDetails->setCellValue('G1', 'Cantidad');       
        $sheetDetails->setCellValue('K1', 'Precio de la línea');       
        $sheetDetails->setCellValue('N1', 'Tipo de IVA');       
        $sheetDetails->setCellValue('L1', 'Total');       
        $sheetDetails->setCellValue('Q1', 'Costo del artículo');       

        $rows = 2;
       	$rowsDetails = 2;
        foreach ($invoices as $val){
       		$rd = 2;
            $sheet->setCellValue('A' . $rows, '1');
            $sheet->setCellValue('B' . $rows, $val->idInvoice);
            //$sheet->setCellValue('C' . $rows, $val->store_name);
            $sheet->setCellValue('D' . $rows, $val->date);
	    	$sheet->setCellValue('E' . $rows, $val->vendorId);
            $sheet->setCellValue('F' . $rows, $val->clientId);
            $sheet->setCellValue('G' . $rows, $val->client_name);
            $sheet->setCellValue('H' . $rows, $val->state);
            $sheet->setCellValue('I' . $rows, $val->client_address);
            $sheet->setCellValue('N' . $rows, !$val->hasIva);
	        $sheet->setCellValue('P' . $rows, empty($val->client_phone) ? $val->client_phone : $val->client_cellphone);       
	        $sheet->setCellValue('BI' . $rows, $val->total);       
	        //$sheet->setCellValue('BJ' . $rows, $val->total);       
	        $sheet->setCellValue('BR' . $rows, '0'); 
	        $sheet->setCellValue('BY' . $rows, $val->comments); 

	        $details = $this->invoices_model->getDetails($val->idInvoice);

	        foreach ($details as $det){
	            $sheetDetails->setCellValue('A' . $rowsDetails, '1');
	            $sheetDetails->setCellValue('B' . $rowsDetails, $val->idInvoice);
	            $sheetDetails->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetDetails->setCellValue('D' . $rowsDetails, $det->productId);
	            $sheetDetails->setCellValue('F' . $rowsDetails, $det->description);
	            $sheetDetails->setCellValue('G' . $rowsDetails, $det->quantity);
	            $sheetDetails->setCellValue('K' . $rowsDetails, $det->unit);
	            //$sheetDetails->setCellValue('N' . $rowsDetails, $det->hasIva);
	            $sheetDetails->setCellValue('L' . $rowsDetails, $det->subtotal);
		        $sheetDetails->setCellValue('Q' . $rowsDetails, $det->base);

	            $rowsDetails++;
	            $rd++;
	        } 

            $rows++;
        } 
        $writer = new Xlsx($spreadsheet);
		$writer->save("public/".$fileName);

		$writerDetails = new Xlsx($spreadsheetDetails);
		$writerDetails->save("public/".$fileNameDetails);


		header("Content-Type: application/vnd.ms-excel");
        redirect(base_url()."/public/".$fileName);        
    }    
}