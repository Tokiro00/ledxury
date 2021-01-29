<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settlements extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1,2]);
        $this->load->model("expenses_model");
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
    }

	public function index()
	{

		$vendors = $this->vendors_model->getVendors();
		foreach ($vendors as $vendor){
			$vendor->settlement = getVendorSettlement($vendor->idUser);
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
	
	public function approve($vendor){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$invoices = $this->invoices_model->getVendorPaidInvoices($vendor);
		$total = 0;
		$inv = "Facturas:";
		$vou = "Vales:";
		foreach ($invoices as $key => $invoice) {

			$data  = array(
				'state' => 3,
			);

			$this->invoices_model->update($invoice->idInvoice,$data);
			$inv .= " (".$invoice->idInvoice.")"; 
			if($invoice->clientId == $vendor)
			{

				if($invoice->hasIva)
				{
					$total -= ($invoice->total * ($invoice->iva/100));
				}else
				{
					$details = $this->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						$total -= ($detail->subtotal - ($detail->quantity * $detail->price_base));
					}
				}
			}else
			{
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $this->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						$total += ($detail->subtotal - ($detail->quantity * $detail->price_base));
					}
				}
			}
		}

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
		
		$total -= $vtotal;
		
		if($total < 0)
		{
			$data  = array(
				'userId' => $vendor,
				'value' => abs($total),
				'description' => "Faltante después de liquidación",
				'state' => 1,
			);

			$this->vouchers_model->save($data);
		}else
		{
			$user = $this->vendors_model->getVendor($vendor);
			$data  = array(
				'value' => $total,
				'description' => "Liquidación de ".$user->name." ".$inv." ".$vou,
			);

			$this->expenses_model->save($data);
		}
		//print_r($data);

		
		echo base_url()."sisvent/admin/settlements";
		

	}
}