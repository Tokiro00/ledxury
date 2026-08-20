<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cxp — panel de Cuentas por Pagar a proveedores (aging dashboard).
 * Portado de stockaccessories.co (20/08/2026). Moneda base: COP.
 *
 * URL: GET /sisvent/purchases/cxp
 */
class Cxp extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->helper('compras');
        $this->load->model('cxp_model');
    }

    public function index(): void
    {
        $role = (int) $this->session->userdata('user_data')['role'];
        if (!in_array($role, array(1, 2, 4), true)) {
            show_error('No autorizado.', 403);
            return;
        }

        $this->load->view('sisvent/purchases/cxp/index', array(
            'aging'          => $this->cxp_model->agingByProvider(),
            'totals'         => $this->cxp_model->cxpTotals(),
            'month_payments' => $this->cxp_model->paymentsThisMonth(),
            'advances'       => $this->cxp_model->advancesByProvider(),
            'transit'        => $this->cxp_model->transitByProvider(),
            'role'           => $role,
        ));
    }
}
