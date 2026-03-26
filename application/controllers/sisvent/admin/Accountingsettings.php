<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accountingsettings extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('config_contable');
        $this->load->model('accountingsettings_model');
        $this->load->model('subaccount_model');
    }

    // ========================================================================
    // VISTA PRINCIPAL
    // ========================================================================

    public function index()
    {
        $data = array(
            'settings' => $this->accountingsettings_model->getSettings(),
            'subaccounts' => $this->subaccount_model->getSubaccounts()
        );

        $this->load->view('sisvent/admin/accountingsettings/index', $data);
    }

    // ========================================================================
    // GUARDAR CONFIGURACION
    // ========================================================================

    public function save()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $settings = $this->input->post('settings');

        if ($settings && is_array($settings)) {
            $this->accountingsettings_model->saveMultiple($settings);
            $this->session->set_flashdata('success', 'Configuracion contable actualizada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'No se recibieron datos para guardar.');
        }

        redirect(base_url() . 'sisvent/admin/accountingsettings');
    }
}
