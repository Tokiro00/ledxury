<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("subaccount_model");
        $this->load->model("auxsubaccount_model");
        $this->load->model("entry_model");
    }

	public function index()
	{
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;

		$total = $this->payments_model->getTotal();
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'page' => $page,
			'total' => $total,
			'limit' => $limit,
			'payments' => $this->payments_model->getPayments($page, $limit), 
		);
		$this->load->view("sisvent/admin/payments/list",$data);
		
	}

	public function search($term)
	{
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->payments_model->getTotalSearch($term);
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'total' => $total,
			'page' => $pag,
			'limit' => $limit,
			'payments' => $this->payments_model->searchByWord($term, $page, $limit), 
		);
		$this->load->view("sisvent/admin/payments/list",$data);
		
	}

	public function add(){

		$data =array( 
			'invoices' => $this->invoices_model->getNonPaidInvoices($this->session->userdata('user_data')['role'] != 3),
			'methods' => $this->payments_model->getPaymentMethods(), 
		);
		$this->load->view("sisvent/admin/payments/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		date_default_timezone_set("America/Bogota");

		$idInvoice = $this->input->post("invoice-id");
		$method = $this->input->post("method");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comments");
		$date = $this->input->post("date");
		$subaccountId = $this->input->post("subaccount");

		if(!$date)
			$date = date('Y-m-d H:i:s');
		
		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'invoiceId' =>$idInvoice,
			'clientId' =>$invoice->clientId,
			'vendorId' =>$invoice->vendorId,
			'paymentMethod' =>$method,
			'payment' =>$payment,
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			'comments' =>$comment
		);

		$this->payments_model->save($data);

		$acum = $this->payments_model->getInvoicePayment($idInvoice);

		$data  = array(
			'payment' => $acum->payment,
			'state' => $acum->payment + $invoice->discount >= $invoice->total ? 2 : 1,
		);

		$this->invoices_model->update($idInvoice,$data);

		$client = $this->clients_model->getClient($invoice->clientId);
		$clientSubaccountId = "130505".$client->f_id;
		$clientSubaccount = $this->auxsubaccount_model->getAuxsubaccountByAccountId($clientSubaccountId);
		$account_balance = 0;
		$entryType = "Ajuste";
		if(!isset($clientSubaccount)){
			$account_balance = $this->invoices_model->getClientDebt($invoice->clientId);
			$accountAccount = $this->subaccount_model->getClientSubaccountsByStore($invoice->storeId);
			$data  = array(
                'accountID' => $clientSubaccountId,
                'accountAccount' => $accountAccount->id,
                'accountName' => $client->name,
                'accountBalance' => -$payment,//$account_balance->debt,
                'accountSide' => 1,
                'accountStatement' => 1
            );
            /*switch ($data['accountSide']) {
                case 1://Debito
                    $data['accountDebit']  = $payment;
                    $data['accountCredit'] = 0;
                    $data2 = array(
		                'accountDebit' => $accountAccount->accountDebit + $payment
		            );
                    $this->subaccount_model->update($accountAccount->id, $data2);
                    break;

                default://Credito*/
                    $data['accountDebit']  = 0;
                    $data['accountCredit'] = $payment;
                    $data2 = array(
		                'accountCredit' => $accountAccount->accountCredit + $payment
		            );
                    $this->subaccount_model->update($accountAccount->id, $data2);
            /*        break;
            }*/

            $this->auxsubaccount_model->save($data);

            $clientSubaccountDBId =  $this->db->insert_id();
			$clientSubaccount = $this->auxsubaccount_model->getAuxsubaccount($clientSubaccountDBId);
			$entryType = "Inicial";

		}else{
			$account_balance = $clientSubaccount->accountBalance;
			$clientSubaccountDBId = $clientSubaccount->id;
			$accountAccount = $this->subaccount_model->getSubaccount($clientSubaccount->accountAccount);

            /*switch ($clientSubaccount->accountSide) {
                case 1:
                	$data  = array(
		                'accountDebit' => $clientSubaccount->accountDebit + $payment,
		            );
                    $this->auxsubaccount_model->update($clientSubaccount->id, $data);
                    $data2 = array(
		                'accountDebit' => $accountAccount->accountDebit + $payment
		            );
                    $this->subaccount_model->update($accountAccount->id, $data2);
                    break;

                default:*/
                	$data  = array(
		                'accountCredit' => $clientSubaccount->accountCredit + $payment,
		                'accountBalance' => $clientSubaccount->accountDebit - ($clientSubaccount->accountCredit + $payment),
		            );
                    $this->auxsubaccount_model->update($clientSubaccount->id, $data);
                    $data2 = array(
		                'accountCredit' => $accountAccount->accountCredit + $payment,
		                'accountBalance' => $accountAccount->accountDebit - ($accountAccount->accountCredit + $payment)
		            );
                    $this->subaccount_model->update($accountAccount->id, $data2);
                    //break;
            //}
		}

		$subaccount = $this->subaccount_model->getSubaccount($subaccountId);

		$data  = array(
			'userID' => $this->session->userdata('user_data')['uname'],
			'entryDescription' => "Pago Factura ".$idInvoice." de ".$client->name,
			'entryType' => $entryType,
			'entryDebitAccount' => $subaccountId,
			//'entryDebitAuxaccount' => $subaccountId,
			'entryDebitBalance' => $payment,
			'entryCreditAccount' => $accountAccount->id,
			'entryCreditAuxaccount' => $clientSubaccountDBId,
			'entryCreditBalance' => $payment,
			'entryStatus' => 1,
			//'entryStatusComment' => ,
			'created_by' => $this->session->userdata('user_data')['uname'],
			'entryCreateDate' => date('Y-m-d H:i:s')
        );
        $this->entry_model->save($data);
        
        $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." hizo pago a factura ".$idInvoice);

		redirect(base_url()."sisvent/admin/payments");
		
		/*$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'name' => $name
			);

			if ($this->payments_model->save($data)) {
				redirect(base_url()."sisvent/admin/payments");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/admin/payments/add");
			}
		}
		else{
			$this->add();
		}*/
	}

	
	public function getInvoice()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$invoice = $this->invoices_model->getInvoice($this->input->post("inv"));

		$invoice->subaccounts = $this->subaccount_model->getStoreSubaccounts($invoice->storeId);

		echo json_encode($invoice);
	}
	/*public function getVendorClients()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendors = $this->clients_model->getVendorClients($this->input->post("vendor"));
		echo json_encode($vendors);
	}

	public function getClient()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$client = $this->clients_model->getClient($this->input->post("client"));
		echo json_encode($client);
	}*/

	public function edit($payment_id){
		$data =array( 
			'payment' => $this->payments_model->getPayment($payment_id)
		);
		//print_r($data);
		$this->load->view("sisvent/admin/payments/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$payment_id = $this->input->post("payment_id");
		$comment = $this->input->post("comments");
		$date = $this->input->post("date");
		
		$data  = array(
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			'comments' =>$comment
		);

		if ($this->payments_model->update($payment_id,$data)) {
        	$this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha editado pago ".$payment_id);
			redirect(base_url()."sisvent/admin/payments");
		}
		else{
			$data =array( 
				'payment' => $this->payments_model->getPayment($payment_id)
			);
			$this->session->set_flashdata("error","No se pudo actualizar la información");
			$this->load->view("sisvent/admin/payments/edit",$data);
			//redirect(base_url()."sisvent/admin/payments/edit/".$payment_id);
		}
		
	}

	public function delete($payment_id){
		
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$payment = $this->payments_model->getPayment($payment_id);

		$invoice = $this->invoices_model->getInvoice($payment->invoiceId);

		$data  = array(
			'payment' => $invoice->payment - $payment->payment,
			'state' => ($invoice->payment - $payment->payment >= $invoice->total) ? 2 : (($invoice->payment - $payment->payment <= 0) ? 0 : 1),
		);

		$this->invoices_model->update($payment->invoiceId,$data);

		$this->payments_model->remove($payment_id);
		//redirect(base_url()."sisvent/admin/payments");
        $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha eliminado pago ".$payment_id);
		echo base_url()."sisvent/admin/payments";
	}
	
}