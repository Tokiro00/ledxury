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
        $this->load->model("accountingsettings_model"); // requerido por Accounting_lib para resolver cuentas configuradas
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
        if ((int)($bill->deleted ?? 0) === 1) {
            $this->session->set_flashdata("error", "La factura {$bill->invoiceNumber} fue anulada y ya no es visible.");
            redirect(base_url() . "sisvent/admin/accountspayable");
            return;
        }

        $payments = $this->supplierpayments_model->getPaymentsByInvoice($id);
        $details = $this->supplierinvoicedetails_model->getByInvoice($id);

        $destinationStore = null;
        if (!empty($bill->destination_store)) {
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
     * Cierre de Compra a MAM — modelo de consignación inversa.
     *
     * Ledxury vende sin tener inventario propio (lo surte MAM). On-demand
     * el admin dispara este cierre que:
     *   1. Detecta productos vendidos en invoice_details desde el último
     *      cierre (timestamp guardado en supplier_invoices.notes con
     *      prefijo "CIERRE_MAM_HASTA:YYYY-MM-DD HH:MM:SS").
     *   2. Consolida por SKU, multiplica por products.cost (editable en UI).
     *   3. Al confirmar, crea una supplier_invoice a providerId=12 (MAM)
     *      con el asiento contable (DR Costo Ventas / CR CxP Proveedores).
     *
     * GET  → vista preview con tabla editable
     * POST → crea la supplier_invoice
     */
    public function closeCycleMam()
    {
        $providerMamId = 12; // MAM en tabla providers
        $userId  = $this->session->userdata('user_data')['uname'];

        // Detectar fecha del último cierre MAM
        $lastClosure = $this->db->select('idSupplierInvoice, notes, created_at')
            ->from('supplier_invoices')
            ->where('providerId', $providerMamId)
            ->where('deleted', 0)
            ->where("notes LIKE 'CIERRE_MAM_HASTA:%'", null, false)
            ->order_by('idSupplierInvoice', 'DESC')
            ->limit(1)
            ->get()->row();

        // Default sinceDt: si hay cierre previo usar su fecha, sino reset del
        // inventario (1 mayo 2026 18:06 — primer reset masivo, ver migración).
        $sinceDtDefault = '2026-05-01 18:06:38';
        if ($lastClosure && preg_match('/CIERRE_MAM_HASTA:(\S+ \S+|\S+)/', $lastClosure->notes, $m)) {
            $sinceDtDefault = trim($m[1]);
        }

        // Filtros: aceptar override por GET ?from=YYYY-MM-DD&to=YYYY-MM-DD
        $fromIn = $this->input->get('from');
        $toIn   = $this->input->get('to');
        $sinceDt = $fromIn ? trim($fromIn) . ' 00:00:00' : $sinceDtDefault;
        $untilDt = $toIn   ? trim($toIn)   . ' 23:59:59' : date('Y-m-d H:i:s');

        // POST → procesar (usa $sinceDt/$untilDt actuales)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->_processCloseCycleMam($providerMamId, $sinceDt, $untilDt, $userId);
        }

        // Últimas 5 facturas MAM (para mostrar como historial)
        $prevPurchases = $this->db->select('idSupplierInvoice, invoiceNumber, invoiceDate, total, paidAmount, balance, status, notes, created_at')
            ->from('supplier_invoices')
            ->where('providerId', $providerMamId)
            ->where('deleted', 0)
            ->order_by('idSupplierInvoice', 'DESC')
            ->limit(5)
            ->get()->result();

        // GET preview: consolidar invoice_details desde sinceDt
        // CLAVE: restar stock positivo actual = devoluciones físicas en bodega
        // (productos que el cliente devolvió → MAM no debe facturarlos)
        // Fórmula: a_facturar = vendidos − stock_positivo
        // products.cost está mayormente vacío (legacy). El costo real vive en
        // products.cost_cop (97% poblado). COALESCE para tolerar productos con
        // solo cost legacy seteado.
        $rows = $this->db->query("
            SELECT idi.productId, p.description,
                   COALESCE(NULLIF(p.cost_cop,0), NULLIF(p.cost,0), 0) AS costo_actual,
                   SUM(idi.quantity) AS vendidos,
                   COALESCE((SELECT GREATEST(0, SUM(inv.stock)) FROM inventory inv WHERE inv.idProduct = idi.productId), 0) AS devoluciones,
                   GREATEST(0, SUM(idi.quantity) - COALESCE((SELECT GREATEST(0, SUM(inv.stock)) FROM inventory inv WHERE inv.idProduct = idi.productId), 0)) AS unidades,
                   COUNT(DISTINCT i.idInvoice) AS n_facturas,
                   MIN(i.date) AS primera_venta, MAX(i.date) AS ultima_venta
            FROM invoice_details idi
            JOIN invoices i ON i.idInvoice = idi.invoiceId
            JOIN products p ON p.idProduct = idi.productId
            WHERE i.deleted=0
              AND i.date > ?
              AND i.date <= ?
              AND i.state IN (0,1,2)
            GROUP BY idi.productId
            HAVING unidades > 0
            ORDER BY unidades DESC
        ", array($sinceDt, $untilDt))->result();

        // Bodega por defecto (Medellín = 1)
        $stores = $this->stores_model->getStores();

        $data = array(
            'pageTitle'     => 'Cierre Compra MAM',
            'breadcrumbs'   => array('Finanzas', 'Cuentas por Pagar', 'Cierre MAM'),
            'sinceDt'       => $sinceDt,
            'untilDt'       => $untilDt,
            'fromIn'        => $fromIn,
            'toIn'          => $toIn,
            'rows'          => $rows,
            'stores'        => $stores,
            'lastClosure'   => $lastClosure,
            'prevPurchases' => $prevPurchases,
            'providerMamId' => $providerMamId,
            'role'          => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/accountspayable/cierre_mam', $data);
    }

    private function _processCloseCycleMam($providerMamId, $sinceDt, $untilDt, $userId)
    {
        header('Content-Type: application/json');
        $this->outh_model->CSRFVerify();

        $storeId  = (int)$this->input->post('store_id') ?: 1;
        $items    = $this->input->post('items'); // array: [{productId, qty, cost}, ...]
        $notes    = trim((string)$this->input->post('notes'));

        if (empty($items) || !is_array($items)) {
            echo json_encode(array('success' => false, 'message' => 'Sin productos para cerrar')); return;
        }

        // Validar y calcular total
        $total = 0;
        $clean = array();
        foreach ($items as $it) {
            $pid = trim($it['productId'] ?? '');
            $qty = (int)($it['qty'] ?? 0);
            $cost = (float)($it['cost'] ?? 0);
            if (!$pid || $qty <= 0 || $cost <= 0) continue;
            $sub = $qty * $cost;
            $total += $sub;
            $clean[] = array('productId' => $pid, 'qty' => $qty, 'cost' => $cost, 'subtotal' => $sub);
        }
        if (empty($clean) || $total <= 0) {
            echo json_encode(array('success' => false, 'message' => 'Costos en 0 o cantidades inválidas')); return;
        }

        $this->db->trans_start();

        // 1. Crear supplier_invoice
        $invNumber = 'CIERRE-MAM-' . date('Ymd-His');
        $billData = array(
            'providerId'    => $providerMamId,
            'invoiceNumber' => $invNumber,
            'invoiceDate'   => date('Y-m-d'),
            'dueDate'       => date('Y-m-d', strtotime('+30 days')),
            'total'         => $total,
            'subtotal'      => $total,
            'tax'           => 0,
            'paidAmount'    => 0,
            'balance'       => $total,
            'status'        => 'pendiente',
            'storeId'       => $storeId,
            'received'      => 1, // marcamos recibido — MAM ya entregó
            'received_at'   => date('Y-m-d H:i:s'),
            'received_by'   => $userId,
            'notes'         => 'CIERRE_MAM_HASTA:' . $untilDt . "\n" . $notes,
            'created_at'    => date('Y-m-d H:i:s'),
        );
        $okBill = $this->db->insert('supplier_invoices', $billData);
        $billId = $this->db->insert_id();
        if (!$okBill || !$billId) {
            $err = $this->db->error();
            log_message('error', 'closeCycleMam: insert supplier_invoices failed: ' . json_encode($err));
            $this->db->trans_rollback();
            echo json_encode(array('success' => false, 'message' => 'Error creando factura: ' . ($err['message'] ?? 'desconocido')));
            return;
        }

        // 2. Insertar detalles
        // Schema real (prod + local): id, supplierInvoiceId, productId, description, quantity, unitPrice, total
        foreach ($clean as $it) {
            $okDet = $this->db->insert('supplier_invoice_details', array(
                'supplierInvoiceId' => $billId,
                'productId'         => $it['productId'],
                'description'       => '',
                'quantity'          => $it['qty'],
                'unitPrice'         => $it['cost'],
                'total'             => $it['subtotal'],
            ));
            if (!$okDet) {
                $err = $this->db->error();
                log_message('error', 'closeCycleMam: insert detail failed pid=' . $it['productId'] . ': ' . json_encode($err));
                $this->db->trans_rollback();
                echo json_encode(array('success' => false, 'message' => 'Error en detalle ' . $it['productId'] . ': ' . ($err['message'] ?? 'desconocido')));
                return;
            }
        }

        // 3. Asiento contable: DR Mercancía en tránsito / CR Proveedores
        $entryOk = $this->accounting_lib->recordSupplierBill(
            $billId, $providerMamId, $storeId, $total, $userId
        );

        $this->db->trans_complete();

        if (!$this->db->trans_status() || !$entryOk) {
            log_message('error', 'closeCycleMam: trans failed. trans_status=' . var_export($this->db->trans_status(), true) . ' entryOk=' . var_export($entryOk, true) . ' billId=' . $billId);
            echo json_encode(array(
                'success' => false,
                'message' => 'Falló asiento contable. Revisa que exista la cuenta 143505 (Mercancía en tránsito) y el proveedor MAM (id=' . $providerMamId . ') configurado.',
                'debug'   => array('bill_id' => $billId, 'entry_ok' => $entryOk, 'trans_status' => $this->db->trans_status()),
            ));
            return;
        }

        echo json_encode(array(
            'success'        => true,
            'bill_id'        => $billId,
            'invoice_number' => $invNumber,
            'total'          => $total,
            'items_count'    => count($clean),
            'redirect_url'   => base_url('sisvent/admin/accountspayable/view/' . $billId),
        ));
    }

    /**
     * Anula una factura de proveedor (soft delete) y revierte su asiento contable.
     * Solo permitido si no tiene pagos aplicados.
     */
    public function deleteBill()
    {
        header('Content-Type: application/json');
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(array('success' => false, 'message' => 'Método no permitido')); return;
        }

        $id = (int)$this->input->post('id');
        if (!$id) { echo json_encode(array('success' => false, 'message' => 'ID inválido')); return; }

        $bill = $this->supplierbills_model->getBill($id);
        if (!$bill) { echo json_encode(array('success' => false, 'message' => 'Factura no encontrada')); return; }
        if ((int)$bill->deleted === 1 || $bill->status === 'anulada') {
            echo json_encode(array('success' => false, 'message' => 'La factura ya está anulada')); return;
        }
        if ((float)($bill->paidAmount ?? 0) > 0) {
            echo json_encode(array('success' => false, 'message' => 'No se puede anular: la factura tiene pagos aplicados. Primero revierte los pagos.')); return;
        }

        $userId = $this->session->userdata('user_data')['uname'];

        $this->db->trans_start();

        // 1. Soft delete factura
        $this->db->where('idSupplierInvoice', $id)->update('supplier_invoices', array(
            'deleted'    => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'status'     => 'anulada',
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        // 2. Hard delete detalles (la tabla no tiene flag deleted)
        $this->db->where('supplierInvoiceId', $id)->delete('supplier_invoice_details');

        // 3. Marcar asiento contable como deleted (revierte efecto contable)
        $this->db->where('entryTransactionType', 'supplier_bill')
                 ->where('entryTransactionId', $id)
                 ->where('deleted', 0)
                 ->update('entries', array(
                     'deleted'    => 1,
                     'deleted_at' => date('Y-m-d H:i:s'),
                     'updated_at' => date('Y-m-d H:i:s'),
                 ));

        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            echo json_encode(array('success' => false, 'message' => 'Falló transacción al anular'));
            return;
        }

        echo json_encode(array('success' => true, 'message' => 'Factura anulada'));
    }

    /**
     * Acta de Devolución a MAM — modelo consignación inversa, vía operativa.
     *
     * Lista productos con stock positivo (devoluciones físicas en bodega
     * Ledxury) → admin marca cuánto se entrega a MAM → baja inventory + crea
     * registro en mam_returns. SIN asiento contable porque esos productos
     * nunca entraron a libros (Ledxury opera sin inventario contable; el
     * closeCycleMam ya descuenta el stock al facturar a MAM).
     *
     * GET  → preview con tabla editable
     * POST → crea mam_return + items + baja inventory
     */
    public function returnToMam()
    {
        $providerMamId = 12;
        $userId = $this->session->userdata('user_data')['uname'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->_processReturnToMam($providerMamId, $userId);
        }

        // Filtros de fecha (mismo patrón que closeCycleMam): filtran por
        // inventory.updated_at — la fecha del último movimiento de stock.
        $fromIn = trim((string)$this->input->get('from'));
        $toIn   = trim((string)$this->input->get('to'));

        // Última devolución a MAM (para mostrar contexto del último cierre)
        $lastReturn = $this->db->select('id, return_code, return_date, delivered_at')
            ->where('deleted', 0)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('mam_returns')->row();

        // Resolver rango efectivo
        if ($fromIn) {
            $sinceDt = $fromIn . ' 00:00:00';
        } elseif ($lastReturn && !empty($lastReturn->delivered_at)) {
            $sinceDt = $lastReturn->delivered_at;
        } else {
            $sinceDt = '2026-05-01 00:00:00';
        }
        $untilDt = $toIn ? ($toIn . ' 23:59:59') : date('Y-m-d H:i:s');

        // GET: productos con stock positivo (= devoluciones físicas en bodega).
        // Filtramos por updated_at solo si el user puso rango manual o hay último cierre.
        $sql = "
            SELECT i.idStore, i.idProduct, p.description, i.stock,
                   i.updated_at AS last_change,
                   COALESCE(NULLIF(p.cost_cop,0), NULLIF(p.cost,0), 0) AS unit_cost
            FROM inventory i
            JOIN products p ON p.idProduct = i.idProduct
            WHERE i.stock > 0
              AND i.updated_at > ?
              AND i.updated_at <= ?
            ORDER BY i.stock DESC, p.description ASC
        ";
        $rows = $this->db->query($sql, array($sinceDt, $untilDt))->result();

        // Devoluciones MAM previas (últimas 5 para contexto)
        $prevReturns = $this->db->select('id, return_code, return_date, total_units, total_skus, total_cost, status, delivered_to')
            ->where('deleted', 0)
            ->order_by('id', 'DESC')
            ->limit(5)
            ->get('mam_returns')->result();

        $stores = $this->stores_model->getStores();

        $data = array(
            'pageTitle'    => 'Devolución a MAM',
            'breadcrumbs'  => array('Finanzas', 'Cuentas por Pagar', 'Devolución MAM'),
            'rows'         => $rows,
            'prevReturns'  => $prevReturns,
            'lastReturn'   => $lastReturn,
            'fromIn'       => $fromIn,
            'toIn'         => $toIn,
            'sinceDt'      => $sinceDt,
            'untilDt'      => $untilDt,
            'stores'       => $stores,
            'providerMamId'=> $providerMamId,
            'role'         => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/accountspayable/return_to_mam', $data);
    }

    private function _processReturnToMam($providerMamId, $userId)
    {
        header('Content-Type: application/json');
        $this->outh_model->CSRFVerify();

        $items = $this->input->post('items');
        $notes = trim((string)$this->input->post('notes'));
        $deliveredTo = trim((string)$this->input->post('delivered_to'));

        if (empty($items) || !is_array($items)) {
            echo json_encode(array('success' => false, 'message' => 'Sin productos para devolver')); return;
        }

        // Validar + calcular total con costo de producto
        $clean = array();
        $totalUnits = 0;
        $totalCost = 0;
        foreach ($items as $it) {
            $pid = trim($it['productId'] ?? '');
            $qty = (int)($it['qty'] ?? 0);
            $storeId = (int)($it['storeId'] ?? 1);
            if (!$pid || $qty <= 0) continue;

            // Validar stock + leer costo (cost_cop con fallback a cost legacy)
            $row = $this->db->select('inventory.stock, COALESCE(NULLIF(products.cost_cop,0), NULLIF(products.cost,0), 0) AS unit_cost', false)
                ->from('inventory')
                ->join('products', 'products.idProduct = inventory.idProduct', 'left')
                ->where('inventory.idProduct', $pid)
                ->where('inventory.idStore', $storeId)
                ->limit(1)
                ->get()->row();

            if (!$row || (int)$row->stock < $qty) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Stock insuficiente para ' . $pid . ' (hay ' . ($row->stock ?? 0) . ', pedís ' . $qty . ')',
                )); return;
            }
            $unitCost = (float)$row->unit_cost;
            $clean[] = array(
                'productId' => $pid,
                'qty' => $qty,
                'storeId' => $storeId,
                'unitCost' => $unitCost,
                'subtotal' => $qty * $unitCost,
            );
            $totalUnits += $qty;
            $totalCost  += $qty * $unitCost;
        }
        if (empty($clean)) {
            echo json_encode(array('success' => false, 'message' => 'Sin items válidos')); return;
        }

        $this->db->trans_start();

        $returnCode = 'DEV-MAM-' . date('Ymd-His');
        $this->db->insert('mam_returns', array(
            'provider_id'   => $providerMamId,
            'return_date'   => date('Y-m-d'),
            'return_code'   => $returnCode,
            'total_units'   => $totalUnits,
            'total_skus'    => count($clean),
            'total_cost'    => $totalCost,
            'status'        => 'entregado',
            'notes'         => $notes,
            'created_by'    => $userId,
            'delivered_to'  => $deliveredTo,
            'delivered_at'  => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
        ));
        $returnId = $this->db->insert_id();

        $primaryStoreId = $clean[0]['storeId'];

        foreach ($clean as $it) {
            $this->db->insert('mam_return_items', array(
                'mam_return_id' => $returnId,
                'product_id'    => $it['productId'],
                'store_id'      => $it['storeId'],
                'qty'           => $it['qty'],
                'created_at'    => date('Y-m-d H:i:s'),
            ));
            // Bajar stock
            $this->db->set('stock', 'stock - ' . (int)$it['qty'], false)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('idProduct', $it['productId'])
                ->where('idStore', $it['storeId'])
                ->update('inventory');
        }

        // Asiento contable inverso: DR Proveedores aux MAM / CR Inventario
        $entryId = null;
        $ncBillId = null;
        if ($totalCost > 0) {
            $entryId = $this->accounting_lib->recordSupplierReturn(
                $returnId, $providerMamId, $primaryStoreId, $totalCost, $userId
            );
            if ($entryId) {
                $this->db->where('id', $returnId)->update('mam_returns', array('entry_id' => $entryId));
            }

            // Crear supplier_invoice negativa (visible en la lista de Cuentas por Pagar
            // como "Nota crédito"). Esto reduce el saldo neto con el proveedor.
            $ncNumber = 'NC-MAM-' . date('Ymd-His');
            $this->db->insert('supplier_invoices', array(
                'providerId'    => $providerMamId,
                'invoiceNumber' => $ncNumber,
                'invoiceDate'   => date('Y-m-d'),
                'dueDate'       => date('Y-m-d'),
                'total'         => -1 * $totalCost,
                'subtotal'      => -1 * $totalCost,
                'tax'           => 0,
                'paidAmount'    => 0,
                'balance'       => -1 * $totalCost,
                'status'        => 'pendiente',
                'storeId'       => $primaryStoreId,
                'received'      => 1,
                'received_at'   => date('Y-m-d H:i:s'),
                'received_by'   => $userId,
                'notes'         => 'NOTA_CREDITO_MAM: ref devolución ' . $returnCode . ($notes ? "\n" . $notes : ''),
                'created_at'    => date('Y-m-d H:i:s'),
            ));
            $ncBillId = $this->db->insert_id();
        }

        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            echo json_encode(array('success' => false, 'message' => 'Falló la transacción')); return;
        }

        $msg = 'Devolución registrada por ' . number_format($totalUnits, 0, ',', '.') . ' unidades.';
        if ($totalCost > 0 && $entryId) {
            $msg .= ' Nota crédito al proveedor por $' . number_format($totalCost, 0, ',', '.') . ' (asiento #' . $entryId . ').';
        } else if ($totalCost > 0 && !$entryId) {
            $msg .= ' ⚠ El asiento contable NO se generó. Revisa la configuración contable.';
        } else {
            $msg .= ' Sin costo registrado: no se generó nota crédito.';
        }

        echo json_encode(array(
            'success'     => true,
            'return_id'   => $returnId,
            'return_code' => $returnCode,
            'total_units' => $totalUnits,
            'total_cost'  => $totalCost,
            'total_skus'  => count($clean),
            'entry_id'    => $entryId,
            'message'     => $msg,
            'pdf_url'     => base_url('sisvent/admin/accountspayable/returnPdf/' . $returnId),
        ));
    }

    /**
     * Acta imprimible de devolución (HTML para print).
     */
    public function returnPdf($id)
    {
        $id = (int)$id;
        $return = $this->db->where('id', $id)->where('deleted', 0)->get('mam_returns')->row();
        if (!$return) show_404();

        $items = $this->db->select('mri.*, p.description, s.name AS store_name')
            ->from('mam_return_items mri')
            ->join('products p', 'p.idProduct = mri.product_id', 'left')
            ->join('stores s', 's.idStore = mri.store_id', 'left')
            ->where('mri.mam_return_id', $id)
            ->order_by('mri.id', 'ASC')
            ->get()->result();

        $provider = $this->db->where('idProvider', $return->provider_id)->get('providers')->row();

        $this->load->view('sisvent/admin/accountspayable/return_pdf', array(
            'return'   => $return,
            'items'    => $items,
            'provider' => $provider,
            'role'     => $this->session->userdata('user_data')['role'],
        ));
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
        $details = $this->supplierinvoicedetails_model->getByInvoice($id);

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
