<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounts Payable Controller (Cuentas por Pagar)
 * Manages supplier invoices and payments
 */
class Accountspayable extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 4]);
        $this->load->model("supplierbills_model");
        $this->load->model("supplierpayments_model");
        $this->load->model("providers_model");
        $this->load->model("cashboxes_model");
        $this->load->model("bankaccounts_model");
        $this->load->model("cashmovements_model");
        $this->load->library("accounting_lib");
    }

    /**
     * List all supplier invoices
     */
    public function index()
    {
        $page = $this->input->get('p') ?: 1;
        $limit = 50;

        // Build filters from query params
        $filters = array();
        if ($this->input->get('provider')) {
            $filters['providerId'] = $this->input->get('provider');
        }
        if ($this->input->get('status')) {
            $filters['status'] = $this->input->get('status');
        }
        if ($this->input->get('from')) {
            $filters['from'] = $this->input->get('from');
        }
        if ($this->input->get('to')) {
            $filters['to'] = $this->input->get('to');
        }

        $total = $this->supplierbills_model->getTotal($filters);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        // Update overdue status
        $this->supplierbills_model->updateOverdueStatus();

        // Get aging report for summary cards
        $aging = $this->supplierbills_model->getAgingReport();

        $data = array(
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'bills' => $this->supplierbills_model->getBills($page, $limit, $filters),
            'providers' => $this->providers_model->getProviders(),
            'aging' => $aging,
            'filters' => $filters
        );
        $this->load->view("sisvent/admin/accountspayable/list", $data);
    }

    /**
     * Form to add new supplier invoice
     */
    public function add()
    {
        $data = array(
            'providers' => $this->providers_model->getProviders()
        );
        $this->load->view("sisvent/admin/accountspayable/add", $data);
    }

    /**
     * Store new supplier invoice
     */
    public function store()
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        date_default_timezone_set("America/Bogota");

        $userId = $this->session->userdata('user_data')['uname'];
        $storeId = $this->session->userdata('user_data')['store'];

        $providerId = $this->input->post("provider_id");
        $invoiceNumber = $this->input->post("invoice_number");
        $invoiceDate = $this->input->post("invoice_date");
        $dueDate = $this->input->post("due_date");
        $total = $this->input->post("total");
        $concept = $this->input->post("concept");
        $expenseCode = $this->input->post("expense_code") ?: '519595';

        // Validate
        if (!$providerId || !$invoiceNumber || !$total || $total <= 0) {
            $this->session->set_flashdata("error", "Proveedor, número de factura y total son requeridos");
            redirect(base_url() . "sisvent/admin/accountspayable/add");
            return;
        }

        // Save supplier invoice
        $data = array(
            'providerId' => $providerId,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate ?: date('Y-m-d'),
            'dueDate' => $dueDate ?: date('Y-m-d', strtotime('+30 days')),
            'total' => $total,
            'balance' => $total,
            'paidAmount' => 0,
            'concept' => $concept,
            'storeId' => $storeId,
            'status' => 'pendiente',
            'created_by' => $userId
        );

        $this->supplierbills_model->save($data);
        $billId = $this->supplierbills_model->lastID();

        // Generate accounting entry
        $this->accounting_lib->recordSupplierBill(
            $billId,
            $providerId,
            $storeId,
            $total,
            $userId,
            $expenseCode
        );

        $this->logs_model->logMessage("info", "Usuario " . $userId . " registró factura proveedor #" . $invoiceNumber);

        $this->session->set_flashdata("success", "Factura de proveedor registrada exitosamente");
        redirect(base_url() . "sisvent/admin/accountspayable");
    }

    /**
     * View supplier invoice details
     */
    public function view($id)
    {
        $bill = $this->supplierbills_model->getBill($id);

        if (!$bill) {
            $this->session->set_flashdata("error", "Factura no encontrada");
            redirect(base_url() . "sisvent/admin/accountspayable");
            return;
        }

        $payments = $this->supplierpayments_model->getPaymentsByInvoice($id);

        $data = array(
            'bill' => $bill,
            'payments' => $payments
        );
        $this->load->view("sisvent/admin/accountspayable/view", $data);
    }

    /**
     * Form to make payment to supplier invoice
     */
    public function pay($id)
    {
        $bill = $this->supplierbills_model->getBill($id);

        if (!$bill) {
            $this->session->set_flashdata("error", "Factura no encontrada");
            redirect(base_url() . "sisvent/admin/accountspayable");
            return;
        }

        if ($bill->status == 'pagada') {
            $this->session->set_flashdata("error", "Esta factura ya está completamente pagada");
            redirect(base_url() . "sisvent/admin/accountspayable/view/" . $id);
            return;
        }

        $storeId = $this->session->userdata('user_data')['store'];

        $data = array(
            'bill' => $bill,
            'cashboxes' => $this->cashboxes_model->getActiveCashboxes($storeId),
            'bankaccounts' => $this->bankaccounts_model->getActiveBankAccounts($storeId)
        );
        $this->load->view("sisvent/admin/accountspayable/pay", $data);
    }

    /**
     * Process payment to supplier
     */
    public function processPayment()
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        date_default_timezone_set("America/Bogota");

        $userId = $this->session->userdata('user_data')['uname'];
        $storeId = $this->session->userdata('user_data')['store'];

        $billId = $this->input->post("bill_id");
        $amount = $this->input->post("amount");
        $paymentDate = $this->input->post("payment_date") ?: date('Y-m-d');
        $reference = $this->input->post("reference");
        $notes = $this->input->post("notes");
        $cashSourceType = $this->input->post("cash_source_type");
        $cashSourceId = ($cashSourceType == 'cashbox')
            ? $this->input->post("cash_source_cashbox")
            : $this->input->post("cash_source_bank");

        $bill = $this->supplierbills_model->getBill($billId);

        if (!$bill) {
            $this->session->set_flashdata("error", "Factura no encontrada");
            redirect(base_url() . "sisvent/admin/accountspayable");
            return;
        }

        // Validate amount
        if (!$amount || $amount <= 0) {
            $this->session->set_flashdata("error", "El monto debe ser mayor a cero");
            redirect(base_url() . "sisvent/admin/accountspayable/pay/" . $billId);
            return;
        }

        if ($amount > $bill->balance) {
            $this->session->set_flashdata("error", "El monto no puede ser mayor al saldo pendiente ($" . number_format($bill->balance, 2) . ")");
            redirect(base_url() . "sisvent/admin/accountspayable/pay/" . $billId);
            return;
        }

        // Validate cash source has sufficient balance
        if ($cashSourceType == 'cashbox') {
            $cashbox = $this->cashboxes_model->getCashbox($cashSourceId);
            if (!$cashbox || $cashbox->currentBalance < $amount) {
                $this->session->set_flashdata("error", "La caja no tiene saldo suficiente");
                redirect(base_url() . "sisvent/admin/accountspayable/pay/" . $billId);
                return;
            }
        } else {
            $bank = $this->bankaccounts_model->getBankAccount($cashSourceId);
            if (!$bank || $bank->currentBalance < $amount) {
                $this->session->set_flashdata("error", "La cuenta bancaria no tiene saldo suficiente");
                redirect(base_url() . "sisvent/admin/accountspayable/pay/" . $billId);
                return;
            }
        }

        // 1. Save supplier payment
        $paymentData = array(
            'supplierInvoiceId' => $billId,
            'providerId' => $bill->providerId,
            'amount' => $amount,
            'paymentDate' => $paymentDate,
            'paymentMethod' => $cashSourceType,
            'reference' => $reference,
            'notes' => $notes,
            'storeId' => $storeId,
            'status' => 'ejecutado',
            'created_by' => $userId
        );
        $this->supplierpayments_model->save($paymentData);
        $paymentId = $this->supplierpayments_model->lastID();

        // 2. Update supplier invoice (paidAmount, balance, status)
        $newPaidAmount = (float)$bill->paidAmount + (float)$amount;
        $newBalance = (float)$bill->total - $newPaidAmount;
        $newStatus = ($newBalance <= 0) ? 'pagada' : 'parcial';

        $this->supplierbills_model->update($billId, array(
            'paidAmount' => $newPaidAmount,
            'balance' => max(0, $newBalance),
            'status' => $newStatus
        ));

        // 3. Create cash movement (egreso)
        $movementData = array(
            'sourceType' => $cashSourceType,
            'sourceId' => $cashSourceId,
            'movementType' => 'egreso',
            'movementDate' => date('Y-m-d H:i:s', strtotime($paymentDate)),
            'amount' => $amount,
            'concept' => "Pago a proveedor - Fact. #" . $bill->invoiceNumber,
            'category' => 'pago_proveedor',
            'documentNumber' => $reference,
            'referenceType' => 'supplier_payment',
            'referenceId' => $paymentId,
            'status' => 'ejecutado',
            'created_by' => $userId
        );
        $this->cashmovements_model->save($movementData);
        $movementId = $this->cashmovements_model->lastID();

        // 4. Update payment with movement ID
        $this->supplierpayments_model->update($paymentId, array('cashMovementId' => $movementId));

        // 5. Update cash/bank balance (subtract for payment)
        if ($cashSourceType == 'cashbox') {
            $this->cashboxes_model->updateBalance($cashSourceId, $amount, 'sub');
        } else {
            $this->bankaccounts_model->updateBalance($cashSourceId, $amount, 'sub');
        }

        // 6. Generate accounting entry
        $cashAccountId = ($cashSourceType == 'cashbox')
            ? $this->accounting_lib->getCashAccount($storeId)
            : $this->accounting_lib->getBankAccount($storeId);

        $this->accounting_lib->recordSupplierPayment(
            $paymentId,
            $bill->providerId,
            $amount,
            $storeId,
            $userId,
            $cashAccountId
        );

        $this->logs_model->logMessage("info", "Usuario " . $userId . " registró pago a proveedor por $" . $amount);

        $this->session->set_flashdata("success", "Pago registrado exitosamente");
        redirect(base_url() . "sisvent/admin/accountspayable/view/" . $billId);
    }

    /**
     * Cancel/void a supplier invoice
     */
    public function cancel($id)
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $bill = $this->supplierbills_model->getBill($id);

        if (!$bill) {
            $this->session->set_flashdata("error", "Factura no encontrada");
            redirect(base_url() . "sisvent/admin/accountspayable");
            return;
        }

        if ($bill->paidAmount > 0) {
            $this->session->set_flashdata("error", "No se puede anular una factura con pagos registrados");
            redirect(base_url() . "sisvent/admin/accountspayable/view/" . $id);
            return;
        }

        $this->supplierbills_model->remove($id);

        $userId = $this->session->userdata('user_data')['uname'];
        $this->logs_model->logMessage("info", "Usuario " . $userId . " anuló factura proveedor #" . $bill->invoiceNumber);

        $this->session->set_flashdata("success", "Factura anulada exitosamente");
        redirect(base_url() . "sisvent/admin/accountspayable");
    }

    /**
     * Get bill info via AJAX
     */
    public function getBill()
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $billId = $this->input->post("bill_id");
        $bill = $this->supplierbills_model->getBill($billId);

        echo json_encode($bill);
    }

    /**
     * Get pending bills for a provider via AJAX
     */
    public function getProviderBills()
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $providerId = $this->input->post("provider_id");
        $bills = $this->supplierbills_model->getPendingBills($providerId);

        echo json_encode($bills);
    }
}
