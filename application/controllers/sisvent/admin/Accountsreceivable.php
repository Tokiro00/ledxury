<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accountsreceivable extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('cartera');
        $this->load->model('invoices_model');
        $this->load->model('clients_model');
        $this->load->model('stores_model');
        $this->load->model('users_model');
    }

    /**
     * List all accounts receivable with aging report
     */
    public function index()
    {
        $page = $this->input->get('p') ?: 1;
        $limit = 50;

        // Filters
        $clientId = $this->input->get('client') ?: null;
        $storeId = $this->input->get('store') ?: null;
        $vendorId = $this->input->get('vendor') ?: null;

        // Get aging summary
        $aging = $this->invoices_model->getAccountsReceivableAging($clientId, $storeId, $vendorId);

        // Get paginated receivables
        $total = $this->invoices_model->getTotalAccountsReceivable($clientId, $storeId, $vendorId);
        $last = ceil($total / $limit);
        if ($page > $last && $last > 0) $page = $last;
        if ($page <= 0) $page = 1;

        $receivables = $this->invoices_model->getAccountsReceivable($clientId, $storeId, $vendorId, $page, $limit);

        // Get filter options
        $clients = $this->clients_model->getClients();
        $stores = $this->stores_model->getStores();
        $vendors = $this->users_model->getUsers(false);

        $data = array(
            'receivables' => $receivables,
            'aging' => $aging,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'clients' => $clients,
            'stores' => $stores,
            'vendors' => $vendors,
            'filter_client' => $clientId,
            'filter_store' => $storeId,
            'filter_vendor' => $vendorId
        );

        $this->load->view('sisvent/admin/accountsreceivable/list', $data);
    }

    /**
     * View receivables grouped by client
     */
    public function byClient()
    {
        $storeId = $this->input->get('store') ?: null;
        $vendorId = $this->input->get('vendor') ?: null;

        // Get aging summary
        $aging = $this->invoices_model->getAccountsReceivableAging(null, $storeId, $vendorId);

        // Get receivables by client
        $clients_receivables = $this->invoices_model->getAccountsReceivableByClient($storeId, $vendorId);

        // Get filter options
        $stores = $this->stores_model->getStores();
        $vendors = $this->users_model->getUsers(false);

        $data = array(
            'clients_receivables' => $clients_receivables,
            'aging' => $aging,
            'stores' => $stores,
            'vendors' => $vendors,
            'filter_store' => $storeId,
            'filter_vendor' => $vendorId
        );

        $this->load->view('sisvent/admin/accountsreceivable/by_client', $data);
    }

    /**
     * View details for a specific client
     */
    public function clientDetail($clientId)
    {
        if (empty($clientId)) {
            redirect('sisvent/admin/accountsreceivable');
        }

        // Get client info
        $client = $this->clients_model->getClient($clientId);
        if (empty($client)) {
            $this->session->set_flashdata('error', 'Cliente no encontrado');
            redirect('sisvent/admin/accountsreceivable');
        }

        // Get all pending invoices for this client
        $receivables = $this->invoices_model->getAccountsReceivable($clientId, null, null, 1, 1000);

        // Get aging for this client
        $aging = $this->invoices_model->getAccountsReceivableAging($clientId);

        $data = array(
            'client' => $client,
            'receivables' => $receivables,
            'aging' => $aging
        );

        $this->load->view('sisvent/admin/accountsreceivable/client_detail', $data);
    }

    /**
     * AJAX endpoint for getting client receivables data
     */
    public function getClientReceivables()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Direct access not allowed', 403);
        }

        $clientId = $this->input->post('client_id');

        if (empty($clientId)) {
            echo json_encode(array('success' => false, 'message' => 'ID de cliente requerido'));
            return;
        }

        $receivables = $this->invoices_model->getAccountsReceivable($clientId, null, null, 1, 100);
        $aging = $this->invoices_model->getAccountsReceivableAging($clientId);

        echo json_encode(array(
            'success' => true,
            'receivables' => $receivables,
            'aging' => $aging
        ));
    }
}
