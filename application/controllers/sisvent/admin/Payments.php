<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('caja_bancos');
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("subaccount_model");
        $this->load->model("auxsubaccount_model");
        $this->load->model("entry_model");
        $this->load->model("cashboxes_model");
        $this->load->model("bankaccounts_model");
        $this->load->model("cashmovements_model");
        $this->load->library("accounting_lib");
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

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		date_default_timezone_set("America/Bogota");

		$idInvoice = $this->input->post("invoice-id");
		$method = $this->input->post("method");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comments");
		$date = $this->input->post("date");
		$cashSourceTypeRaw = $this->input->post("cash_source_type");
		$cashSourceId = ($cashSourceTypeRaw == 'cashbox')
			? $this->input->post("cash_source_cashbox")
			: $this->input->post("cash_source_bank");
		$cashSourceType = ($cashSourceTypeRaw == 'cashbox') ? 'caja' : 'banco';

		if(!$date)
			$date = date('Y-m-d H:i:s');

		$invoice = $this->invoices_model->getInvoice($idInvoice);
		$userId = $this->session->userdata('user_data')['uname'];

		// 1. Guardar pago
		$data = array(
			'invoiceId' => $idInvoice,
			'clientId' => $invoice->clientId,
			'vendorId' => $invoice->vendorId,
			'paymentMethod' => $method,
			'payment' => $payment,
			'date' => date('Y-m-d H:i:s', strtotime($date)),
			'comments' => $comment
		);
		$this->payments_model->save($data);
		$paymentId = $this->db->insert_id();

		// 2. Actualizar factura
		$acum = $this->payments_model->getInvoicePayment($idInvoice);
		$this->invoices_model->update($idInvoice, array(
			'payment' => $acum->payment,
			'state' => $acum->payment + $invoice->discount >= round($invoice->total, 2) ? 2 : 1,
		));

		// 3. Crear movimiento de caja/banco
		$movementData = array(
			'sourceType' => $cashSourceType,
			'sourceId' => $cashSourceId,
			'movementType' => 'ingreso',
			'movementDate' => date('Y-m-d H:i:s', strtotime($date)),
			'amount' => $payment,
			'concept' => "Pago - Factura #" . str_pad($idInvoice, 6, "0", STR_PAD_LEFT),
			'category' => 'pago',
			'documentNumber' => (string)$idInvoice,
			'referenceType' => 'payment',
			'referenceId' => $paymentId,
			'status' => 'ejecutado',
			'created_by' => $userId
		);
		$this->cashmovements_model->save($movementData);
		$movementId = $this->cashmovements_model->lastID();

		// 4. Vincular pago con movimiento
		$this->payments_model->update($paymentId, array('cashMovementId' => $movementId));

		// 5. Actualizar saldo de caja/banco
		if ($cashSourceType == 'caja') {
			$this->cashboxes_model->updateBalance($cashSourceId, $payment, 'add');
		} else {
			$this->bankaccounts_model->updateBalance($cashSourceId, $payment, 'add');
		}

		// 6. Registrar asiento contable via Accounting_lib
		$cashAccountId = ($cashSourceType == 'caja')
			? $this->accounting_lib->getCashAccount($invoice->storeId)
			: $this->accounting_lib->getBankAccount($invoice->storeId);

		$this->accounting_lib->recordPayment($paymentId, $idInvoice, $invoice->clientId, $payment, $method, $invoice->storeId, $userId, $cashAccountId);

		$this->logs_model->logMessage("info", "Usuario " . $userId . " hizo pago a factura " . $idInvoice);

		redirect(base_url()."sisvent/admin/payments");
	}


	public function getInvoice()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$invoice = $this->invoices_model->getInvoice($this->input->post("inv"));

		$storeId = $invoice->storeId ?: $this->session->userdata('user_data')['store'];
		$invoice->subaccounts = $this->subaccount_model->getStoreSubaccounts($storeId);
		$invoice->cashboxes = $this->cashboxes_model->getCashboxesByStore($storeId);
		$invoice->bankaccounts = $this->bankaccounts_model->getBankAccountsByStore($storeId);

		echo json_encode($invoice);
	}

	public function edit($payment_id){
		$data =array(
			'payment' => $this->payments_model->getPayment($payment_id)
		);
		$this->load->view("sisvent/admin/payments/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

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
		}

	}

	public function delete($payment_id){

		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$payment = $this->payments_model->getPayment($payment_id);

		// Revertir movimiento de caja/banco si existe
		if ($payment->cashMovementId) {
			$movement = $this->cashmovements_model->getMovement($payment->cashMovementId);
			if ($movement && $movement->status != 'anulado') {
				if ($movement->sourceType == 'cashbox') {
					$this->cashboxes_model->updateBalance($movement->sourceId, $movement->amount, 'sub');
				} else {
					$this->bankaccounts_model->updateBalance($movement->sourceId, $movement->amount, 'sub');
				}
				$this->cashmovements_model->remove($movement->idMovement);
			}
		}

		$invoice = $this->invoices_model->getInvoice($payment->invoiceId);

		$data  = array(
			'payment' => $invoice->payment - $payment->payment,
			'state' => ($invoice->payment - $payment->payment >= round($invoice->total,2)) ? 2 : (($invoice->payment - $payment->payment <= 0) ? 0 : 1),
		);

		$this->invoices_model->update($payment->invoiceId,$data);

		$this->payments_model->remove($payment_id);
        $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha eliminado pago ".$payment_id);
		echo base_url()."sisvent/admin/payments";
	}

}
