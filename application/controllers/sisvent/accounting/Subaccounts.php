<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subaccounts extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]);
        $this->load->model("noinvoices_model");
        $this->load->model("nopayments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
    }

    public function index()
    {
        $page = $this->input->get('p');
        
        $limit = 50;
        if(!$page)
            $page = 1;

        $total = $this->nopayments_model->getTotal();
        $last       = ceil( $total / $limit );

        if($page > $last)
            $page = $last;

        if($page <= 0)
            $page = 1;

        $data  = array(
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'payments' => $this->nopayments_model->getPayments($page, $limit), 
        );
        $this->load->view("sisvent/admin/nopayments/list",$data);
        
    }

    
}