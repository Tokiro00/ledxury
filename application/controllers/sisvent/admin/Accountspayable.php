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
        $this->backend_lib->controlModule('cuentas_pagar');
        $this->load->model("supplierbills_model");
        $this->load->model("supplierpayments_model");
        $this->load->model("providers_model");
        $this->load->model("cashboxes_model");
        $this->load->model("bankaccounts_model");
        $this->load->model("cashmovements_model");
        $this->load->model("supplierinvoicedetails_model");
        $this->load->model("inventory_model");
        $this->load->model("stores_model");
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
        if ($this->input->get('received') !== null && $this->input->get('received') !== '') {
            $filters['received'] = $this->input->get('received');
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
            'providers' => $this->providers_model->getProviders(),
            'stores' => $this->stores_model->getStores()
        );
        $this->load->view("sisvent/admin/accountspayable/add", $data);
    }

    /**
     * Store new supplier invoice with product details
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
        $concept = $this->input->post("concept");
        $destinationStore = $this->input->post("destination_store");

        // Product arrays
        $products = $this->input->post("products");
        $quantities = $this->input->post("quantities");
        $costs = $this->input->post("costs");
        $descriptions = $this->input->post("descriptions");
        $subtotals = $this->input->post("subtotals");

        // Validate
        if (!$providerId || !$invoiceNumber || empty($products)) {
            $this->session->set_flashdata("error", "Proveedor, numero de factura y al menos un producto son requeridos");
            redirect(base_url() . "sisvent/admin/accountspayable/add");
            return;
        }

        // Calculate total from line items
        $total = 0;
        for ($i = 0; $i < count($products); $i++) {
            $total += (float)$subtotals[$i];
        }

        if ($total <= 0) {
            $this->session->set_flashdata("error", "El total debe ser mayor a cero");
            redirect(base_url() . "sisvent/admin/accountspayable/add");
            return;
        }

        // Save supplier invoice (NO incluir 'balance' - es columna GENERATED)
        $data = array(
            'providerId' => $providerId,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate ?: date('Y-m-d'),
            'dueDate' => $dueDate ?: date('Y-m-d', strtotime('+30 days')),
            'total' => $total,
            'paidAmount' => 0,
            'concept' => $concept,
            'storeId' => $storeId,
            'destination_store' => $destinationStore,
            'received' => 0,
            'status' => 'pendiente',
            'created_by' => $userId
        );

        $this->supplierbills_model->save($data);
        $billId = $this->supplierbills_model->lastID();

        // Save product details
        for ($i = 0; $i < count($products); $i++) {
            $detailData = array(
                'supplierInvoiceId' => $billId,
                'productId' => $products[$i],
                'description' => $descriptions[$i],
                'quantity' => (int)$quantities[$i],
                'unitCost' => (float)$costs[$i],
                'total' => (float)$subtotals[$i]
            );
            $this->supplierinvoicedetails_model->save($detailData);
        }

        // Generate accounting entry (compra de mercancía en tránsito)
        $this->accounting_lib->recordSupplierBill(
            $billId,
            $providerId,
            $storeId,
            $total,
            $userId
        );

        $this->logs_model->logMessage("info", "Usuario " . $userId . " registro factura proveedor #" . $invoiceNumber . " con " . count($products) . " productos");

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
        $details = $this->supplierinvoicedetails_model->getDetails($id);

        $destinationStore = null;
        if ($bill->destination_store) {
            $destinationStore = $this->stores_model->getStore($bill->destination_store);
        }

        $data = array(
            'bill' => $bill,
            'payments' => $payments,
            'details' => $details,
            'destinationStore' => $destinationStore
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
            'cashboxes' => $this->cashboxes_model->getCashboxesByStore($storeId),
            'bankaccounts' => $this->bankaccounts_model->getBankAccountsByStore($storeId)
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
        $cashSourceTypeRaw = $this->input->post("cash_source_type");
        $cashSourceId = ($cashSourceTypeRaw == 'cashbox')
            ? $this->input->post("cash_source_cashbox")
            : $this->input->post("cash_source_bank");
        $cashSourceType = ($cashSourceTypeRaw == 'cashbox') ? 'caja' : 'banco';

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
        if ($cashSourceType == 'caja') {
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
        if ($cashSourceType == 'caja') {
            $this->cashboxes_model->updateBalance($cashSourceId, $amount, 'sub');
        } else {
            $this->bankaccounts_model->updateBalance($cashSourceId, $amount, 'sub');
        }

        // 6. Generate accounting entry
        $cashAccountId = ($cashSourceType == 'caja')
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

        if (isset($bill->received) && $bill->received == 1) {
            $this->session->set_flashdata("error", "No se puede anular una factura cuya mercancia ya fue recibida");
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

    /**
     * Search products via AJAX (for autocomplete in add form)
     */
    public function getProducts()
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $valor = $this->input->post("valor");
        $products = $this->inventory_model->getProducts($valor);
        echo json_encode($products);
    }

    /**
     * Get single product info via AJAX
     */
    public function getProduct()
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $producto = $this->inventory_model->getProduct($this->input->post("ref"));
        echo json_encode($producto);
    }

    /**
     * Receive merchandise - update inventory
     */
    public function receive($id)
    {
        $this->outh_model->CSRFVerify();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        date_default_timezone_set("America/Bogota");

        $bill = $this->supplierbills_model->getBill($id);

        if (!$bill) {
            $this->session->set_flashdata("error", "Factura no encontrada");
            redirect(base_url() . "sisvent/admin/accountspayable");
            return;
        }

        if (isset($bill->received) && $bill->received == 1) {
            $this->session->set_flashdata("error", "Esta mercancia ya fue recibida");
            redirect(base_url() . "sisvent/admin/accountspayable/view/" . $id);
            return;
        }

        if ($bill->status == 'anulada') {
            $this->session->set_flashdata("error", "No se puede recibir mercancia de una factura anulada");
            redirect(base_url() . "sisvent/admin/accountspayable/view/" . $id);
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];
        $store = $bill->destination_store ?: $bill->storeId;

        // Get product details
        $details = $this->supplierinvoicedetails_model->getDetails($id);

        // Add each product to inventory at destination store
        foreach ($details as $detail) {
            $productoActual = $this->inventory_model->getStoreProduct($store, $detail->productId);

            if (empty($productoActual)) {
                // Product not in this store's inventory yet - create record
                $data = array(
                    'idStore' => $store,
                    'idProduct' => $detail->productId,
                    'stock' => $detail->quantity
                );
                $this->inventory_model->save($data);
            } else {
                // Product exists - add quantity
                $data = array(
                    'stock' => $productoActual->stock + $detail->quantity
                );
                $this->inventory_model->update($store, $detail->productId, $data);
            }
        }

        // Mark invoice as received
        $this->supplierbills_model->markAsReceived($id, $userId);

        // Generate accounting entry: Inventario (143501) ← Mercancía en tránsito (143505)
        $this->accounting_lib->recordSupplierReceive(
            $id,
            $bill->total,
            $store,
            $userId
        );

        $this->logs_model->logMessage("info", "Usuario " . $userId . " recibio mercancia de factura proveedor #" . $bill->invoiceNumber . " en bodega " . $store);
        $this->session->set_flashdata("success", "Mercancia recibida exitosamente. Se actualizaron " . count($details) . " productos en inventario.");
        redirect(base_url() . "sisvent/admin/accountspayable/view/" . $id);
    }
}
