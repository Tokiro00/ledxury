<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subaccounts extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]);
        $this->load->model("account_model");
        $this->load->model("subaccount_model");
    }

    public function index()
    {
        $data  = array(
            'subaccounts' => $this->subaccount_model->getSubaccounts(), 
        );
        $this->load->view("sisvent/accounting/subaccounts/list",$data);
        
    }

    public function add(){

        $data =array( 
            'accounts' => $this->account_model->getAccounts(),
            'accountside' => $this->subaccount_model->getAccountSides(),
            'accountstatement' => $this->subaccount_model->getAccountStatements()
        );
        $this->load->view("sisvent/accounting/subaccounts/add", $data);
    }

    public function store(){
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

        $subaccount_id = $this->input->post("subaccount_id");
        $account_id = $this->input->post("account_id");
        $name = $this->input->post("name");
        $account_balance = $this->input->post("account_balance");
        $account_side = $this->input->post("account_side");
        $account_statement = $this->input->post("account_statement");
        
        $this->form_validation->set_rules("subaccount_id","Nombre","is_unique[subaccounts.accountID]|required");
        $this->form_validation->set_rules("name","Nombre","required");
        
        if ($this->form_validation->run()) {
            $data  = array(
                'accountID' => $subaccount_id,
                'accountAccount' => $account_id,
                'accountName' => $name,
                'accountBalance' => $account_balance,
                'accountSide' => $account_side,
                'accountStatement' => $account_statement
            );
            switch ($data['accountSide']) {
                case 1:
                    $data['accountDebit']  = $data['accountBalance'];
                    $data['accountCredit'] = 0;
                    break;

                default:
                    $data['accountDebit']  = 0;
                    $data['accountCredit'] = $data['accountBalance'];
                    break;
            }

            if ($this->subaccount_model->save($data)) {
                redirect(base_url()."sisvent/accounting/subaccounts");
            }
            else{
                $this->session->set_flashdata("error","No se pudo guardar la información");
                redirect(base_url()."sisvent/accounting/subaccounts/add");
            }
        }
        else{
            $this->add();
        }
    }

    public function edit($subaccount_id){
        $data =array( 
            'subaccount' => $this->subaccount_model->getSubaccount($subaccount_id),
            'accounts' => $this->accountgroup_model->getAccounts(),
            'accountside' => $this->subaccount_model->getAccountSides(),
            'accountstatement' => $this->subaccount_model->getAccountStatements()
        );
        //print_r($data);
        $this->load->view("sisvent/accounting/subaccounts/edit",$data);
    }

    public function update(){
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

        $subaccount_id = $this->input->post("subaccount_id");
        $account_id = $this->input->post("account_id");
        $name = $this->input->post("name");
        $account_balance = $this->input->post("account_balance");
        $account_side = $this->input->post("account_side");
        $account_statement = $this->input->post("account_statement");
        
        $this->form_validation->set_rules("name","Nombre","required");
        
        if ($this->form_validation->run()) {
            
            $data  = array(
                'accountAccount' => $account_id,
                'accountName' => $name,
                'accountBalance' => $account_balance,
                'accountSide' => $account_side,
                'accountStatement' => $account_statement
            );

            if ($this->subaccount_model->update($subaccount_id,$data)) {
                redirect(base_url()."sisvent/accounting/subaccounts");
            }
            else{
                $this->session->set_flashdata("error","No se pudo actualizar la información");
                redirect(base_url()."sisvent/accounting/subaccounts/edit/".$subaccount_id);
            }
        }
        else{
            $this->edit($subaccount_id);
        }
    }

    public function delete($subaccount_id){
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
        
        $this->subaccount_model->remove($subaccount_id);
        //redirect(base_url()."sisvent/accounting/subaccounts");
        echo base_url()."sisvent/accounting/subaccounts";
    }
    
}