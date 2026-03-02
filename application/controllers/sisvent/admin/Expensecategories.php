<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expensecategories extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('gastos');
        $this->load->model('expensecategories_model');
        $this->load->model('Subaccount_model');
    }

    // ========================================================================
    // LISTADO
    // ========================================================================

    public function index()
    {
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->expensecategories_model->getTotal();
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'categories' => $this->expensecategories_model->getCategories($page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit
        );

        $this->load->view('sisvent/admin/expensecategories/list', $data);
    }

    public function search($term)
    {
        $term = str_replace("%20", " ", $term);
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->expensecategories_model->getTotalSearch($term);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'categories' => $this->expensecategories_model->searchByWord($term, $page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search_term' => $term
        );

        $this->load->view('sisvent/admin/expensecategories/list', $data);
    }

    // ========================================================================
    // CREAR
    // ========================================================================

    public function add()
    {
        $data = array(
            'subaccounts' => $this->Subaccount_model->getExpenseSubaccounts()
        );
        $this->load->view('sisvent/admin/expensecategories/add', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $code = $this->input->post('code');
        $name = $this->input->post('name');
        $description = $this->input->post('description');
        $accountingSubaccountId = $this->input->post('accounting_subaccount_id');

        $this->form_validation->set_rules('code', 'Código', 'required|max_length[20]');
        $this->form_validation->set_rules('name', 'Nombre', 'required|max_length[100]');

        if ($this->form_validation->run()) {

            if ($this->expensecategories_model->codeExists($code)) {
                $this->session->set_flashdata('error', 'El código ya existe. Por favor use otro.');
                redirect(base_url() . 'sisvent/admin/expensecategories/add');
            }

            // Derivar accounting_account_id desde la subcuenta seleccionada
            $accountingAccountId = null;
            if ($accountingSubaccountId) {
                $sub = $this->Subaccount_model->getSubaccount($accountingSubaccountId);
                if ($sub) {
                    $accountingAccountId = $sub->accountAccount;
                }
            }

            $data = array(
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'accounting_account_id' => $accountingAccountId,
                'accounting_subaccount_id' => $accountingSubaccountId ?: null,
                'is_active' => 1
            );

            if ($this->expensecategories_model->save($data)) {
                redirect(base_url() . 'sisvent/admin/expensecategories');
            } else {
                $this->session->set_flashdata('error', 'No se pudo crear la categoría');
                redirect(base_url() . 'sisvent/admin/expensecategories/add');
            }
        } else {
            $this->add();
        }
    }

    // ========================================================================
    // EDITAR
    // ========================================================================

    public function edit($id)
    {
        $category = $this->expensecategories_model->getCategory($id);
        if (!$category) {
            redirect(base_url() . 'sisvent/admin/expensecategories');
        }

        $data = array(
            'category' => $category,
            'subaccounts' => $this->Subaccount_model->getExpenseSubaccounts()
        );

        $this->load->view('sisvent/admin/expensecategories/edit', $data);
    }

    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $id = $this->input->post('id');
        $code = $this->input->post('code');
        $name = $this->input->post('name');
        $description = $this->input->post('description');
        $accountingSubaccountId = $this->input->post('accounting_subaccount_id');
        $isActive = $this->input->post('is_active') ? 1 : 0;

        $this->form_validation->set_rules('code', 'Código', 'required|max_length[20]');
        $this->form_validation->set_rules('name', 'Nombre', 'required|max_length[100]');

        if ($this->form_validation->run()) {

            if ($this->expensecategories_model->codeExists($code, $id)) {
                $this->session->set_flashdata('error', 'El código ya existe. Por favor use otro.');
                redirect(base_url() . 'sisvent/admin/expensecategories/edit/' . $id);
            }

            // Derivar accounting_account_id desde la subcuenta seleccionada
            $accountingAccountId = null;
            if ($accountingSubaccountId) {
                $sub = $this->Subaccount_model->getSubaccount($accountingSubaccountId);
                if ($sub) {
                    $accountingAccountId = $sub->accountAccount;
                }
            }

            $data = array(
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'accounting_account_id' => $accountingAccountId,
                'accounting_subaccount_id' => $accountingSubaccountId ?: null,
                'is_active' => $isActive
            );

            if ($this->expensecategories_model->update($id, $data)) {
                redirect(base_url() . 'sisvent/admin/expensecategories');
            } else {
                $this->session->set_flashdata('error', 'No se pudo actualizar la categoría');
                redirect(base_url() . 'sisvent/admin/expensecategories/edit/' . $id);
            }
        } else {
            $this->edit($id);
        }
    }

    // ========================================================================
    // ELIMINAR
    // ========================================================================

    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $this->expensecategories_model->remove($id);
        echo base_url() . 'sisvent/admin/expensecategories';
    }
}
