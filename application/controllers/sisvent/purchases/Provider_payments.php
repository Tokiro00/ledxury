<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Provider_payments — pagos a proveedores desde cajas/bancos de Ledxury.
 * Portado de stockaccessories.co (20/08/2026). Moneda base: COP.
 *
 * URLs:
 *   GET  /sisvent/purchases/provider_payments[?provider_id=N&date_from=Y&date_to=Y]
 *   POST /sisvent/purchases/provider_payments/save   (desde modal de factura)
 *   POST /sisvent/purchases/provider_payments/delete/<id>
 */
class Provider_payments extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->helper('compras');
        $this->load->model('cxp_model');
        $this->load->model('providers_model');
    }

    private function _guard(): int
    {
        $role = (int) $this->session->userdata('user_data')['role'];
        if (!in_array($role, array(1, 2, 4), true)) {
            show_error('No autorizado.', 403);
            exit;
        }
        return $role;
    }

    public function index(): void
    {
        $role = $this->_guard();
        $filters = array(
            'provider_id' => $this->input->get('provider_id') ?: null,
            'date_from'   => $this->input->get('date_from') ?: date('Y-m-01'),
            'date_to'     => $this->input->get('date_to')   ?: date('Y-m-d'),
        );
        $payments = $this->cxp_model->listAllPayments($filters);
        $providers = $this->providers_model->getProviders();

        $totals = array('count' => 0, 'amount_base' => 0, 'fx_diff' => 0);
        foreach ($payments as $p) {
            $totals['count']++;
            $totals['amount_base'] += (float) $p->amount * ($p->currency === 'COP' ? 1 : (float) $p->exchange_rate);
            $totals['fx_diff']     += (float) $p->fx_diff;
        }

        $this->load->view('sisvent/purchases/provider_payments/index', array(
            'payments'  => $payments,
            'providers' => $providers,
            'filters'   => $filters,
            'totals'    => $totals,
            'role'      => $role,
        ));
    }

    public function save(): void
    {
        $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sisvent/purchases/cxp');
            return;
        }

        $invoiceId = (int) $this->input->post('invoice_id');
        if ($invoiceId <= 0) {
            $this->session->set_flashdata('error', 'Factura inválida.');
            redirect('sisvent/purchases/cxp');
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'] ?? null;

        // El origen viene como "banco:3" o "caja:1" del select unificado.
        $fuente = (string) $this->input->post('fuente');
        $sourceType = null; $sourceId = null;
        if ($fuente !== '' && strpos($fuente, ':') !== false) {
            list($sourceType, $sourceId) = explode(':', $fuente, 2);
            $sourceId = (int) $sourceId;
        }

        $data = array(
            'invoice_id'     => $invoiceId,
            'pay_date'       => $this->input->post('pay_date') ?: date('Y-m-d'),
            'currency'       => $this->input->post('currency') ?: 'COP',
            'exchange_rate'  => (float) str_replace(',', '.', (string) $this->input->post('exchange_rate')) ?: 1,
            'amount'         => (float) str_replace(',', '.', (string) $this->input->post('amount')),
            'payment_method' => $this->input->post('payment_method') ?: null,
            'source_type'    => $sourceType,
            'source_id'      => $sourceId,
            'reference'      => $this->input->post('reference') ?: null,
            'notes'          => $this->input->post('notes') ?: null,
            'created_by'     => $userId,
        );

        if ($data['amount'] <= 0) {
            $this->session->set_flashdata('error', 'El monto del pago debe ser mayor a 0.');
            redirect('sisvent/purchases/provider_invoices/view/' . $invoiceId);
            return;
        }

        try {
            $this->cxp_model->createPayment($data);
            $this->session->set_flashdata('success', 'Pago registrado · ' . money($data['amount']));
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'No se pudo registrar el pago: ' . $e->getMessage());
        }
        redirect('sisvent/purchases/provider_invoices/view/' . $invoiceId);
    }

    public function delete($id): void
    {
        $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            show_error('Método no permitido.', 405);
            return;
        }

        $id = (int) $id;
        $payment = $this->cxp_model->getPayment($id);
        if (!$payment) {
            $this->session->set_flashdata('error', 'Pago no encontrado.');
            redirect('sisvent/purchases/provider_payments');
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'] ?? null;
        try {
            $this->cxp_model->deletePayment($id, $userId);
            $this->session->set_flashdata('success', sprintf('Pago %s eliminado · tesorería ajustada.', $payment->pay_code));
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'No se pudo eliminar: ' . $e->getMessage());
        }
        redirect('sisvent/purchases/provider_invoices/view/' . (int) $payment->invoice_id);
    }
}
