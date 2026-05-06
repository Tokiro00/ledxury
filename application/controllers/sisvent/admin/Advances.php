<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Anticipos a vendedores. Workflow: pendiente → aprobado → desembolsado → pagado.
 *
 * Quién puede aprobar/desembolsar/anular: roles 1 (admin), 2 (gerente), 4 (contador).
 * Crear puede cualquiera con permiso 'cartera'.
 */
class Advances extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('cartera');
        $this->load->model('employeeadvances_model');
        $this->load->model('vendors_model');
        $this->load->model('cashboxes_model');
        $this->load->model('bankaccounts_model');
        $this->load->model('cashmovements_model');
        $this->load->model('stores_model');
        $this->load->library('accounting_lib');
    }

    private function _canApprove()
    {
        $role = (int)$this->session->userdata('user_data')['role'];
        return in_array($role, array(1, 2, 4));
    }

    public function index()
    {
        $page = (int)$this->input->get('p') ?: 1;
        $filters = array();
        if ($this->input->get('employee_id')) $filters['employee_id'] = $this->input->get('employee_id');
        if ($this->input->get('status'))      $filters['status']      = $this->input->get('status');
        if ($this->input->get('from'))        $filters['from']        = $this->input->get('from');
        if ($this->input->get('to'))          $filters['to']          = $this->input->get('to');

        $limit = 20;
        $total = $this->employeeadvances_model->getTotal($filters);
        $last  = max(1, ceil($total / $limit));
        if ($page > $last) $page = $last;
        if ($page < 1)     $page = 1;

        $data = array(
            'advances'  => $this->employeeadvances_model->getList($filters, $page, $limit),
            'vendors'   => $this->vendors_model->getVendors(),
            'balances'  => $this->employeeadvances_model->getBalanceByEmployee(),
            'page'      => $page,
            'last'      => $last,
            'total'     => $total,
            'filters'   => $filters,
            'role'      => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/advances/list', $data);
    }

    public function add()
    {
        $storeId = $this->session->userdata('user_data')['store'] ?: 1;
        $data = array(
            'vendors'      => $this->vendors_model->getVendors(),
            'cashboxes'    => $this->cashboxes_model->getCashboxesByStore($storeId),
            'bankaccounts' => $this->bankaccounts_model->getBankAccountsByStore($storeId),
            'stores'       => $this->stores_model->getStores(),
            'nextCode'     => $this->employeeadvances_model->getNextCode(),
            'preselect_employee' => $this->input->get('employee_id'),  // ?employee_id=X para pre-seleccionar
            'role'         => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/advances/add', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $employeeId  = $this->input->post('employee_id');
        $amount      = (float)$this->input->post('amount');
        $purpose     = trim($this->input->post('purpose'));
        $type        = $this->input->post('type') ?: 'anticipo';
        $storeId     = (int)$this->input->post('store_id') ?: 1;
        $sourceType  = $this->input->post('source_type');
        $sourceId    = $this->input->post('source_id');
        $disburseNow = $this->input->post('disburse_now') === '1';

        // Campos nuevos (paridad Lumen + fecha solicitada).
        $advanceDate = $this->input->post('advance_date') ?: date('Y-m-d');
        $numInstall  = max(1, (int)$this->input->post('num_installments'));
        if ($type === 'anticipo') $numInstall = 1;  // anticipo simple: siempre 1 cuota
        $installAmt  = $numInstall > 0 ? round($amount / $numInstall) : $amount;
        $observations = trim((string)$this->input->post('observations')) ?: null;

        $today = date('Y-m-d');

        $this->form_validation->set_rules('employee_id', 'Empleado', 'required');
        $this->form_validation->set_rules('amount', 'Monto', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('purpose', 'Propósito', 'required');
        $this->form_validation->set_rules('advance_date', 'Fecha', 'required');

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors());
            redirect(base_url() . 'sisvent/admin/advances/add');
            return;
        }

        if ($disburseNow) {
            if (!in_array($sourceType, array('caja','banco')) || !$sourceId) {
                $this->session->set_flashdata('error', 'Seleccioná caja o banco para desembolsar ahora');
                redirect(base_url() . 'sisvent/admin/advances/add');
                return;
            }
            if ($this->accounting_lib->isPeriodClosed($today, $storeId)) {
                $this->session->set_flashdata('error', 'No se puede desembolsar en un período ya cerrado');
                redirect(base_url() . 'sisvent/admin/advances/add');
                return;
            }
        }

        $userId = $this->session->userdata('user_data')['uname'];
        $code = $this->employeeadvances_model->getNextCode();

        $this->db->trans_start();

        $this->employeeadvances_model->save(array(
            'code' => $code,
            'employee_id' => $employeeId,
            'amount' => $amount,
            'outstanding_balance' => $amount,
            'purpose' => $purpose,
            'observations' => $observations,
            'advance_date' => $advanceDate,
            'num_installments' => $numInstall,
            'installment_amount' => $installAmt,
            'type' => $type,
            'store_id' => $storeId,
            'status' => 'pendiente',
            'created_by' => $userId,
        ));
        $advanceId = $this->employeeadvances_model->lastID();

        if ($disburseNow && $this->_canApprove()) {
            $this->_processDisbursement($advanceId, $employeeId, $amount, $sourceType, $sourceId, $storeId, $userId, $purpose, $today);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            redirect(base_url() . 'sisvent/admin/advances/view/' . $advanceId);
        } else {
            $this->session->set_flashdata('error', 'Error al guardar el anticipo');
            redirect(base_url() . 'sisvent/admin/advances/add');
        }
    }

    public function view($id)
    {
        $advance = $this->employeeadvances_model->get($id);
        if (!$advance) redirect(base_url() . 'sisvent/admin/advances');

        $storeId = $advance->store_id;
        $data = array(
            'advance'      => $advance,
            'cashboxes'    => $this->cashboxes_model->getCashboxesByStore($storeId),
            'bankaccounts' => $this->bankaccounts_model->getBankAccountsByStore($storeId),
            'role'         => $this->session->userdata('user_data')['role'],
            'canApprove'   => $this->_canApprove(),
        );
        $this->load->view('sisvent/admin/advances/view', $data);
    }

    /**
     * pendiente → aprobado (sin movimiento de plata todavía)
     */
    public function approve($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;
        if (!$this->_canApprove()) { echo 'error:Sin permiso para aprobar anticipos'; return; }

        $advance = $this->employeeadvances_model->get($id);
        if (!$advance) { echo 'error:Anticipo no encontrado'; return; }
        if ($advance->status !== 'pendiente') { echo 'error:Solo anticipos pendientes pueden aprobarse'; return; }

        $userId = $this->session->userdata('user_data')['uname'];
        $this->employeeadvances_model->update($id, array(
            'status' => 'aprobado',
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ));
        echo base_url() . 'sisvent/admin/advances/view/' . $id;
    }

    /**
     * aprobado|pendiente → desembolsado (sale el dinero, cash movement + asiento)
     */
    public function disburse($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;
        if (!$this->_canApprove()) { echo 'error:Sin permiso para desembolsar'; return; }

        $advance = $this->employeeadvances_model->get($id);
        if (!$advance) { echo 'error:Anticipo no encontrado'; return; }
        if (!in_array($advance->status, array('pendiente','aprobado'))) {
            echo 'error:Estado actual no permite desembolso (' . $advance->status . ')';
            return;
        }

        $sourceType = $this->input->post('source_type');
        $sourceId   = $this->input->post('source_id');
        if (!in_array($sourceType, array('caja','banco')) || !$sourceId) {
            echo 'error:Seleccioná caja o banco';
            return;
        }
        $today = date('Y-m-d');
        if ($this->accounting_lib->isPeriodClosed($today, $advance->store_id)) {
            echo 'error:No se puede desembolsar en un período ya cerrado';
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];
        $this->db->trans_start();
        $this->_processDisbursement($id, $advance->employee_id, (float)$advance->amount, $sourceType, $sourceId, $advance->store_id, $userId, $advance->purpose, $today);
        $this->db->trans_complete();

        echo base_url() . 'sisvent/admin/advances/view/' . $id;
    }

    /**
     * Anular anticipo. Si está desembolsado y aún tiene saldo, postea reversa
     * (devuelve plata a caja). No se permite si ya se cruzó parte con liquidación.
     */
    public function cancel($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;
        if (!$this->_canApprove()) { echo 'error:Sin permiso para anular anticipos'; return; }

        $advance = $this->employeeadvances_model->get($id);
        if (!$advance) { echo 'error:Anticipo no encontrado'; return; }
        if (in_array($advance->status, array('pagado','anulado'))) {
            echo 'error:Estado actual no permite anular (' . $advance->status . ')';
            return;
        }

        // Si tiene cruces parciales con liquidaciones, no se puede anular
        $crosses = $this->db->where('advance_id', $id)->count_all_results('settlement_advance_payments');
        if ($crosses > 0) {
            echo 'error:Este anticipo ya tiene cruces con liquidaciones, no se puede anular. Hacer ajuste manual.';
            return;
        }

        $reason = trim($this->input->post('reason'));
        $userId = $this->session->userdata('user_data')['uname'];

        $this->db->trans_start();

        // Si ya se desembolsó y el saldo está intacto, postea reversa contable + devuelve plata
        $reversalEntryId = null;
        if ($advance->status === 'desembolsado' && (float)$advance->outstanding_balance == (float)$advance->amount) {
            // Devolver plata a la caja/banco origen
            $cashAccountId = ($advance->source_type === 'caja')
                ? $this->accounting_lib->getCashAccount($advance->store_id)
                : $this->accounting_lib->getBankAccount($advance->store_id);
            if ($cashAccountId) {
                $reversalEntryId = $this->accounting_lib->reverseEmployeeAdvance(
                    $advance->id, (float)$advance->amount, $advance->employee_id, $cashAccountId,
                    $advance->store_id, $userId, 'Reversa anticipo ' . $advance->code, date('Y-m-d')
                );
            }
            // Movimiento de caja inverso (ingreso)
            $this->cashmovements_model->save(array(
                'movementType' => 'ingreso',
                'sourceType' => $advance->source_type,
                'sourceId'   => $advance->source_id,
                'amount'     => $advance->amount,
                'concept'    => 'Reversa anticipo ' . $advance->code . ' (' . $reason . ')',
                'category'   => 'anticipo_reversa',
                'referenceType' => 'advance',
                'referenceId'   => $advance->id,
                'executedBy'    => $userId,
                'movementDate'  => date('Y-m-d H:i:s'),
                'status'        => 'ejecutado',
            ));
            if ($advance->source_type === 'caja') {
                $this->cashboxes_model->updateBalance($advance->source_id, $advance->amount, 'add');
            } else {
                $this->bankaccounts_model->updateBalance($advance->source_id, $advance->amount, 'add');
            }
        }

        $this->employeeadvances_model->update($id, array(
            'status' => 'anulado',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $reason,
            'reversal_entry_id' => $reversalEntryId,
            'outstanding_balance' => 0,
        ));

        $this->db->trans_complete();
        echo base_url() . 'sisvent/admin/advances';
    }

    // ========================================================================
    // PRIVATE HELPERS
    // ========================================================================

    private function _processDisbursement($advanceId, $employeeId, $amount, $sourceType, $sourceId, $storeId, $userId, $description, $date)
    {
        // 1. Cash movement (egreso)
        $this->cashmovements_model->save(array(
            'movementType' => 'egreso',
            'sourceType' => $sourceType,
            'sourceId'   => $sourceId,
            'amount'     => $amount,
            'concept'    => 'Anticipo: ' . $description,
            'category'   => 'anticipo_empleado',
            'referenceType' => 'advance',
            'referenceId'   => $advanceId,
            'executedBy'    => $userId,
            'movementDate'  => $date . ' ' . date('H:i:s'),
            'status'        => 'ejecutado',
        ));
        $movementId = $this->cashmovements_model->lastID();

        // 2. Saldo
        if ($sourceType == 'caja') {
            $this->cashboxes_model->updateBalance($sourceId, $amount, 'subtract');
        } else {
            $this->bankaccounts_model->updateBalance($sourceId, $amount, 'subtract');
        }

        // 3. Asiento contable: DR 136525 [aux=empleado] / CR Caja|Banco
        $cashAccountId = ($sourceType === 'caja')
            ? $this->accounting_lib->getCashAccount($storeId)
            : $this->accounting_lib->getBankAccount($storeId);

        $entryId = null;
        if ($cashAccountId) {
            $entryId = $this->accounting_lib->recordEmployeeAdvance(
                $advanceId, $amount, $employeeId, $cashAccountId,
                $storeId, $userId, 'Anticipo: ' . $description, $date
            );
        }

        $this->employeeadvances_model->update($advanceId, array(
            'status' => 'desembolsado',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'cash_movement_id' => $movementId,
            'entry_id' => $entryId,
            'disbursed_at' => date('Y-m-d H:i:s'),
        ));
    }
}
