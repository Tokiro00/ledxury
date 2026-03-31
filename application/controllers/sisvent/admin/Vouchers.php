<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vouchers extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('cartera');
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
    }

	public function index()
	{
		$page = $this->input->get('p') ?: 1;
		$limit = 50;

		// Build filters
		$filters = array();
		if ($this->input->get('vendor')) {
			$filters['vendor'] = $this->input->get('vendor');
		}
		if ($this->input->get('state')) {
			$filters['state'] = $this->input->get('state');
		}
		if ($this->input->get('from')) {
			$filters['from'] = $this->input->get('from');
		}
		if ($this->input->get('to')) {
			$filters['to'] = $this->input->get('to');
		}

		$total = $this->vouchers_model->getTotal($filters);
		$last = ceil($total / $limit);

		if ($page > $last) $page = $last;
		if ($page <= 0) $page = 1;

		$data = array(
			'page' => $page,
			'total' => $total,
			'limit' => $limit,
			'vouchers' => $this->vouchers_model->getFilteredVouchers($page, $limit, $filters),
			'summary' => $this->vouchers_model->getVouchersSummaryByVendor($filters),
			'grandTotal' => $this->vouchers_model->getVouchersGrandTotal($filters),
			'vendors' => $this->vendors_model->getVendors(),
			'filters' => $filters
		);
		$this->load->view("sisvent/admin/vouchers/list", $data);
	}

	public function search($term)
	{
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->vouchers_model->getTotalSearch($term);
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
			'vouchers' => $this->vouchers_model->searchByWord($term, $page, $limit), 
		);
		$this->load->view("sisvent/admin/vouchers/list",$data);
		
	}

	public function add(){

		$data =array( 
			'vendors' => $this->vendors_model->getVendors(),
			'methods' => $this->payments_model->getPaymentMethods(), 
		);
		$this->load->view("sisvent/admin/vouchers/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("vendor");
		$payment = $this->input->post("payment");
		$comment = $this->input->post("comments");
		$method = $this->input->post("method");
		$date = $this->input->post("date");

		$invoice = $this->invoices_model->getInvoice($idInvoice);

		$data  = array(
			'userId' => $vendor,
			'value' => $payment,
			'paymentMethod' => $method,
			'description' => $comment,
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			//'date' => ($date),
			'state' => 1,
		);

		$this->vouchers_model->save($data);

		redirect(base_url()."sisvent/admin/vouchers");
	}

	public function edit($voucher_id){
		$data =array( 
			'voucher' => $this->vouchers_model->getVoucher($voucher_id),
			'methods' => $this->payments_model->getPaymentMethods(), 
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
		$method = $this->input->post("method");
		$date = $this->input->post("date");
		
		$data  = array(
			'value' => $payment,
			'paymentMethod' => $method,
			'date' => date('Y-m-d H:i:s',strtotime($date)),
			//'date' => ($date),
			'description' => $comment
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

	public function detailed(){
		$data  = array(
			'vendors' => $this->vendors_model->getVendors(),
		);
		$this->load->view("sisvent/admin/vouchers/detailed",$data);
		//$this->load->view("sisvent/store/inventory/index",$data);
	}

	public function getDetail(){
		
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$user_id = $this->input->post("user");
		$since = $this->input->post("since");
		$until = $this->input->post("until");
		$data =array( 
			'vouchers' => $this->vouchers_model->getVendorVouchers($user_id,$since,$until)
		);
		
		//redirect(base_url()."sisvent/admin/vouchers");
		$this->load->view("sisvent/admin/vouchers/detail",$data);
	}

	public function delete($voucher_id){
		
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->vouchers_model->remove($voucher_id);
		//redirect(base_url()."sisvent/admin/vouchers");
		echo base_url()."sisvent/admin/vouchers";
	}
	
}