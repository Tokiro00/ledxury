<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Libro Diario (Journal Book)
 * Lists all accounting entries with filters
 */
class Entries extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // Admin
        $this->load->model("entry_model");
        $this->load->model("stores_model");
    }

    public function index()
    {
        $page = $this->input->get('p') ?: 1;
        $limit = 50;

        // Filtros
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to = $this->input->get('to') ?: date('Y-m-d');
        $storeId = $this->input->get('store');
        $type = $this->input->get('type');

        // Construir filtros para la consulta
        $filters = array(
            'from' => $from,
            'to' => $to,
            'store' => $storeId,
            'type' => $type
        );

        $total = $this->entry_model->getTotalEntriesFiltered($filters);
        $last = ceil($total / $limit);

        if ($page > $last) $page = max($last, 1);
        if ($page <= 0) $page = 1;

        $entries = $this->entry_model->getEntriesFiltered($filters, $page, $limit);

        // Calcular totales del período
        $totals = $this->entry_model->getTotalsByDateRange($from, $to, $storeId);

        $data = array(
            'entries' => $entries,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'stores' => $this->stores_model->getStores(),
            'filter_from' => $from,
            'filter_to' => $to,
            'filter_store' => $storeId,
            'filter_type' => $type,
            'totalDebit' => $totals->totalDebit ?: 0,
            'totalCredit' => $totals->totalCredit ?: 0
        );
        $this->load->view("sisvent/accounting/entries/list", $data);
    }

    /**
     * View single entry details
     */
    public function view($id)
    {
        $entry = $this->entry_model->getEntryWithDetails($id);

        if (!$entry) {
            redirect(base_url() . 'sisvent/accounting/entries');
        }

        $data = array(
            'entry' => $entry
        );
        $this->load->view("sisvent/accounting/entries/view", $data);
    }
}
