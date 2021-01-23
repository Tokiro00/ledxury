<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vouchers extends CI_Controller {

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
			'vouchers' => $this->vouchers_model->getVouchers(), 
		);
		$this->load->view("sisvent/admin/vouchers/list",$data);
		
	}

	public function add(){

		$data =array( 
			'vendors' => $this->vendors_model->getVendors(),
		);
		$this->load->view("sisvent/admin/vouchers/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("vendor");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comments");

		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'userId' =>$vendor,
			'value' =>$payment,
			'description' =>$comment,
			'state' => 1,
		);

		$this->vouchers_model->save($data);

		redirect(base_url()."sisvent/admin/vouchers");
	}

	public function edit($voucher_id){
		$data =array( 
			'voucher' => $this->vouchers_model->getVoucher($voucher_id)
		);
		//print_r($data);
		$this->load->view("sisvent/admin/vouchers/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$voucher_id = $this->input->post("voucher_id");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comments");
		
		$data  = array(
			'value' =>$payment,
			'description' =>$comment
		);

		if ($this->vouchers_model->update($voucher_id,$data)) {
			redirect(base_url()."sisvent/admin/vouchers");
		}
		else{
			$data =array( 
				'voucher' => $this->vouchers_model->getVoucher($voucher_id)
			);
			$this->session->set_flashdata("error","No se pudo actualizar la información");
			$this->load->view("sisvent/admin/vouchers/edit",$data);
			//redirect(base_url()."sisvent/admin/vouchers/edit/".$voucher_id);
		}
		
	}

	public function delete($voucher_id){
		
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->vouchers_model->remove($voucher_id);
		//redirect(base_url()."sisvent/admin/vouchers");
		echo base_url()."sisvent/admin/vouchers";
	}
	
}