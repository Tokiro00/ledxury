<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settlements extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        $this->load->model("expenses_model");
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("users_model");
		$this->load->model("vendors_model");
    }

	public function index()
	{

		$this->backend_lib->control([1,2]);

		$user = $this->users_model->getUser($this->session->userdata('user_data')['uname']); 
		$user->admin_store_arr = explode(',', $user->admin_store);

		$vendors = $this->vendors_model->getVendors($user->admin_store_arr);
		foreach ($vendors as $vendor){
			$s_temp = getVendorSettlement($vendor->idUser);
			$st_temp = getVendorTotalSettlement($vendor->idUser);
			$vendor->settlement = $s_temp->total;
			$vendor->alert = $s_temp->alert;
			$vendor->totalSettlement = $st_temp->total;
			$vendor->totalalert = $st_temp->alert;
			$vendor->possibleSettlement = getVendorPossibleSettlement($vendor->idUser)->total;
		}

		$data  = array(
			'settlements' => $vendors, 
		);
		$this->load->view("sisvent/admin/settlements/list",$data);
		
	}
	
	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'html' => getVendorSettlementView($vendor), 
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/admin/settlements/view",$data);
	}

	public function view2($vendor){
		$this->outh_model->CSRFVerify();

		//$vendor = $this->input->post("id");
		/*$data  = array(
			'vendor' => $this->vendors_model->getVendor($vendor),
			//'html' => $this->invoices_model->getVendorPaidInvoices2($vendor), 
		);
		$totalMonthInvoices = $this->invoices_model->getVendorTotalInvoicesSince($vendor,date('Y-m-01 00:00:00'));
		echo "<pre>";
		print_r($totalMonthInvoices);
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
		echo "<br>";
		$totalPaidMonthInvoices = $this->payments_model->getVendorTotalPaymentsSince($vendor,date('Y-m-01 00:00:00'));
		echo "<pre>";
		print_r($totalPaidMonthInvoices);
		echo "</pre>";
		print_r($this->db->last_query());
		echo "<br>";*/

		//$this->load->view("sisvent/admin/settlements/view",$data);
		echo "<pre>";
		print_r(getVendorSettlementView($vendor));
		echo "</pre>";
		echo "<br>";
		//print_r($this->db->last_query());
	}

	public function viewtotal(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'html' => getVendorSettlementTotalView($vendor), 
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/admin/settlements/view",$data);
	}
	
	public function approve($vendor){
		$this->backend_lib->control([1,2]);
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$invoices = $this->invoices_model->getVendorPaidInvoices($vendor);
		$total = 0;
		$inv = "Facturas:";
		$desc = "Descuento:";
		$ecom = "e-commerce:";
		$ivainv = "IVA:";
		$vou = "Vales:";
		foreach ($invoices as $key => $invoice) {

			$data  = array(
				'state' => 3,
			);
			$this->invoices_model->update($invoice->idInvoice,$data);
			if(!$invoice->blacklisted)
			{
				$details = $this->invoices_model->getDetails($invoice->idInvoice);
				if($invoice->clientId == $vendor)
				{
					if($invoice->discount > 0)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= ($invoice->total - $not_settle_total - $invoice->discount) * (0.1);
						$desc .= " (".$invoice->idInvoice.")"; 
					}else if($invoice->e_commerce)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= ($invoice->total - $not_settle_total) * (0.15);
						$ecom .= " (".$invoice->idInvoice.")"; 
					}else
					if($invoice->hasIva)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= (($invoice->total - $not_settle_total) * ($invoice->iva/100));
						$ivainv .= " (".$invoice->idInvoice.")"; 
					}else
					{
						$inv .= " (".$invoice->idInvoice.")"; 
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								continue;
							}
							$total -= ($detail->subtotal - ($detail->quantity * $detail->base));
						}
					}
				}else
				{
					if($invoice->discount > 0)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total += ($invoice->total - $not_settle_total - $invoice->discount) * (0.1);
						$desc .= " (".$invoice->idInvoice.")"; 
					}else if($invoice->e_commerce)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total += ($invoice->total - $not_settle_total) * (0.15);
						$ecom .= " (".$invoice->idInvoice.")"; 
					}else
					if($invoice->hasIva)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total += ($invoice->total - $not_settle_total) * ($invoice->iva/100);
						$ivainv .= " (".$invoice->idInvoice.")"; 
					}else
					{
						//$details = $this->invoices_model->getDetails($invoice->idInvoice);
						$inv .= " (".$invoice->idInvoice.")"; 
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								continue;
							}
							$total += ($detail->subtotal - ($detail->quantity * $detail->base));
						}
					}
				}
			}
		}

		//print_r("Total: ".$total);
		$vouchers = $this->vouchers_model->getVendorPaidVouchers($vendor);
		$vtotal = 0;

		foreach($vouchers as $key => $voucher){
			$data  = array(
				'state' => 2
			);

			$this->vouchers_model->update($voucher->idVoucher,$data);
			$vtotal += ($voucher->value);
			$vou .= " (".$voucher->idVoucher.")"; 
		}
		//print_r("Voucher: ".$total);
		
		$total -= $vtotal;
		
		if($total < 0)
		{
			$user = $this->vendors_model->getVendor($vendor);
			$data  = array(
				'vendorId' => $vendor,
				'value' => $total,
				'description' => "Liquidación de ".$user->name." ".$inv." ".$ivainv." ".$desc." ".$ecom." ".$vou,
			);

			$this->expenses_model->save($data);

			$idExpenses = $this->db->insert_id();

			$data  = array(
				'userId' => $vendor,
				'value' => abs($total),
				'paymentMethod' => 4,
				'description' => "Faltante después de liquidación  - Liquidación ".$idExpenses,
				'state' => 1,
			);

			$this->vouchers_model->save($data);
		}else
		{
			$user = $this->vendors_model->getVendor($vendor);
			$data  = array(
				'vendorId' => $vendor,
				'value' => $total,
				'description' => "Liquidación de ".$user->name." ".$inv." ".$ivainv." ".$desc." ".$ecom." ".$vou,
			);

			$this->expenses_model->save($data);
		}
		//print_r($data);

		
		echo base_url()."sisvent/admin/settlements";
		

	}
}