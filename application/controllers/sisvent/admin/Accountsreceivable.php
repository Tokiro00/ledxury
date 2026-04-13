<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accountsreceivable extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('cartera');
        $this->load->model('invoices_model');
        $this->load->model('clients_model');
        $this->load->model('stores_model');
        $this->load->model('users_model');
        $this->load->model('payments_model');
        $this->load->model('cashboxes_model');
        $this->load->model('bankaccounts_model');
        $this->load->model('cashmovements_model');
        $this->load->library('accounting_lib');
    }

    /**
     * List all accounts receivable with aging report
     */
    public function index()
    {
        $page = $this->input->get('p') ?: 1;
        $limit = 50;

        // Filters
        $clientId = $this->input->get('client') ?: null;
        $storeId = $this->input->get('store') ?: null;
        $vendorId = $this->input->get('vendor') ?: null;

        // Vendedores solo ven sus clientes
        $role = $this->session->userdata('user_data')['role'];
        if ($role == 3) {
            $vendorId = $this->session->userdata('user_data')['uname'];
        }

        // Get aging summary
        $aging = $this->invoices_model->getAccountsReceivableAging($clientId, $storeId, $vendorId);

        // Get paginated receivables
        $total = $this->invoices_model->getTotalAccountsReceivable($clientId, $storeId, $vendorId);
        $last = ceil($total / $limit);
        if ($page > $last && $last > 0) $page = $last;
        if ($page <= 0) $page = 1;

        $receivables = $this->invoices_model->getAccountsReceivable($clientId, $storeId, $vendorId, $page, $limit);

        // Get filter options
        $clients = $this->clients_model->getClients();
        $stores = $this->stores_model->getStores();
        $vendors = $this->users_model->getUsers(false);

        $data = array(
            'receivables' => $receivables,
            'aging' => $aging,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'clients' => $clients,
            'stores' => $stores,
            'vendors' => $vendors,
            'filter_client' => $clientId,
            'filter_store' => $storeId,
            'filter_vendor' => $vendorId
        );

        $this->load->view('sisvent/admin/accountsreceivable/list', $data);
    }

    /**
     * View receivables grouped by client
     */
    public function byClient()
    {
        $storeId = $this->input->get('store') ?: null;
        $vendorId = $this->input->get('vendor') ?: null;

        // Vendedores solo ven sus clientes
        $role = $this->session->userdata('user_data')['role'];
        if ($role == 3) {
            $vendorId = $this->session->userdata('user_data')['uname'];
        }

        // Get aging summary
        $aging = $this->invoices_model->getAccountsReceivableAging(null, $storeId, $vendorId);

        // Get receivables by client
        $clients_receivables = $this->invoices_model->getAccountsReceivableByClient($storeId, $vendorId);

        // Get filter options
        $stores = $this->stores_model->getStores();
        $vendors = $this->users_model->getUsers(false);

        $data = array(
            'clients_receivables' => $clients_receivables,
            'aging' => $aging,
            'stores' => $stores,
            'vendors' => $vendors,
            'filter_store' => $storeId,
            'filter_vendor' => $vendorId
        );

        $this->load->view('sisvent/admin/accountsreceivable/by_client', $data);
    }

    /**
     * View details for a specific client
     */
    public function clientDetail($clientId)
    {
        if (empty($clientId)) {
            redirect('sisvent/admin/accountsreceivable');
        }

        // Get client info
        $client = $this->clients_model->getClient($clientId);
        if (empty($client)) {
            $this->session->set_flashdata('error', 'Cliente no encontrado');
            redirect('sisvent/admin/accountsreceivable');
        }

        // Get all pending invoices for this client
        $receivables = $this->invoices_model->getAccountsReceivable($clientId, null, null, 1, 1000);

        // Get aging for this client
        $aging = $this->invoices_model->getAccountsReceivableAging($clientId);

        $data = array(
            'client' => $client,
            'receivables' => $receivables,
            'aging' => $aging
        );

        $this->load->view('sisvent/admin/accountsreceivable/client_detail', $data);
    }

    /**
     * AJAX endpoint for getting client receivables data
     */
    public function getClientReceivables()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Direct access not allowed', 403);
        }

        $clientId = $this->input->post('client_id');

        if (empty($clientId)) {
            echo json_encode(array('success' => false, 'message' => 'ID de cliente requerido'));
            return;
        }

        $receivables = $this->invoices_model->getAccountsReceivable($clientId, null, null, 1, 100);
        $aging = $this->invoices_model->getAccountsReceivableAging($clientId);

        echo json_encode(array(
            'success' => true,
            'receivables' => $receivables,
            'aging' => $aging
        ));
    }

    /**
     * Cartera completa por tienda y vendedor
     */
    public function byStore()
    {
        // Vendedores solo ven sus clientes
        $role = $this->session->userdata('user_data')['role'];
        $vendorFilter = null;
        if ($role == 3) {
            $vendorFilter = $this->session->userdata('user_data')['uname'];
        }

        $rows = $this->invoices_model->getDebtByStoreAndVendor($vendorFilter);
        $aging = $this->invoices_model->getAccountsReceivableAging(null, null, $vendorFilter);

        // Agrupar por tienda
        $storesData = array();
        foreach ($rows as $r) {
            $sid = $r->storeId;
            if (!isset($storesData[$sid])) {
                $storesData[$sid] = array(
                    'storeId' => $sid,
                    'store_name' => $r->store_name,
                    'total_debt' => 0, 'debt_over_90' => 0, 'debt_61_90' => 0,
                    'debt_31_60' => 0, 'debt_0_30' => 0,
                    'total_invoiced' => 0, 'total_paid' => 0,
                    'client_count' => 0, 'invoice_count' => 0,
                    'vendors' => array()
                );
            }
            $storesData[$sid]['total_debt'] += (float)$r->total_debt;
            $storesData[$sid]['debt_over_90'] += (float)$r->debt_over_90;
            $storesData[$sid]['debt_61_90'] += (float)$r->debt_61_90;
            $storesData[$sid]['debt_31_60'] += (float)$r->debt_31_60;
            $storesData[$sid]['debt_0_30'] += (float)$r->debt_0_30;
            $storesData[$sid]['total_invoiced'] += (float)$r->total_invoiced;
            $storesData[$sid]['total_paid'] += (float)$r->total_paid;
            $storesData[$sid]['client_count'] += (int)$r->client_count;
            $storesData[$sid]['invoice_count'] += (int)$r->invoice_count;
            $storesData[$sid]['vendors'][] = $r;
        }

        // Ordenar tiendas por deuda DESC
        uasort($storesData, function($a, $b) { return $b['total_debt'] <=> $a['total_debt']; });

        $data = array(
            'storesData' => $storesData,
            'aging' => $aging
        );
        $this->load->view('sisvent/admin/accountsreceivable/by_store', $data);
    }

    // ========================================================================
    // PAGO RAPIDO DESDE CARTERA
    // ========================================================================

    /**
     * AJAX: Return payment form HTML for a specific invoice
     */
    public function quickPayment()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Acceso directo no permitido', 403);
        }

        $idInvoice = $this->input->post('id');
        if (empty($idInvoice)) {
            echo '<p class="text-red-600 text-sm">ID de factura requerido</p>';
            return;
        }

        $invoice = $this->invoices_model->getInvoice($idInvoice);
        if (empty($invoice)) {
            echo '<p class="text-red-600 text-sm">Factura no encontrada</p>';
            return;
        }

        $data = array(
            'invoice' => $invoice,
            'methods' => $this->payments_model->getPaymentMethods(),
            'cashboxes' => $this->cashboxes_model->getCashboxesByStore($invoice->storeId),
            'bankaccounts' => $this->bankaccounts_model->getBankAccountsByStore($invoice->storeId)
        );

        $this->load->view('sisvent/admin/accountsreceivable/payment_modal', $data);
    }

    /**
     * POST: Make payment from cartera view
     * Replicates the 6-step payment flow from Invoices controller
     */
    public function makePayment()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $idInvoice = $this->input->post('id');
        $method = $this->input->post('method');
        $payment = (float)$this->input->post('payment');
        $comment = $this->input->post('comment');
        $date = $this->input->post('date');
        $cashSourceTypeRaw = $this->input->post('cash_source_type');
        $cashSourceId = ($cashSourceTypeRaw == 'cashbox')
            ? $this->input->post('cash_source_cashbox')
            : $this->input->post('cash_source_bank');
        $cashSourceType = ($cashSourceTypeRaw == 'cashbox') ? 'caja' : 'banco';
        $returnTo = $this->input->post('return_to') ?: 'list';

        if (!$date)
            $date = date('Y-m-d H:i:s');

        $invoice = $this->invoices_model->getInvoice($idInvoice);
        if (empty($invoice)) {
            echo json_encode(array('success' => false, 'message' => 'Factura no encontrada'));
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];

        // 1. Guardar pago
        $data = array(
            'invoiceId' => $idInvoice,
            'clientId' => $invoice->clientId,
            'vendorId' => $invoice->vendorId,
            'paymentMethod' => $method,
            'payment' => $payment,
            'date' => date('Y-m-d H:i:s', strtotime($date)),
            'comments' => $comment
        );

        $this->payments_model->save($data);
        $paymentId = $this->db->insert_id();

        // 2. Actualizar factura
        $acum = $this->payments_model->getInvoicePayment($idInvoice);
        $this->invoices_model->update($idInvoice, array(
            'payment' => $acum->payment,
            'state' => $invoice->list_price
                ? ($acum->payment >= ($invoice->total * 0.7) ? 2 : ($acum->payment == 0 ? 0 : 1))
                : (($acum->payment + $invoice->discount) >= round($invoice->total, 2) ? 2 : ($acum->payment == 0 ? 0 : 1)),
        ));

        // 3. Crear movimiento de caja/banco (ingreso)
        $movementData = array(
            'sourceType' => $cashSourceType,
            'sourceId' => $cashSourceId,
            'movementType' => 'ingreso',
            'movementDate' => date('Y-m-d H:i:s', strtotime($date)),
            'amount' => $payment,
            'concept' => "Pago Cartera - Factura #" . str_pad($idInvoice, 6, "0", STR_PAD_LEFT),
            'category' => 'pago',
            'documentNumber' => $idInvoice,
            'referenceType' => 'payment',
            'referenceId' => $paymentId,
            'status' => 'ejecutado'
        );

        $this->cashmovements_model->save($movementData);
        $movementId = $this->cashmovements_model->lastID();

        // 4. Vincular pago con movimiento
        $this->payments_model->update($paymentId, array('cashMovementId' => $movementId));

        // 5. Actualizar saldo de caja/banco
        if ($cashSourceType == 'caja') {
            $this->cashboxes_model->updateBalance($cashSourceId, $payment, 'add');
        } else {
            $this->bankaccounts_model->updateBalance($cashSourceId, $payment, 'add');
        }

        // 6. Registrar asiento contable via Accounting_lib
        $cashAccountId = ($cashSourceType == 'caja')
            ? $this->accounting_lib->getCashAccount($invoice->storeId)
            : $this->accounting_lib->getBankAccount($invoice->storeId);

        $this->accounting_lib->recordPayment(
            $paymentId,
            $idInvoice,
            $invoice->clientId,
            $payment,
            $method,
            $invoice->storeId,
            $userId,
            $cashAccountId
        );

        $this->logs_model->logMessage("info", "Usuario $userId hizo pago cartera factura $idInvoice por $" . number_format($payment, 0, ',', '.'));

        echo json_encode(array(
            'success' => true,
            'message' => 'Pago registrado correctamente por $' . number_format($payment, 0, ',', '.'),
            'paymentId' => $paymentId
        ));
    }

    /**
     * POST: Distribute payment across oldest invoices first (FIFO)
     */
    public function multiPayment($clientId)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        if (empty($clientId)) {
            echo json_encode(array('success' => false, 'message' => 'Cliente requerido'));
            return;
        }

        $totalPayment = (float)$this->input->post('total_payment');
        $method = $this->input->post('method');
        $comment = $this->input->post('comment');
        $date = $this->input->post('date');
        $cashSourceTypeRaw = $this->input->post('cash_source_type');
        $cashSourceId = ($cashSourceTypeRaw == 'cashbox')
            ? $this->input->post('cash_source_cashbox')
            : $this->input->post('cash_source_bank');
        $cashSourceType = ($cashSourceTypeRaw == 'cashbox') ? 'caja' : 'banco';

        if (!$date) $date = date('Y-m-d H:i:s');
        if ($totalPayment <= 0) {
            echo json_encode(array('success' => false, 'message' => 'El monto debe ser mayor a cero'));
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];

        // Get oldest pending invoices for this client (FIFO)
        $receivables = $this->invoices_model->getAccountsReceivable($clientId, null, null, 1, 1000);

        if (empty($receivables)) {
            echo json_encode(array('success' => false, 'message' => 'No hay facturas pendientes para este cliente'));
            return;
        }

        // Sort by date ascending (oldest first)
        usort($receivables, function($a, $b) {
            return strtotime($a->date) - strtotime($b->date);
        });

        $remaining = $totalPayment;
        $paymentsApplied = array();

        foreach ($receivables as $inv) {
            if ($remaining <= 0) break;

            $balance = (float)$inv->balance;
            if ($balance <= 0) continue;

            $applyAmount = min($remaining, $balance);

            // 1. Save payment
            $payData = array(
                'invoiceId' => $inv->idInvoice,
                'clientId' => $clientId,
                'vendorId' => $inv->vendorId,
                'paymentMethod' => $method,
                'payment' => $applyAmount,
                'date' => date('Y-m-d H:i:s', strtotime($date)),
                'comments' => $comment ?: 'Pago multiple cartera'
            );
            $this->payments_model->save($payData);
            $paymentId = $this->db->insert_id();

            // 2. Update invoice
            $acum = $this->payments_model->getInvoicePayment($inv->idInvoice);
            $this->invoices_model->update($inv->idInvoice, array(
                'payment' => $acum->payment,
                'state' => $inv->list_price
                    ? ($acum->payment >= ($inv->total * 0.7) ? 2 : ($acum->payment == 0 ? 0 : 1))
                    : (($acum->payment + $inv->discount) >= round($inv->total, 2) ? 2 : ($acum->payment == 0 ? 0 : 1)),
            ));

            // 3. Cash movement
            $movementData = array(
                'sourceType' => $cashSourceType,
                'sourceId' => $cashSourceId,
                'movementType' => 'ingreso',
                'movementDate' => date('Y-m-d H:i:s', strtotime($date)),
                'amount' => $applyAmount,
                'concept' => "Pago Multiple Cartera - Factura #" . str_pad($inv->idInvoice, 6, "0", STR_PAD_LEFT),
                'category' => 'pago',
                'documentNumber' => $inv->idInvoice,
                'referenceType' => 'payment',
                'referenceId' => $paymentId,
                'status' => 'ejecutado'
            );
            $this->cashmovements_model->save($movementData);
            $movementId = $this->cashmovements_model->lastID();

            // 4. Link payment to movement
            $this->payments_model->update($paymentId, array('cashMovementId' => $movementId));

            // 5. Update balance
            if ($cashSourceType == 'caja') {
                $this->cashboxes_model->updateBalance($cashSourceId, $applyAmount, 'add');
            } else {
                $this->bankaccounts_model->updateBalance($cashSourceId, $applyAmount, 'add');
            }

            // 6. Accounting entry
            $cashAccountId = ($cashSourceType == 'caja')
                ? $this->accounting_lib->getCashAccount($inv->storeId)
                : $this->accounting_lib->getBankAccount($inv->storeId);

            $this->accounting_lib->recordPayment(
                $paymentId,
                $inv->idInvoice,
                $clientId,
                $applyAmount,
                $method,
                $inv->storeId,
                $userId,
                $cashAccountId
            );

            $paymentsApplied[] = array(
                'invoiceId' => $inv->idInvoice,
                'amount' => $applyAmount
            );

            $remaining -= $applyAmount;
        }

        $appliedTotal = $totalPayment - $remaining;
        $this->logs_model->logMessage("info", "Usuario $userId hizo pago multiple cartera cliente $clientId por $" . number_format($appliedTotal, 0, ',', '.') . " (" . count($paymentsApplied) . " facturas)");

        echo json_encode(array(
            'success' => true,
            'message' => 'Pago distribuido: $' . number_format($appliedTotal, 0, ',', '.') . ' en ' . count($paymentsApplied) . ' facturas',
            'payments' => $paymentsApplied,
            'remaining' => $remaining
        ));
    }
}
