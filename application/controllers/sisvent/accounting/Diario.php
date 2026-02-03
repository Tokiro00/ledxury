<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Diario extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]);
        $this->load->model('entry_model');
    }

    public function index()
    {
        $from    = $this->input->get('from');
        $to      = $this->input->get('to');
        $storeId = $this->input->get('store');
        $page    = $this->input->get('p');

        if (!$from) $from = date('Y-m-01');
        if (!$to)   $to   = date('Y-m-d');
        if (!$page) $page = 1;

        $limit = 50;
        $total = $this->entry_model->getTotalByDateRange($from, $to, $storeId);
        $last  = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0)    $page = 1;

        $entries = $this->entry_model->getEntriesByDateRange($from, $to, $storeId, $page, $limit);
        $totals  = $this->entry_model->getTotalsByDateRange($from, $to, $storeId);

        $data = array(
            'entries'      => $entries,
            'from'         => $from,
            'to'           => $to,
            'storeId'      => $storeId,
            'page'         => $page,
            'total'        => $total,
            'limit'        => $limit,
            'totalDebit'   => $totals ? (float)$totals->totalDebit : 0,
            'totalCredit'  => $totals ? (float)$totals->totalCredit : 0
        );

        $this->load->view('sisvent/accounting/diario/list', $data);
    }
}