<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Provider_advances — Anticipos a proveedores (modelo bolsa).
 * Portado de stockaccessories.co (20/08/2026). Tesorería: cajas/bancos.
 *
 * URLs:
 *   GET  /sisvent/purchases/provider_advances              listado + saldos
 *   GET  /sisvent/purchases/provider_advances/add          nuevo anticipo
 *   POST /sisvent/purchases/provider_advances/save
 *   POST /sisvent/purchases/provider_advances/delete/<id>
 */
class Provider_advances extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->helper('compras');
        if (!$this->db->table_exists('provider_advances')) {
            $this->session->set_flashdata('warning', 'Módulo de anticipos a proveedor no disponible. Falta la tabla provider_advances (migración 069).');
            redirect('sisvent/dashboard');
            exit;
        }
        $this->load->model('provider_advances_model');
        $this->load->model('providers_model');
        $this->load->model('cxp_model');
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

    private function _fuentesPago(): array
    {
        $bancos = $this->db->query("SELECT idBankAccount AS id, bankName AS name FROM bank_accounts WHERE COALESCE(deleted,0)=0 ORDER BY bankName")->result();
        $cajas  = $this->db->query("SELECT idCashbox AS id, name FROM cashboxes WHERE COALESCE(deleted,0)=0 ORDER BY name")->result();
        return array('bancos' => $bancos, 'cajas' => $cajas);
    }

    public function index(): void
    {
        $role = $this->_guard();
        $filters = array(
            'provider_id' => $this->input->get('provider_id') ?: null,
            'status'      => $this->input->get('status') ?: null,
        );
        $payments = $this->cxp_model->listAllPayments(array(
            'provider_id' => $filters['provider_id'] ?? null,
        ));

        $this->load->view('sisvent/purchases/provider_advances/index', array(
            'advances'  => $this->provider_advances_model->listAdvances($filters),
            'balances'  => $this->provider_advances_model->balancesByProvider(),
            'payments'  => $payments,
            'providers' => $this->providers_model->getProviders(),
            'filters'   => $filters,
            'role'      => $role,
        ));
    }

    public function add(): void
    {
        $role = $this->_guard();
        $fuentes = $this->_fuentesPago();
        $this->load->view('sisvent/purchases/provider_advances/add', array(
            'providers'       => $this->providers_model->getProviders(),
            'bancos'          => $fuentes['bancos'],
            'cajas'           => $fuentes['cajas'],
            'preset_provider' => $this->input->get('provider_id') ?: null,
            'role'            => $role,
        ));
    }

    public function save(): void
    {
        $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('sisvent/purchases/provider_advances'); return; }

        $fuente = (string) $this->input->post('fuente');
        $sourceType = null; $sourceId = null;
        if ($fuente !== '' && strpos($fuente, ':') !== false) {
            list($sourceType, $sourceId) = explode(':', $fuente, 2);
            $sourceId = (int) $sourceId;
        }

        $data = array(
            'provider_id'    => (int) $this->input->post('provider_id'),
            'pay_date'       => $this->input->post('pay_date') ?: date('Y-m-d'),
            'currency'       => $this->input->post('currency') ?: 'COP',
            'exchange_rate'  => (float) str_replace(',', '.', (string) $this->input->post('exchange_rate')) ?: 1,
            'amount'         => (float) str_replace(',', '.', (string) $this->input->post('amount')),
            'payment_method' => $this->input->post('payment_method') ?: null,
            'source_type'    => $sourceType,
            'source_id'      => $sourceId,
            'reference'      => $this->input->post('reference') ?: null,
            'notes'          => $this->input->post('notes') ?: null,
            'created_by'     => $this->session->userdata('user_data')['uname'] ?? null,
        );
        try {
            $this->provider_advances_model->createAdvance($data);
            $this->session->set_flashdata('success', 'Anticipo registrado.');
            redirect('sisvent/purchases/provider_advances');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('sisvent/purchases/provider_advances/add');
        }
    }

    public function delete($id): void
    {
        $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { show_error('Método no permitido.', 405); return; }
        $id = (int) $id;
        $adv = $this->provider_advances_model->getAdvance($id);
        if (!$adv) { $this->session->set_flashdata('error', 'Anticipo no encontrado.'); redirect('sisvent/purchases/provider_advances'); return; }
        if ((float) $adv->applied_amount > 0.01) {
            $this->session->set_flashdata('error', 'No se puede anular: el anticipo ya tiene aplicaciones contra facturas.');
            redirect('sisvent/purchases/provider_advances');
            return;
        }
        // Soft-delete + revertir el movimiento y el saldo de tesorería
        $this->db->trans_start();
        $this->db->where('id', $id)->update('provider_advances', array('deleted' => 1, 'status' => 'refunded', 'updated_at' => date('Y-m-d H:i:s')));
        if (!empty($adv->cash_movement_id)) {
            $mov = $this->db->get_where('cash_movements', array('idMovement' => (int) $adv->cash_movement_id))->row();
            if ($mov && (int) $mov->deleted === 0) {
                $this->db->where('idMovement', $mov->idMovement)->update('cash_movements', array(
                    'deleted' => 1, 'status' => 'anulado',
                    'concept' => 'ANULADO · ' . $mov->concept,
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
                if ($mov->sourceType === 'banco') {
                    $this->db->query("UPDATE bank_accounts SET currentBalance = currentBalance + ? WHERE idBankAccount = ?", array($mov->amount, $mov->sourceId));
                } else {
                    $this->db->query("UPDATE cashboxes SET currentBalance = currentBalance + ? WHERE idCashbox = ?", array($mov->amount, $mov->sourceId));
                }
            }
        }
        $this->db->trans_complete();
        $this->session->set_flashdata('success', 'Anticipo ' . $adv->adv_code . ' anulado · tesorería ajustada.');
        redirect('sisvent/purchases/provider_advances');
    }
}
