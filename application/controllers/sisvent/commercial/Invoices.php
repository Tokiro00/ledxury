<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class Invoices extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('facturas');
        $this->load->model("payments_model");
        $this->load->model("budgets_model");
        $this->load->model("invoices_model");
        $this->load->model("stores_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("users_model");
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
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$ps = $this->input->get('s');
		$iva = $this->input->get('i');
		$lc = $this->input->get('lc');

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
		if(!$ps)
			$ps = '';
		if(is_null($iva))
			$iva = 'all';
		if(!$lc)
			$lc = 0;

		if($lc){
			if($ps != ''){
				redirect(base_url()."sisvent/commercial/invoices/search/".$ps.createFullParamsLinks($page, $store, $vendor, $state, $client, $iva )).'&lc=1';
			}else{
				redirect(base_url()."sisvent/commercial/invoices/legalcollection".createFullParamsLinks($page));
			}
		}else
		if($ps != ''){
			redirect(base_url()."sisvent/commercial/invoices/search/".$ps.createFullParamsLinks($page, $store, $vendor, $state, $client, $iva ));
		}else{

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
				'ps' => $ps,
				'page' => $page,
				'strname' => $store != 'all' ? $this->stores_model->getStore($store)->name : '',
				'limit' => $limit,
				'invoices' => $this->invoices_model->getInvoices($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit),
				'last_query' => $this->db->last_query()
			);
			$this->load->view("sisvent/commercial/invoices/list",$data);
		}
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
		$ps = $this->input->get('s');
		$iva = $this->input->get('i');
		$lc = $this->input->get('lc');

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
		if(!$ps)
			$ps = '';
		if(is_null($iva))
			$iva = 'all';
		if(!$lc)
			$lc = 0;

		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($invoice_id), 
			'vendors' => $this->vendors_model->getVendors(),
			'details' => $this->invoices_model->getDetails($invoice_id),
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'ps' => $ps,
			'lc' => $lc,
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
		$list_price = $this->input->post("list_price");
		$legal_collection = $this->input->post("legal_collection");
		$comments = $this->input->post("comments");
		$client = $this->input->post("client");
		
		$products = $this->input->post("refs");
		$budget_bases = $this->input->post("price_base");
		$budget_rates = $this->input->post("budget-rates");
		$if_id = $this->input->post("if_id");
		$quantities = $this->input->post("budget-quantities");
		$reviewed = $this->input->post("reviewed");
		$budget_subtotal = $this->input->post("budget-subtotal");
		$discount = $this->input->post("discount");
		$discount_perc = $this->input->post("discount_perc");
		$vendor = $this->input->post("vendor");
		$tracking_number = $this->input->post("tracking_number");
		$tracking_carrier = $this->input->post("tracking_carrier");

		$page = $this->input->get('p');
		$pstore = $this->input->get('str');
		$pvendor = $this->input->get('v');
		$pstate = $this->input->get('ste');
		$pclient = $this->input->get('c');
		$ps = $this->input->get('s');
		$iva = $this->input->get('i');
		$lc = $this->input->get('lc');

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
		if(!$ps)
			$ps = '';
		if(is_null($iva))
			$iva = 'all';
		if(!$lc)
			$lc = 0;

		$acum = $this->payments_model->getInvoicePayment($idInvoice);

		// Obtener factura actual para comparar tracking
		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'total' => $total,
			'clientId' => $client,
			'vendorId' => $vendor,
			'if_id' => $if_id,
			'discount' => $discount,
			'discount_perc' => $discount_perc,
			'e_commerce' => $e_commerce == "on",
			'list_price' => $list_price == "on",
			'legal_collection' => $legal_collection == "on",
			'hasIva' => $hasIva ?? 0,
			//'state' => ($acum->payment + $discount) >= $total ? 2 : ($acum->payment == 0 ? 0 : 1),
			'state' => $invoice->list_price ? (($acum->payment) >= (round($total,2)) * 0.7 ? 2 : ($acum->payment == 0 ? 0 : 1)) : (($acum->payment + $discount) >= round($total,2) ? 2 : ($acum->payment == 0 ? 0 : 1)),
			'comments' => $comments,
		);

		// Agregar campos de tracking si se proporcionan
		if (!empty($tracking_number)) {
			$data['tracking_number'] = trim($tracking_number);
			$data['tracking_carrier'] = $tracking_carrier ?: 'interrapidisimo';
			// Preservar estado existente o usar 'pending' para nuevos envíos
			// El estado se actualizará automáticamente cuando se integre la API de tracking
			if (empty($invoice->tracking_number)) {
				$data['tracking_status'] = 'pending';
				$data['shipped_at'] = date('Y-m-d H:i:s');
			}
			$data['tracking_last_update'] = date('Y-m-d H:i:s');
		} elseif (isset($tracking_number) && $tracking_number === '') {
			// Si se borra el tracking number, limpiar campos relacionados
			$data['tracking_number'] = null;
			$data['tracking_carrier'] = null;
			$data['tracking_status'] = null;
			$data['tracking_location'] = null;
			$data['tracking_last_update'] = null;
			$data['shipped_at'] = null;
		}


		if ($this->invoices_model->update($idInvoice,$data)) {
			$this->invoices_model->removeDetails($idInvoice);
			$this->_save_detail($products,$idInvoice,$quantities,$budget_rates,$budget_bases,$budget_subtotal,$reviewed);
        	$this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha editado factura ".$idInvoice);
			if($lc)
				redirect(base_url()."sisvent/commercial/invoices/legalcollection".createFullParamsLinks($page));
			else
				redirect(base_url()."sisvent/commercial/invoices".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva, $ps ));
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
				'ps' => $ps,
				'lc' => $lc,
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
				'reviewed' => (!empty($reviewed) && is_array($reviewed) && in_array($i, $reviewed)) ? 1 : 0,
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
				'reviewed' => (!empty($reviewed) && is_array($reviewed) && in_array($i, $reviewed)) ? 1 : 0,
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
		$ps = $this->input->get('s');
		$iva = $this->input->get('i');
		$lc = $this->input->get('lc');


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
		if(!$ps)
			$ps = '';
		if(is_null($iva))
			$iva = 'all';
		if(!$lc)
			$lc = 0;

		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($invoice_id), 
			'details' => $this->invoices_model->getDetails($invoice_id),
			'pstore' => $store,
			'pvendor' => $vendor,
			'pstate' => $state,
			'pclient' => $client,
			'piva' => $iva,
			'ps' => $ps,
			'lc' => $lc,
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
		$ps = $this->input->get('s');
		$iva = $this->input->get('i');
		$lc = $this->input->get('lc');

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
		if(!$ps)
			$ps = '';
		if(is_null($iva))
			$iva = 'all';
		if(!$lc)
			$lc = 0;

		$acum = $this->payments_model->getInvoicePayment($idInvoice);
		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'total' => $invoice->total - $total,
			//'state' => ($invoice->payment + $invoice->discount) >= $invoice->total - $total ? 2 : ($invoice->payment == 0 ? 0 : 1),
			'state' => $invoice->list_price ? (($invoice->payment) >= round($invoice->total - $total,2) * 0.7 ? 2 : ($invoice->payment == 0 ? 0 : 1)) : (($invoice->payment + $invoice->discount) >= round($invoice->total - $total,2) ? 2 : ($invoice->payment == 0 ? 0 : 1)),
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
        	$this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." hizo reembolso ".$idRefund." a factura ".$idInvoice);

			// Registrar asiento contable de la devolución
			$this->accounting_lib->recordRefund(
				$idRefund,
				$idInvoice,
				$invoice->clientId,
				$total,
				$invoice->storeId,
				$this->session->userdata('user_data')['uname']
			);

			$this->_update_detail_after_refund($products,$store,$idRefund,$idInvoice,$quantities,$n_quantities,$budget_rates,$budget_bases,$budget_subtotal);
			if($lc)
				redirect(base_url()."sisvent/commercial/invoices/legalcollection".createFullParamsLinks($page));
			else
				redirect(base_url()."sisvent/commercial/invoices".createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $iva, $ps ));
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
				'ps' => $ps,
				'lc' => $lc,
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
				
				if(!empty($productoActual)){
					$data = array(
						'stock' => $productoActual->stock + $n_quantities[$i]
					);
					$this->inventory_model->update($store,$products[$i],$data);
				}else{
					$data  = array(
						'idStore' => $store, 
						'idProduct' => $products[$i],
						'stock' => $n_quantities[$i]
					);
					$this->inventory_model->save($data);
				}
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

	/**
	 * List all refunds with pagination
	 */
	public function refunds(){
		$page = $this->input->get('p') ?: 1;
		$limit = 50;

		$total = $this->invoices_model->getTotalRefunds();
		$last = ceil($total / $limit);

		if ($page > $last) $page = $last;
		if ($page <= 0) $page = 1;

		$data = array(
			'refunds' => $this->invoices_model->getRefunds($page, $limit),
			'page' => $page,
			'total' => $total,
			'limit' => $limit
		);
		$this->load->view("sisvent/commercial/invoices/refunds", $data);
	}

	/**
	 * View single refund details
	 */
	public function viewRefund($id){
		$refund = $this->invoices_model->getRefund($id);

		if (!$refund) {
			$this->session->set_flashdata("error", "Devolución no encontrada");
			redirect(base_url() . "sisvent/commercial/invoices/refunds");
			return;
		}

		$data = array(
			'refund' => $refund,
			'details' => $this->invoices_model->getRefundDetails($id)
		);
		$this->load->view("sisvent/commercial/invoices/viewrefund", $data);
	}

	/**
	 * Undo a refund - restore invoice and inventory
	 */
	public function undoRefund($id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$refund = $this->invoices_model->getRefund($id);

		if (!$refund) {
			$this->session->set_flashdata("error", "Devolución no encontrada");
			redirect(base_url() . "sisvent/commercial/invoices/refunds");
			return;
		}

		$userId = $this->session->userdata('user_data')['uname'];
		$refundDetails = $this->invoices_model->getRefundDetails($id);

		// Start transaction
		$this->db->trans_start();

		// 1. Restore invoice total
		$newTotal = $refund->invoiceTotal + $refund->total;

		// 2. Recalculate invoice state
		$newState = 0;
		if ($refund->list_price) {
			// list_price logic: paid >= 70% of total
			$newState = ($refund->invoicePayment >= round($newTotal, 2) * 0.7) ? 2 : ($refund->invoicePayment == 0 ? 0 : 1);
		} else {
			// normal logic: paid + discount >= total
			$newState = (($refund->invoicePayment + $refund->invoiceDiscount) >= round($newTotal, 2)) ? 2 : ($refund->invoicePayment == 0 ? 0 : 1);
		}

		$this->invoices_model->update($refund->invoiceId, array(
			'total' => $newTotal,
			'state' => $newState
		));

		// 3. Restore invoice details quantities and reduce inventory
		foreach ($refundDetails as $detail) {
			// Get current invoice detail
			$invoiceDetail = $this->invoices_model->getInvoiceDetail($refund->invoiceId, $detail->productId);

			if ($invoiceDetail) {
				// Restore quantity in invoice detail
				$newQty = $invoiceDetail->quantity + $detail->quantity;
				$newDetailTotal = $newQty * $invoiceDetail->unit;

				$this->invoices_model->update_detail($refund->invoiceId, $detail->productId, array(
					'quantity' => $newQty,
					'total' => $newDetailTotal
				));
			}

			// Reduce inventory (products go back out of stock)
			$storeProduct = $this->inventory_model->getStoreProduct($refund->storeId, $detail->productId);
			if ($storeProduct) {
				$newStock = max(0, $storeProduct->stock - $detail->quantity);
				$this->inventory_model->update($refund->storeId, $detail->productId, array(
					'stock' => $newStock
				));
			}
		}

		// 4. Soft delete the refund
		$this->invoices_model->deleteRefund($id);

		// 5. Create reversal accounting entry
		$this->accounting_lib->recordRefundReversal(
			$id,
			$refund->invoiceId,
			$refund->clientId,
			$refund->total,
			$refund->storeId,
			$userId
		);

		$this->db->trans_complete();

		if ($this->db->trans_status()) {
			$this->logs_model->logMessage("info", "Usuario " . $userId . " deshizo devolución #" . $id . " de factura #" . $refund->invoiceId);
			$this->session->set_flashdata("success", "Devolución #" . $id . " deshecha exitosamente. La factura ha sido restaurada.");
		} else {
			$this->session->set_flashdata("error", "Error al deshacer la devolución");
		}

		redirect(base_url() . "sisvent/commercial/invoices/refunds");
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

	public function viewpayment(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$idInvoice = $this->input->post("id");
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'payments' => $this->payments_model->getInvoicePayments($idInvoice),
		);
		$this->load->view("sisvent/admin/payments/view",$data);
	}

	public function viewpayments($idInvoice){
		$this->outh_model->CSRFVerify();

		//if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		//$idInvoice = $this->input->post("id");
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'payments' => $this->payments_model->getInvoicePayments($idInvoice),
		);
		$this->load->view("sisvent/admin/payments/view",$data);
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
		$lc = $this->input->get('lc');

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
		if(!$lc)
			$lc = 0;

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		if($lc){
			$total = $this->invoices_model->getTotalSearchLC($term,$store, $user->admin_store_arr);
		}else{
			$total = $this->invoices_model->getTotalSearch($term,$store, $vendor, $state, $client, $iva, $user->admin_store_arr);
		}
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		if($lc){
			$data  = array(
				'stores' => $this->stores_model->getStores(),
				'total' => $total,
				'page' => $page,
				'pstore' => $store,
				'limit' => $limit,
				'ps' => $term,
				'invoices' => $this->invoices_model->searchByWordLC($term,$this->session->userdata('user_data')['role'] != 3, $store, $user->admin_store_arr, $page, $limit), 
				'lq' => $this->db->last_query()
			);
			$this->load->view("sisvent/commercial/invoices/legalcollection",$data);
		}else{
			$data  = array(
				'stores' => $this->stores_model->getStores(),
				'vendors' => $this->vendors_model->getVendors(),
				'clients' => $this->clients_model->getClients(),
				'total' => $total,
				'pstore' => $store,
				'pvendor' => $vendor,
				'pstate' => $state,
				'pclient' => $client,
				'strname' => $store != 'all' ? $this->stores_model->getStore($store)->name : '',
				'piva' => $iva,
				'ps' => $term,
				'lc' => $lc,
				'page' => $pag,
				'limit' => $limit,
				'invoices' => $this->invoices_model->searchByWord($term,$this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit),
				'last_query' => $this->db->last_query()
			);
			$this->load->view("sisvent/commercial/invoices/list",$data);
		}
	}
	
	public function searchbyp()
	{
		$page = $this->input->get('p');
		//$lc = $this->input->get('lc');

		$limit = 50;
		if(!$page)
			$page = 1;
		
		if(!$lc)
			$lc = 0;
		if(!$ps)
			$ps = 'zzz';
		
		
		redirect(base_url()."sisvent/commercial/invoices/searchbyproduct/".$ps.createFullParamsLinks($page));

			
	}

	public function searchbyproduct($term){
		
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
		$lc = $this->input->get('lc');

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
		if(!$lc)
			$lc = 0;

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		if($lc){
			$total = $this->invoices_model->getTotalSearchByProductLC($term);
		}else{
			$total = $this->invoices_model->getTotalSearchByProduct($term);
		}
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		if($lc){
			$data  = array(
				'stores' => $this->stores_model->getStores(),
				'total' => $total,
				'page' => $page,
				'pstore' => $store,
				'limit' => $limit,
				'ps' => $term,
				'invoices' => $this->invoices_model->searchByProductLC($term,$this->session->userdata('user_data')['role'] != 3, $page, $limit), 
				'lq' => $this->db->last_query()
			);
			$this->load->view("sisvent/commercial/invoices/searchbyp",$data);
		}else{
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
				'ps' => $term,
				'lc' => $lc,
				'page' => $pag,
				'limit' => $limit,
				'invoices' => $this->invoices_model->searchByProduct($term,$this->session->userdata('user_data')['role'] != 3, $page, $limit),
				'last_query' => $this->db->last_query()
			);
			$this->load->view("sisvent/commercial/invoices/searchbyp",$data);
		}
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
		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice),
			'vendors' => $this->vendors_model->getVendors(),
			'methods' => $this->payments_model->getPaymentMethods(),
			'subaccounts' => $this->subaccount_model->getStoreSubaccounts($invoice->storeId),
			'cashboxes' => $this->cashboxes_model->getCashboxesByStore($invoice->storeId),
			'bankaccounts' => $this->bankaccounts_model->getBankAccountsByStore($invoice->storeId),
			'params' => $params
		);
		$this->load->view("sisvent/commercial/invoices/payment",$data);
	}

	public function makepayment(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$idInvoice = $this->input->post("id");
		$method = $this->input->post("method");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comment");
		$date = $this->input->post("date");
		$cashSourceTypeRaw = $this->input->post("cash_source_type");
		$cashSourceId = ($cashSourceTypeRaw == 'cashbox')
			? $this->input->post("cash_source_cashbox")
			: $this->input->post("cash_source_bank");
		$cashSourceType = ($cashSourceTypeRaw == 'cashbox') ? 'caja' : 'banco';
		$params = $this->input->post("params");


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
			'state' => $invoice->list_price
				? ($acum->payment >= ($invoice->total * 0.7) ? 2 : ($acum->payment == 0 ? 0 : 1))
				: (($acum->payment + $invoice->discount) >= round($invoice->total, 2) ? 2 : ($acum->payment == 0 ? 0 : 1)),
		));

		
		// 3. Crear movimiento de caja/banco (ingreso)
		$movementData = array(
			'sourceType' => $cashSourceType,
			'sourceId' => $cashSourceId,
			'movementType' => 'ingreso',
			'movementDate' => date('Y-m-d H:i:s', strtotime($date)),
			'amount' => $payment,
			'concept' => "Pago - Factura #" . str_pad($idInvoice, 6, "0", STR_PAD_LEFT),
			'category' => 'pago',
			'documentNumber' => $idInvoice,
			'referenceType' => 'payment',
			'referenceId' => $paymentId,
			'status' => 'ejecutado'
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

		$this->accounting_lib->recordPayment(
			$paymentId,
			$idInvoice,
			$invoice->clientId,
			$payment,
			$method,
			$invoice->storeId,
			$userId,
			$cashAccountId
		);

		$this->logs_model->logMessage("info", "Usuario " . $userId . " hizo pago a factura " . $idInvoice);

		echo base_url()."sisvent/commercial/invoices".$params;
	}

	public function validate()
	{
		

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
		if(!empty($user->admin_store))
			$user->admin_store_arr = explode(',', $user->admin_store);
		else
			$user->admin_store_arr = array();

		$invs = $this->invoices_model->getNoPaidNoInLegalCollectionInvoices($this->session->userdata('user_data')['role'] != 3);
		$todayMin3M = date( "Y-m-d H:i:s", strtotime('-3 months'));

		foreach ($invs as $key => $invoice) {
			if(empty($invoice->originalVendorId) && $invoice->date < $todayMin3M && ($invoice->state == 0 || $invoice->state == 1))
			{
				$collector = '00000';
				/*switch ($invoice->storeId) {
					case 1://Medellín
					case 2:
						$collector = '43755412';//Aleja
						break;
					case 3://Bogotá
					case 4:
						$collector = '12077935';//Yubi
						break;
					case 5://Cali
					case 6:
						$collector = '1126908266';//Yami
						break;
					default:
						break;
				}*/
				$data  = array(
					'legal_collection' => 1,
					'originalVendorId' => $invoice->vendorId,
					'vendorId' => $collector
				);

				$this->invoices_model->update($invoice->idInvoice,$data);
			}
		}

		redirect(base_url()."sisvent/commercial/invoices/legalcollection");
	}

	public function checkold()
	{
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$vendor = $this->input->get('v');
		$state = $this->input->get('ste');
		$client = $this->input->get('c');
		$ps = $this->input->get('s');
		$iva = $this->input->get('i');
		$lc = $this->input->get('lc');

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
		if(!$ps)
			$ps = '';
		if(is_null($iva))
			$iva = 'all';
		if(!$lc)
			$lc = 0;

		

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
				'ps' => $ps,
				'page' => $page,
				'strname' => $store != 'all' ? $this->stores_model->getStore($store)->name : '',
				'limit' => $limit,
				'invoices' => $this->invoices_model->getNoPaidNoInLegalCollectionInvoices($this->session->userdata('user_data')['role'] != 3),//$this->invoices_model->getInvoices($this->session->userdata('user_data')['role'] != 3, $store, $vendor, $state, $client, $iva, $user->admin_store_arr, $page, $limit),
				'last_query' => $this->db->last_query()
			);
			$this->load->view("sisvent/commercial/invoices/list",$data);
		
	}

	public function legalcollection()
	{
		$page = $this->input->get('p');
		$store = $this->input->get('str');
		$ps = $this->input->get('s');
	
		$limit = 50;
		if(!$page)
			$page = 1;
		if(!$store)
			$store = 'all';
		if(!$ps)
			$ps = '';

		$total = $this->invoices_model->getLCTotal($store, $this->session->userdata('user_data')['role'] != 3);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;
		
		$data  = array(
			'stores' => $this->stores_model->getStores(),
			'total' => $total,
			'page' => $page,
			'pstore' => $store,
			'limit' => $limit,
			'ps' => $ps,
			'invoices' => $this->invoices_model->getLegalColletionInvoices($store, $this->session->userdata('user_data')['role'] != 3, $page, $limit), 
			'lq' => $this->db->last_query()
		);
		$this->load->view("sisvent/commercial/invoices/legalcollection",$data);
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
        $sheet->setCellValue('AT1', 'Base');       
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
       		switch ($val->storeId) {
	        	case 3:
	        	case 5:
            		$sheet->setCellValue('A' . $rows, date("Y")-2020);

	        	break;
	        	case 7:
            		$sheet->setCellValue('A' . $rows, date("Y")-2022);
	        		break;
	        	default:
            		$sheet->setCellValue('A' . $rows, date("Y")-2018);
	        		break;
	        }
            //$sheet->setCellValue('A' . $rows, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
            $sheet->setCellValue('B' . $rows, $val->idInvoice);
            $sheet->setCellValue('C' . $rows, substr($val->comments, 0, 50));
            $sheet->setCellValue('D' . $rows, date('Y-m-d H:i:s',strtotime($val->date)));
	        $sheet->setCellValue('E' . $rows, '0');
	        switch ($val->storeId) {
	        	case 1:
	        	case 5:
	        	case 7:
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
	        $sheet->setCellValue('AT' . $rows, $val->total);       
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
	            switch ($val->storeId) {
		        	case 3:
		        	case 5:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2020);
		        	break;
		        	case 7:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2022);
		        		break;
		        	default:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2018);
		        		break;
		        }
	            //$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
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

    public function exportFacCompra(){
		$data  = array(
			'stores' => $this->stores_model->getStores(),  
		);
		$this->load->view("sisvent/commercial/invoices/exportfaccomp",$data);
	}

	public function createExcelFacCompra() {

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

		$dat = uniqid('MAMFacsCompra', true);

		$fileName = 'FRE-'.$dat.'.xlsx';  
		$fileNameDetails = 'LFR-'.$dat.'.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all',  'all', '', -1, 50, $from, $until);
		$spreadsheet = new Spreadsheet();
		$spreadsheetDetails = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetDetails = $spreadsheetDetails->getActiveSheet();
       	$sheet->setCellValue('A1', 'Tipo de documento');
		$sheet->setCellValue('B1', 'Número de documento');
		$sheet->setCellValue('C1', 'Código exterior');
		$sheet->setCellValue('D1', 'Referencia');
		$sheet->setCellValue('E1', 'Fecha');
		$sheet->setCellValue('F1', 'Proveedor');
		$sheet->setCellValue('G1', 'Estado');
		$sheet->setCellValue('H1', 'Código de cliente');
		$sheet->setCellValue('I1', 'Nombre del proveedor');
		$sheet->setCellValue('J1', 'Domicilio del proveedor');
		$sheet->setCellValue('K1', 'Población');
		$sheet->setCellValue('L1', 'Código postal');
		$sheet->setCellValue('M1', 'Provincia');
		$sheet->setCellValue('N1', 'N.I.F.');
		$sheet->setCellValue('O1', 'Tipo de IVA');
		$sheet->setCellValue('P1', 'Recargo de equivalencia');
		$sheet->setCellValue('Q1', 'Teléfono del proveedor');
		$sheet->setCellValue('R1', 'Importe neto 1');
		$sheet->setCellValue('S1', 'Importe neto 2');
		$sheet->setCellValue('T1', 'Importe neto 3');
		$sheet->setCellValue('U1', 'Porcentaje de descuento 1');
		$sheet->setCellValue('V1', 'Porcentaje de descuento 2');
		$sheet->setCellValue('W1', 'Porcentaje de descuento 3');
		$sheet->setCellValue('X1', 'Importe de descuento 1');
		$sheet->setCellValue('Y1', 'Importe de descuento 2');
		$sheet->setCellValue('Z1', 'Importe de descuento 3');
		$sheet->setCellValue('AA1', 'Porcentaje de pronto pago 1');
		$sheet->setCellValue('AB1', 'Porcentaje de pronto pago 2');
		$sheet->setCellValue('AC1', 'Porcentaje de pronto pago 3');
		$sheet->setCellValue('AD1', 'Importe pronto pago 1');
		$sheet->setCellValue('AE1', 'Importe pronto pago 2');
		$sheet->setCellValue('AF1', 'Importe pronto pago 3');
		$sheet->setCellValue('AG1', 'Porcentaje portes 1');
		$sheet->setCellValue('AH1', 'Porcentaje portes 2');
		$sheet->setCellValue('AI1', 'Porcentaje portes 3');
		$sheet->setCellValue('AJ1', 'Importe portes 1');
		$sheet->setCellValue('AK1', 'Importe portes 2');
		$sheet->setCellValue('AL1', 'Importe portes 3');
		$sheet->setCellValue('AM1', 'Porcentaje de financiación 1');
		$sheet->setCellValue('AN1', 'Porcentaje de financiación 2');
		$sheet->setCellValue('AO1', 'Porcentaje de financiación 3');
		$sheet->setCellValue('AP1', 'Importe de financiación 1 ');
		$sheet->setCellValue('AQ1', 'Importe de financiación 2');
		$sheet->setCellValue('AR1', 'Importe de financiación 3');
		$sheet->setCellValue('AS1', 'Base imponible 1');
		$sheet->setCellValue('AT1', 'Base imponible 2');
		$sheet->setCellValue('AU1', 'Base imponible 3');
		$sheet->setCellValue('AV1', 'Porcentaje IVA 1');
		$sheet->setCellValue('AW1', 'Porcentaje IVA 2');
		$sheet->setCellValue('AX1', 'Porcentaje IVA 3');
		$sheet->setCellValue('AY1', 'Importe IVA 1');
		$sheet->setCellValue('AZ1', 'Importe IVA 2');
		$sheet->setCellValue('BA1', 'Importe IVA 3');
		$sheet->setCellValue('BB1', 'Porcentaje de recargo de equivalencia 1');
		$sheet->setCellValue('BC1', 'Porcentaje de recargo de equivalencia 2');
		$sheet->setCellValue('BD1', 'Porcentaje de recargo de equivalencia 3');
		$sheet->setCellValue('BE1', 'Importe de recargo de equivalencia 1');
		$sheet->setCellValue('BF1', 'Importe de recargo de equivalencia 2');
		$sheet->setCellValue('BG1', 'Importe de recargo de equivalencia 3');
		$sheet->setCellValue('BH1', 'Porcentaje de la retención');
		$sheet->setCellValue('BI1', 'Importe de la retención');
		$sheet->setCellValue('BJ1', 'Total');
		$sheet->setCellValue('BK1', 'Forma de pago');
		$sheet->setCellValue('BL1', '1ª línea de observaciones');
		$sheet->setCellValue('BM1', '2ª línea de observaciones');
		$sheet->setCellValue('BN1', 'Traspasada a contabilidad');
		$sheet->setCellValue('BO1', 'Fecha de entrega ');
		$sheet->setCellValue('BP1', 'Hora de entrega');
		$sheet->setCellValue('BQ1', 'Portes');
		$sheet->setCellValue('BR1', 'Texto de portes');
		$sheet->setCellValue('BS1', 'Agencia de transportes');
		$sheet->setCellValue('BT1', 'Comentario después de las líneas de detalle');
		$sheet->setCellValue('BU1', 'Factura deducible');
		$sheet->setCellValue('BV1', 'Usuario que creó el documento');
		$sheet->setCellValue('BW1', 'Último usuario que modificó el documento');
		$sheet->setCellValue('BX1', 'Almacén');
		$sheet->setCellValue('BY1', 'Importe neto exento');
		$sheet->setCellValue('BZ1', 'Porcentaje de descuento exento');
		$sheet->setCellValue('CA1', 'Importe de descuento exento');
		$sheet->setCellValue('CB1', 'Porcentaje pronto pago exento');
		$sheet->setCellValue('CC1', 'Importe de pronto pago exento');
		$sheet->setCellValue('CD1', 'Porcentaje de portes exento');
		$sheet->setCellValue('CE1', 'Importe de portes exento');
		$sheet->setCellValue('CF1', 'Porcentaje de financiación exento');
		$sheet->setCellValue('CG1', 'Importe de financiación exento');
		$sheet->setCellValue('CH1', 'Base imponible exenta');
		$sheet->setCellValue('CI1', 'Enviado por mail');
		$sheet->setCellValue('CJ1', 'Proveedor/Acreedor');
		$sheet->setCellValue('CK1', 'Bien de inversión');
		$sheet->setCellValue('CL1', 'Imagen');
		$sheet->setCellValue('CM1', 'Tipo de retención');
		$sheet->setCellValue('CN1', 'Clave de operación');
		$sheet->setCellValue('CO1', 'Fecha de operación');
		$sheet->setCellValue('CP1', 'Fecha de registro contable');
		$sheet->setCellValue('CQ1', 'Clave de operación intracomunitaria');
		$sheet->setCellValue('CR1', 'Régimen especial de caja');
		$sheet->setCellValue('CS1', 'Departamento');
		$sheet->setCellValue('CT1', 'Subdepartamento');

        $sheetDetails->setCellValue('A1', 'Tipo de documento');
		$sheetDetails->setCellValue('B1', 'Número de documento');
		$sheetDetails->setCellValue('C1', 'Posición de la línea');
		$sheetDetails->setCellValue('D1', 'Artículo');
		$sheetDetails->setCellValue('E1', 'Descripción');
		$sheetDetails->setCellValue('F1', 'Cantidad');
		$sheetDetails->setCellValue('G1', 'Porcentaje descuento 1');
		$sheetDetails->setCellValue('H1', 'Porcentaje descuento 2');
		$sheetDetails->setCellValue('I1', 'Porcentaje descuento 3');
		$sheetDetails->setCellValue('J1', 'Precio del artículo');
		$sheetDetails->setCellValue('K1', 'Total');
		$sheetDetails->setCellValue('L1', 'Campo uso interno');
		$sheetDetails->setCellValue('M1', 'Campo uso interno');
		$sheetDetails->setCellValue('N1', 'Tipo de IVA');
		$sheetDetails->setCellValue('O1', 'Tipo de documento');
		$sheetDetails->setCellValue('P1', 'Tipo de documento');
		$sheetDetails->setCellValue('Q1', 'Código de documento');
		$sheetDetails->setCellValue('R1', 'Ejercicio del que proviene la validación');
		$sheetDetails->setCellValue('S1', 'Alto');
		$sheetDetails->setCellValue('T1', 'Ancho');
		$sheetDetails->setCellValue('U1', 'Fondo');
		$sheetDetails->setCellValue('V1', 'Bultos');
		$sheetDetails->setCellValue('W1', 'Talla');
		$sheetDetails->setCellValue('X1', 'Color');
		$sheetDetails->setCellValue('Y1', 'Imagen asociada');     

        $rows = 2;
       	$rowsDetails = 2;
       	$realtotal = 0;
		
		$sheet->setCellValue('A' . $rows, date("Y")-2018);
        //$sheet->setCellValue('A' . $rows, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
        $sheet->setCellValue('B' . $rows, "001000");
        //$sheet->setCellValue('C' . $rows, substr($val->comments, 0, 50));
        $sheet->setCellValue('E' . $rows, date('Y-m-d H:i:s'));
        $sheet->setCellValue('G' . $rows, '0');
        $sheet->setCellValue('BX' . $rows, 'GEN');
        //$sheet->setCellValue('G' . $rows, $val->vendorFId);

        //$sheet->setCellValue('I' . $rows, $val->clientFId);
        $sheet->setCellValue('F' . $rows, '3');
        $sheet->setCellValue('I' . $rows, 'MAM MEDELLIN');

			//$sheet->setCellValue('J' . $rows, $val->client_name);
        //$sheet->setCellValue('K' . $rows, $val->client_address);
        //$sheet->setCellValue('O' . $rows, $val->client_idNum);
        $sheet->setCellValue('O' . $rows, "1");
        //$sheet->setCellValue('R' . $rows, empty($val->client_phone) ? $val->client_phone : $val->client_cellphone);       
        $sheet->setCellValue('BN' . $rows, '0'); 
        //$sheet->setCellValue('BO' . $rows, $val->comments);   

       	$rd = 2;
        foreach ($invoices as $val){
        	//echo $val->idInvoice."  ".$val->date." ".$val->clientFId." ".$val->client_name."<br>";

       		$realtotal += $val->total;

	        $details = $this->invoices_model->getDetails($val->idInvoice);
	        //echo $this->db->last_query()."<br>";
	        //echo sizeof($details)."<br>";
	        //foreach ($details as $det){
	        for($i = 0; $i < sizeof($details); $i++){
	        	$det = $details[$i];
	        	//echo "   ".$i;
        		//echo "      ". $det->productId."  ".$det->quantity." ".$det->unit." ".$det->subtotal."<br>";
	            switch ($val->storeId) {
		        	case 3:
		        	case 5:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2020);
	            		$sheetDetails->setCellValue('P' . $rowsDetails, date("Y")-2020);
		        	break;
		        	case 7:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2022);
	            		$sheetDetails->setCellValue('P' . $rowsDetails, date("Y")-2022);
		        		break;
		        	default:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2018);
	            		$sheetDetails->setCellValue('P' . $rowsDetails, date("Y")-2018);
		        		break;
		        }
	            //$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
	            $sheetDetails->setCellValue('B' . $rowsDetails, "001000");
	            $sheetDetails->setCellValue('Q' . $rowsDetails, "001000");

	            $sheetDetails->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetDetails->setCellValue('D' . $rowsDetails, $det->productId);
	            $sheetDetails->setCellValue('E' . $rowsDetails, $det->description);
	            $sheetDetails->setCellValue('F' . $rowsDetails, $det->quantity);
	            $sheetDetails->setCellValue('J' . $rowsDetails, $det->unit);
	            ////$sheetDetails->setCellValue('N' . $rowsDetails, $det->hasIva);
	            $sheetDetails->setCellValue('K' . $rowsDetails, $det->subtotal);
		        //$sheetDetails->setCellValue('P' . $rowsDetails, $det->base);
            	$sheetDetails->setCellValue('N' . $rows, "3");

	            $rowsDetails++;
	            $rd++;
	        } 

            //$rows++;
        } 
        $sheet->getStyle('R')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('BJ')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('BY')->getNumberFormat()->setFormatCode('#');

        $sheet->setCellValue('R' . $rows, (int)$realtotal, DataType::TYPE_NUMERIC);       
        $sheet->setCellValue('BJ' . $rows, (int)$realtotal, DataType::TYPE_NUMERIC);       
        $sheet->setCellValue('BY' . $rows, (int)$realtotal, DataType::TYPE_NUMERIC);


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

    public function exportRem(){
		$data  = array(
			'stores' => $this->stores_model->getStores(),  
		);
		$this->load->view("sisvent/commercial/invoices/exportrem",$data);
	}

	//public function createExcelRem($store, $from, $until) {
	public function createExcelRem() {

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

		$dat = uniqid('MAMRems', true);

		$fileName = 'ALB-'.$dat.'.xlsx';  
		$fileNameDetails = 'LAL-'.$dat.'.xlsx';  
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
		$sheet->setCellValue('H1', 'Código del proveedor');
		$sheet->setCellValue('I1', 'Código de cliente');
		$sheet->setCellValue('J1', 'Nombre del cliente');
		$sheet->setCellValue('K1', 'Domicilio del cliente');
		$sheet->setCellValue('L1', 'Población');
		$sheet->setCellValue('M1', 'Código postal');
		$sheet->setCellValue('N1', 'Provincia');
		$sheet->setCellValue('O1', 'N.I.F.');
		$sheet->setCellValue('P1', 'Tipo de IVA');
		$sheet->setCellValue('Q1', 'Recargo de equivalencia');
		$sheet->setCellValue('R1', 'Teléfono del cliente');
		$sheet->setCellValue('S1', 'Importe neto 1');
		$sheet->setCellValue('T1', 'Importe neto 2');
		$sheet->setCellValue('U1', 'Importe neto 3');
		$sheet->setCellValue('V1', 'Porcentaje de descuento 1');
		$sheet->setCellValue('W1', 'Porcentaje de descuento 2');
		$sheet->setCellValue('X1', 'Porcentaje de descuento 3');
		$sheet->setCellValue('Y1', 'Importe de descuento 1');
		$sheet->setCellValue('Z1', 'Importe de descuento 2');
		$sheet->setCellValue('AA1', 'Importe de descuento 3');
		$sheet->setCellValue('AB1', 'Porcentaje de pronto pago 1');
		$sheet->setCellValue('AC1', 'Porcentaje de pronto pago 2');
		$sheet->setCellValue('AD1', 'Porcentaje de pronto pago 3');
		$sheet->setCellValue('AE1', 'Importe pronto pago 1');
		$sheet->setCellValue('AF1', 'Importe pronto pago 2');
		$sheet->setCellValue('AG1', 'Importe pronto pago 3');
		$sheet->setCellValue('AH1', 'Porcentaje portes 1');
		$sheet->setCellValue('AI1', 'Porcentaje portes 2');
		$sheet->setCellValue('AJ1', 'Porcentaje portes 3');
		$sheet->setCellValue('AK1', 'Importe portes 1');
		$sheet->setCellValue('AL1', 'Importe portes 2');
		$sheet->setCellValue('AM1', 'Importe portes 3');
		$sheet->setCellValue('AN1', 'Porcentaje de financiación 1');
		$sheet->setCellValue('AO1', 'Porcentaje de financiación 2');
		$sheet->setCellValue('AP1', 'Porcentaje de financiación 3');
		$sheet->setCellValue('AQ1', 'Importe de financiación 1 ');
		$sheet->setCellValue('AR1', 'Importe de financiación 2');
		$sheet->setCellValue('AS1', 'Importe de financiación 3');
		$sheet->setCellValue('AT1', 'Base imponible 1');
		$sheet->setCellValue('AU1', 'Base imponible 2');
		$sheet->setCellValue('AV1', 'Base imponible 3');
		$sheet->setCellValue('AW1', 'Porcentaje IVA 1');
		$sheet->setCellValue('AX1', 'Porcentaje IVA 2');
		$sheet->setCellValue('AY1', 'Porcentaje IVA 3');
		$sheet->setCellValue('AZ1', 'Importe IVA 1');
		$sheet->setCellValue('BA1', 'Importe IVA 2');
		$sheet->setCellValue('BB1', 'Importe IVA 3');
		$sheet->setCellValue('BC1', 'Porcentaje de recargo de equivalencia 1');
		$sheet->setCellValue('BD1', 'Porcentaje de recargo de equivalencia 2');
		$sheet->setCellValue('BE1', 'Porcentaje de recargo de equivalencia 3');
		$sheet->setCellValue('BF1', 'Importe de recargo de equivalencia 1');
		$sheet->setCellValue('BG1', 'Importe de recargo de equivalencia 2');
		$sheet->setCellValue('BH1', 'Importe de recargo de equivalencia 3');
		$sheet->setCellValue('BI1', 'Porcentaje de la retención');
		$sheet->setCellValue('BJ1', 'Importe de la retención');
		$sheet->setCellValue('BK1', 'Total');
		$sheet->setCellValue('BL1', 'Forma de pago');
		$sheet->setCellValue('BM1', 'Portes');
		$sheet->setCellValue('BN1', 'Texto de portes');
		$sheet->setCellValue('BO1', '1ª línea de observaciones');
		$sheet->setCellValue('BP1', '2ª línea de observaciones');
		$sheet->setCellValue('BQ1', 'Obra de entrega');
		$sheet->setCellValue('BR1', 'Remitido por');
		$sheet->setCellValue('BS1', 'Embalado por');
		$sheet->setCellValue('BT1', 'A la atención de');
		$sheet->setCellValue('BU1', 'Referencia');
		$sheet->setCellValue('BV1', 'Nº de su pedido');
		$sheet->setCellValue('BW1', 'Fecha de su pedido');
		$sheet->setCellValue('BX1', 'Cobrado');
		$sheet->setCellValue('BY1', 'Traspasado a contabilidad');
		$sheet->setCellValue('BZ1', 'Impreso');
		$sheet->setCellValue('CA1', 'Transportista');
		$sheet->setCellValue('CB1', 'Número de expedición 1');
		$sheet->setCellValue('CC1', 'Número de expedición 2');
		$sheet->setCellValue('CD1', 'Anotaciones privadas');
		$sheet->setCellValue('CE1', 'Documentos externos asociados');
		$sheet->setCellValue('CF1', 'Banco del cliente');
		$sheet->setCellValue('CG1', 'Enviado a través del fichero');
		$sheet->setCellValue('CH1', 'Hora de creación');
		$sheet->setCellValue('CI1', 'Comentario después de las líneas de detalle');
		$sheet->setCellValue('CJ1', 'Usuario que creó el documento');
		$sheet->setCellValue('CK1', 'Último usuario que modificó el documento');
		$sheet->setCellValue('CL1', 'Fax');
		$sheet->setCellValue('CM1', 'Importe neto exento');
		$sheet->setCellValue('CN1', 'Porcentaje de descuento exento');
		$sheet->setCellValue('CO1', 'Importe de descuento exento');
		$sheet->setCellValue('CP1', 'Porcentaje pronto pago exento');
		$sheet->setCellValue('CQ1', 'Importe de pronto pago exento');
		$sheet->setCellValue('CR1', 'Porcentaje de portes exento');
		$sheet->setCellValue('CS1', 'Importe de portes 4');
		$sheet->setCellValue('CT1', 'Porcentaje de financiación exento');
		$sheet->setCellValue('CU1', 'Importe de financiación exento');
		$sheet->setCellValue('CV1', 'Base imponible exenta');
		$sheet->setCellValue('CW1', 'Enviado por mail');
		$sheet->setCellValue('CX1', 'Permisos y contraseña del documento');
		$sheet->setCellValue('CY1', 'Ticket, porcentaje de descuento');
		$sheet->setCellValue('CZ1', 'Ticket, importe de descuento');
		$sheet->setCellValue('DA1', 'Caja en que se creó el documento');
		$sheet->setCellValue('DB1', 'IBAN del banco');
		$sheet->setCellValue('DC1', 'BIC del banco');
		$sheet->setCellValue('DD1', 'Nombre del banco');
		$sheet->setCellValue('DE1', 'Entidad de la cuenta del cliente');
		$sheet->setCellValue('DF1', 'Oficina de la cuenta del cliente');
		$sheet->setCellValue('DG1', 'Dígitos de control de la cuenta del cliente');
		$sheet->setCellValue('DH1', 'Número de la cuenta del cliente');

        $sheetDetails->setCellValue('A1', 'Tipo de documento');
		$sheetDetails->setCellValue('B1', 'Número de documento');
		$sheetDetails->setCellValue('C1', 'Posición de la línea');
		$sheetDetails->setCellValue('D1', 'Artículo');
		$sheetDetails->setCellValue('E1', 'Descripción');
		$sheetDetails->setCellValue('F1', 'Cantidad');
		$sheetDetails->setCellValue('G1', 'Porcentaje descuento 1');
		$sheetDetails->setCellValue('H1', 'Porcentaje descuento 2');
		$sheetDetails->setCellValue('I1', 'Porcentaje descuento 3');
		$sheetDetails->setCellValue('J1', 'Precio del artículo');
		$sheetDetails->setCellValue('K1', 'Total');
		$sheetDetails->setCellValue('L1', 'Tipo de IVA');
		$sheetDetails->setCellValue('M1', 'Documento que creó el albarán');
		$sheetDetails->setCellValue('N1', 'Tipo de documento');
		$sheetDetails->setCellValue('O1', 'Código del documento');
		$sheetDetails->setCellValue('P1', 'Precio de costo');
		$sheetDetails->setCellValue('Q1', 'Bultos');
		$sheetDetails->setCellValue('R1', 'Comisión del agente');
		$sheetDetails->setCellValue('S1', 'Campo uso interno');
		$sheetDetails->setCellValue('T1', 'Ejercicio del que proviene la validación');
		$sheetDetails->setCellValue('U1', 'Alto');
		$sheetDetails->setCellValue('V1', 'Ancho');
		$sheetDetails->setCellValue('W1', 'Fondo');
		$sheetDetails->setCellValue('X1', 'Campo uso interno');
		$sheetDetails->setCellValue('Y1', 'Campo uso interno');
		$sheetDetails->setCellValue('Z1', 'IVA inlcuido en la línea');
		$sheetDetails->setCellValue('AA1', 'Precio IVA inluido en la línea');
		$sheetDetails->setCellValue('AB1', 'Total IVA inlcuido en la línea');
		$sheetDetails->setCellValue('AC1', 'Talla');
		$sheetDetails->setCellValue('AD1', 'Color');
		$sheetDetails->setCellValue('AE1', 'Imagen asociada');     

        $rows = 2;
       	$rowsDetails = 2;
       	$realtotal = 0;
       	
        $sheet->setCellValue('A' . $rows, date("Y")-2018);
        //$sheet->setCellValue('A' . $rows, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
        $sheet->setCellValue('B' . $rows, "1000");
        $sheet->setCellValue('C' . $rows, "MED - MAM - ONLINE");
        $sheet->setCellValue('D' . $rows, date('Y-m-d H:i:s'));
        $sheet->setCellValue('E' . $rows, '0');
        $sheet->setCellValue('F' . $rows, 'GEN');
        $sheet->setCellValue('G' . $rows, "2");

        $sheet->setCellValue('I' . $rows, "982");
        $sheet->setCellValue('J' . $rows, "MAM - ONLINE");
        //$sheet->setCellValue('BS' . "0");
        //$sheet->setCellValue('K' . $rows, $val->client_address);
        //$sheet->setCellValue('O' . $rows, $val->client_idNum);
        $sheet->setCellValue('P' . $rows, "0");
        $sheet->setCellValue('Q' . $rows, "0");
        //$sheet->setCellValue('R' . $rows, empty($val->client_phone) ? $val->client_phone : $val->client_cellphone);       
        //$sheet->setCellValue('S' . $rows, $val->total);       
        ////$sheet->setCellValue('AT' . $rows, $val->total);       
        //$sheet->setCellValue('BK' . $rows, $val->total);       
        $sheet->setCellValue('BX' . $rows, '0'); 
        $sheet->setCellValue('BY' . $rows, '0'); 
        //$sheet->setCellValue('CM' . $rows, $val->total);       
        //$sheet->setCellValue('BO' . $rows, $val->comments); 

       	$rd = 2;
        foreach ($invoices as $val){
        	//echo $val->idInvoice."  ".$val->date." ".$val->clientFId." ".$val->client_name."<br>";
       		
       		$realtotal += $val->total;

	        $details = $this->invoices_model->getDetails($val->idInvoice);
	        //echo $this->db->last_query()."<br>";
	        //echo sizeof($details)."<br>";
	        //foreach ($details as $det){
	        for($i = 0; $i < sizeof($details); $i++){
	        	$det = $details[$i];
	        	//echo "   ".$i;
        		//echo "      ". $det->productId."  ".$det->quantity." ".$det->unit." ".$det->subtotal."<br>";
	            switch ($val->storeId) {
		        	case 3:
		        	case 5:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2020);
	            		$sheetDetails->setCellValue('N' . $rowsDetails, date("Y")-2020);
		        	break;
		        	case 7:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2022);
	            		$sheetDetails->setCellValue('N' . $rowsDetails, date("Y")-2022);
		        		break;
		        	default:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2018);
	            		$sheetDetails->setCellValue('N' . $rowsDetails, date("Y")-2018);
		        		break;
		        }
	            //$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
	            $sheetDetails->setCellValue('B' . $rowsDetails, "1000");
	            $sheetDetails->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetDetails->setCellValue('D' . $rowsDetails, $det->productId);
	            $sheetDetails->setCellValue('E' . $rowsDetails, $det->description);
	            $sheetDetails->setCellValue('F' . $rowsDetails, $det->quantity);
	            $sheetDetails->setCellValue('J' . $rowsDetails, $det->unit);
	            $sheetDetails->setCellValue('K' . $rowsDetails, $det->subtotal);
		        $sheetDetails->setCellValue('L' . $rowsDetails, '3');
	            $sheetDetails->setCellValue('O' . $rowsDetails, "1000");
		        $sheetDetails->setCellValue('P' . $rowsDetails, $det->base);
	            $sheetDetails->setCellValue('Z' . $rowsDetails, $val->hasIva ? "0" : "1");

	            $rowsDetails++;
	            $rd++;
	        } 

            //$rows++;
        } 

        $sheet->getStyle('S')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('BK')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('CM')->getNumberFormat()->setFormatCode('#');

	    //$sheet->setCellValue('AT' . $rows, $val->total);       
        $sheet->setCellValueExplicit('S' . $rows, (int)$realtotal, DataType::TYPE_NUMERIC);       
	    $sheet->setCellValueExplicit('BK' . $rows, (int)$realtotal, DataType::TYPE_NUMERIC);
	    $sheet->setCellValueExplicit('CM' . $rows, (int)$realtotal, DataType::TYPE_NUMERIC);

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
        $sheet->setCellValue('AT1', 'Base');       
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
            switch ($val->storeId) {
	        	case 3:
	        	case 5:
            		$sheet->setCellValue('A' . $rows, date("Y")-2020);

	        	break;
	        	case 7:
            		$sheet->setCellValue('A' . $rows, date("Y")-2022);
	        		break;
	        	default:
            		$sheet->setCellValue('A' . $rows, date("Y")-2018);
	        		break;
	        }
            //$sheet->setCellValue('A' . $rows, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
            $sheet->setCellValue('B' . $rows, $val->idInvoice);
            $sheet->setCellValue('C' . $rows, substr($val->comments, 0, 50));
            $sheet->setCellValue('D' . $rows, date('Y-m-d H:i:s',strtotime($val->date))/*$val->date*/);
	        $sheet->setCellValue('E' . $rows, '0');
	        switch ($val->storeId) {
	        	case 1:
	        	case 5:
	        	case 7:
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
	        $sheet->setCellValue('AT' . $rows, $val->total);       
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
	            switch ($val->storeId) {
		        	case 3:
		        	case 5:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2020);
		        	break;
		        	case 7:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2022);
		        		break;
		        	default:
	            		$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-2018);
		        		break;
		        }
	            //$sheetDetails->setCellValue('A' . $rowsDetails, date("Y")-(($val->storeId == 3 || $val->storeId == 5) ? 2020 : 2018));
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
        $sheet->setCellValue('AT1', 'Base');       
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
	        $sheet->setCellValue('AT' . $rows, $val->total);       
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

    // ═══════════════════════════════════════════════════════════════════════
    // MÉTODOS DE TRACKING / RASTREO DE GUÍAS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * AJAX: Consultar estado de tracking de una guía
     * POST: invoice_id, tracking_number
     */
    public function checkTracking()
    {
        // Nota: No usamos CSRFVerify aquí porque es una operación de solo lectura
        // y ya está protegida por la sesión de usuario

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $invoiceId = $this->input->post('invoice_id');
        $trackingNumber = trim($this->input->post('tracking_number'));
        $carrier = $this->input->post('carrier') ?: 'interrapidisimo';

        // Validar parámetros
        if (empty($trackingNumber)) {
            echo json_encode(['success' => false, 'error' => 'Número de guía requerido']);
            return;
        }

        // Validar formato de guía (mínimo 8 dígitos para ser más flexible)
        if (!preg_match('/^[A-Za-z0-9]{8,20}$/', $trackingNumber)) {
            echo json_encode(['success' => false, 'error' => 'Formato de guía inválido (8-20 caracteres alfanuméricos)']);
            return;
        }

        try {
            $trackingInfo = false;

            // Usar librería específica según el carrier
            if ($carrier === 'interrapidisimo') {
                // Usar librería de Interrapidísimo (scraping directo)
                $this->load->library('interrapidisimo_tracker');
                $trackingInfo = $this->interrapidisimo_tracker->getStatus($trackingNumber);

                // Normalizar respuesta para formato consistente
                if ($trackingInfo && isset($trackingInfo['guia'])) {
                    $trackingInfo['tracking_number'] = $trackingInfo['guia'];
                    $trackingInfo['carrier_name'] = 'Interrapidísimo';
                    $trackingInfo['last_event'] = $trackingInfo['status_raw'] ?? '';
                }
            } else {
                // Para otros carriers, intentar con 17TRACK
                $this->load->library('tracking_service');
                $trackingInfo = $this->tracking_service->getStatus($trackingNumber, $carrier);
            }

            if ($trackingInfo === false) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No se pudo obtener información de la guía. Puede que aún no esté registrada en el sistema de la transportadora.'
                ]);
                return;
            }

            // Si tenemos invoice_id, actualizar la factura
            if (!empty($invoiceId)) {
                $updateData = [
                    'tracking_number' => $trackingNumber,
                    'tracking_status' => $trackingInfo['status'],
                    'tracking_location' => $trackingInfo['location'],
                    'tracking_carrier' => $carrier,
                    'tracking_last_update' => date('Y-m-d H:i:s')
                ];

                // Si está entregado, marcar fecha de entrega
                if ($trackingInfo['status'] === 'delivered') {
                    $updateData['delivered_at'] = date('Y-m-d H:i:s');
                }

                $this->invoices_model->updateTracking($invoiceId, $updateData);
            }

            echo json_encode([
                'success' => true,
                'tracking' => [
                    'guia' => $trackingNumber,
                    'status' => $trackingInfo['status'],
                    'status_label' => $trackingInfo['status_label'],
                    'location' => $trackingInfo['location'],
                    'last_event' => $trackingInfo['last_event'] ?? '',
                    'carrier_name' => $trackingInfo['carrier_name'] ?? '',
                    'last_update' => date('d/m/Y H:i'),
                    'events' => $trackingInfo['events'] ?? []
                ]
            ]);

        } catch (Exception $e) {
            log_message('error', 'Error consultando tracking: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Error al consultar el estado de la guía: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Guardar número de guía en una factura
     * POST: invoice_id, tracking_number, tracking_carrier
     */
    public function saveTracking()
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $invoiceId = $this->input->post('invoice_id');
        $trackingNumber = trim($this->input->post('tracking_number'));
        $trackingCarrier = $this->input->post('tracking_carrier') ?: 'interrapidisimo';

        if (empty($invoiceId)) {
            echo json_encode(['success' => false, 'error' => 'ID de factura requerido']);
            return;
        }

        // Verificar que la factura existe
        $invoice = $this->invoices_model->getInvoice($invoiceId);
        if (!$invoice) {
            echo json_encode(['success' => false, 'error' => 'Factura no encontrada']);
            return;
        }

        $updateData = [
            'tracking_carrier' => $trackingCarrier
        ];

        if (!empty($trackingNumber)) {
            // Validar formato
            if (!preg_match('/^\d{10,15}$/', $trackingNumber)) {
                echo json_encode(['success' => false, 'error' => 'Formato de guía inválido']);
                return;
            }

            $updateData['tracking_number'] = $trackingNumber;
            $updateData['tracking_status'] = 'pending';

            // Si es nuevo tracking, marcar fecha de envío
            if (empty($invoice->tracking_number)) {
                $updateData['shipped_at'] = date('Y-m-d H:i:s');
            }
        } else {
            // Limpiar tracking
            $updateData['tracking_number'] = null;
            $updateData['tracking_status'] = null;
            $updateData['tracking_location'] = null;
            $updateData['shipped_at'] = null;
        }

        if ($this->invoices_model->updateTracking($invoiceId, $updateData)) {
            $this->logs_model->logMessage("info", "Usuario " . $this->session->userdata('user_data')['uname'] . " actualizó tracking de factura " . $invoiceId . ": " . $trackingNumber);

            echo json_encode([
                'success' => true,
                'message' => !empty($trackingNumber) ? 'Guía guardada correctamente' : 'Guía eliminada',
                'tracking_number' => $trackingNumber,
                'tracking_carrier' => $trackingCarrier
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar la guía']);
        }
    }

    /**
     * Obtener facturas con tracking activo para el vendedor actual
     * Útil para panel de seguimiento
     */
    public function myTracking()
    {
        $vendorId = $this->session->userdata('user_data')['uname'];
        $status = $this->input->get('status');

        $invoices = $this->invoices_model->getInvoicesByVendorWithTracking($vendorId, $status);
        $summary = $this->invoices_model->getTrackingSummaryByVendor($vendorId);

        $data = [
            'invoices' => $invoices,
            'summary' => $summary,
            'status_filter' => $status
        ];

        // Por ahora retornar JSON, luego se puede crear una vista
        echo json_encode($data);
    }
}