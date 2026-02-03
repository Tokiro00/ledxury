<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Entries extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("entry_model");
    }

    public function index()
	{
		$page = $this->input->get('p') ?: 1;
		$limit = 50;

		$total = $this->entry_model->getTotalEntries();
		$last = ceil($total / $limit);

		if ($page > $last) $page = $last;
		if ($page <= 0) $page = 1;

		$data = array(
			'entries' => $this->entry_model->getEntries($page, $limit),
			'page' => $page,
			'total' => $total,
			'limit' => $limit
		);
		$this->load->view("sisvent/accounting/entries/list", $data);
	}

}