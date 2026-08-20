<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cxp_model — Cuentas por Pagar a proveedores.
 * Portado de stockaccessories.co (20/08/2026) y adaptado a Ledxury:
 *
 *  - Moneda base COP (el fork de origen usaba USD). Multi-moneda se conserva:
 *    exchange_rate = pesos por unidad de la moneda de la factura.
 *  - Pagos salen de la tesorería de Ledxury: source_type 'caja'|'banco' +
 *    source_id (cashboxes / bank_accounts) con su cash_movement y su asiento.
 *  - La CxP contable lleva auxiliar por proveedor (acc_provider_aux).
 *
 * Cubre: provider_invoices, provider_payments, y el aging del panel CxP.
 */
class Cxp_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('compras');
    }

    // ════════════════════════════════════════════════════════════════
    // INVOICES (Facturas de Proveedor / CxP)
    // ════════════════════════════════════════════════════════════════

    /** Crea una factura de proveedor abierta (mercancía ya recibida/entrada). */
    public function createInvoice(array $data): int
    {
        $row = $this->_invoiceRow($data);
        $row['paid'] = 0;
        $row['status'] = 'open';
        $this->db->insert('provider_invoices', $row);
        $invoiceId = (int) $this->db->insert_id();

        foreach (($data['items'] ?? array()) as $it) {
            $it['provider_invoice_id'] = $invoiceId;
            $this->db->insert('provider_invoice_items', $it);
        }

        // Asiento de compra: DR Inventario / CR Proveedores + aux del proveedor
        acc_entry(array(
            'description'      => 'Compra factura proveedor ' . $row['inv_code'],
            'date'             => $row['issue_date'],
            'transaction_type' => 'provider_invoice',
            'transaction_id'   => $invoiceId,
            'debit'            => acc_setting('account_inventory'),
            'credit'           => acc_setting('account_payable'),
            'credit_aux'       => acc_provider_aux($row['provider_id']),
            'amount'           => $this->toBase($row['total'], $row['currency'], $row['exchange_rate']),
            'user'             => $data['created_by'] ?? 'system',
        ));
        return $invoiceId;
    }

    /**
     * Crea una factura EN TRÁNSITO: la deuda se registra al despachar
     * (DR Mercancía en tránsito / CR Proveedores); el inventario entra al
     * recibir. Es la ruta del puente con accesoriosmam.
     */
    public function createTransitInvoice(array $data, array $items = array()): int
    {
        $row = $this->_invoiceRow($data);
        $row['paid'] = 0;
        $row['status'] = 'en_transito';
        $this->db->insert('provider_invoices', $row);
        $invoiceId = (int) $this->db->insert_id();

        foreach ($items as $it) {
            $it['provider_invoice_id'] = $invoiceId;
            $this->db->insert('provider_invoice_items', $it);
        }

        acc_entry(array(
            'description'      => 'Mercancía en tránsito · factura ' . $row['inv_code'],
            'date'             => $row['issue_date'],
            'transaction_type' => 'provider_invoice_transit',
            'transaction_id'   => $invoiceId,
            'debit'            => acc_setting('account_inventory_transit') ?: acc_setting('account_inventory'),
            'credit'           => acc_setting('account_payable'),
            'credit_aux'       => acc_provider_aux($row['provider_id']),
            'amount'           => $this->toBase($row['total'], $row['currency'], $row['exchange_rate']),
            'user'             => $data['created_by'] ?? 'system',
        ));
        return $invoiceId;
    }

    /**
     * Actualiza una factura no recibida/no pagada y re-postea su asiento
     * (anula el anterior con reverso de saldos y crea uno nuevo).
     */
    public function updateInvoice(int $id, array $data): bool
    {
        $prev = $this->db->get_where('provider_invoices', array('id' => $id))->row();
        if (!$prev) return false;

        $row = $this->_invoiceRow($data);
        $row['updated_at'] = date('Y-m-d H:i:s');
        unset($row['created_at'], $row['created_by'], $row['deleted']);
        $this->db->where('id', $id)->update('provider_invoices', $row);

        $wasTransit = ($prev->status === 'en_transito');
        acc_void_entries($id, array($wasTransit ? 'provider_invoice_transit' : 'provider_invoice'), 'reemplazado por edición');

        acc_entry(array(
            'description'      => ($wasTransit ? 'Mercancía en tránsito · factura ' : 'Compra factura proveedor ') . $row['inv_code'] . ' (editada)',
            'date'             => $row['issue_date'],
            'transaction_type' => $wasTransit ? 'provider_invoice_transit' : 'provider_invoice',
            'transaction_id'   => $id,
            'debit'            => $wasTransit ? (acc_setting('account_inventory_transit') ?: acc_setting('account_inventory')) : acc_setting('account_inventory'),
            'credit'           => acc_setting('account_payable'),
            'credit_aux'       => acc_provider_aux($row['provider_id']),
            'amount'           => $this->toBase($row['total'], $row['currency'], $row['exchange_rate']),
            'user'             => $data['created_by'] ?? 'system',
        ));

        $this->recalcInvoice($id);
        return true;
    }

    private function _invoiceRow(array $data): array
    {
        return array(
            'inv_code'      => $data['inv_code'],
            'provider_id'   => (int) $data['provider_id'],
            'po_id'         => !empty($data['po_id']) ? (int) $data['po_id'] : null,
            'issue_date'    => $data['issue_date'],
            'due_date'      => !empty($data['due_date']) ? $data['due_date'] : null,
            'currency'      => $data['currency'] ?? 'COP',
            'exchange_rate' => (float) ($data['exchange_rate'] ?? 1),
            'subtotal'      => (float) ($data['subtotal'] ?? 0),
            'tax'           => (float) ($data['tax'] ?? 0),
            'withholding'   => (float) ($data['withholding'] ?? 0),
            'total'         => (float) ($data['total'] ?? 0),
            'financing_pct' => isset($data['financing_pct']) && $data['financing_pct'] !== '' ? (float) $data['financing_pct'] : null,
            'origin_ref'    => $data['origin_ref'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'created_by'    => $data['created_by'] ?? null,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
            'deleted'       => 0,
        );
    }

    /** Convierte un monto a COP con la tasa dada. */
    public function toBase($amount, $currency, $rate): float
    {
        return ($currency === 'COP' || !$currency) ? (float) $amount : (float) $amount * ((float) $rate ?: 1);
    }

    public function getInvoice(int $id): ?object
    {
        $row = $this->db->query("
            SELECT pi.*, p.name AS provider_name,
                   (pi.total - pi.paid) AS balance,
                   DATEDIFF(CURDATE(), pi.issue_date) AS age_days,
                   (SELECT COUNT(*) FROM provider_payments pp
                      WHERE pp.invoice_id = pi.id AND COALESCE(pp.deleted,0)=0
                        AND pp.source_id IS NOT NULL) AS cash_payments
            FROM provider_invoices pi
            JOIN providers p ON p.idProvider = pi.provider_id
            WHERE pi.id = ? AND COALESCE(pi.deleted,0) = 0
        ", array($id))->row();
        return $row ?: null;
    }

    public function getItems(int $invoiceId): array
    {
        return $this->db->query("
            SELECT pii.*, pr.description AS product_name
            FROM provider_invoice_items pii
            LEFT JOIN products pr ON pr.idProduct = pii.product_id
            WHERE pii.provider_invoice_id = ?
            ORDER BY pii.id
        ", array($invoiceId))->result();
    }

    public function listInvoices(array $filters = array()): array
    {
        $where = array("COALESCE(pi.deleted,0) = 0");
        $bind  = array();
        if (!empty($filters['provider_id'])) { $where[] = "pi.provider_id = ?"; $bind[] = (int) $filters['provider_id']; }
        if (!empty($filters['status']))      { $where[] = "pi.status = ?";      $bind[] = $filters['status']; }
        if (!empty($filters['open_only']))   { $where[] = "pi.status IN ('open','paid_partial')"; }
        $whereSql = implode(' AND ', $where);
        return $this->db->query("
            SELECT pi.*, p.name AS provider_name,
                   (pi.total - pi.paid) AS balance,
                   DATEDIFF(CURDATE(), pi.issue_date) AS age_days,
                   (SELECT COUNT(*) FROM provider_payments pp
                      WHERE pp.invoice_id = pi.id AND COALESCE(pp.deleted,0)=0
                        AND pp.source_id IS NOT NULL) AS cash_payments,
                   CASE WHEN pi.due_date IS NOT NULL AND pi.due_date < CURDATE() AND pi.status != 'paid'
                        THEN DATEDIFF(CURDATE(), pi.due_date) ELSE 0 END AS days_overdue
            FROM provider_invoices pi
            JOIN providers p ON p.idProvider = pi.provider_id
            WHERE {$whereSql}
            ORDER BY pi.issue_date DESC, pi.id DESC
        ", $bind)->result();
    }

    /** Recalcula paid y status sumando los pagos vivos. */
    public function recalcInvoice(int $invoiceId): void
    {
        $paid = (float) $this->db->query("
            SELECT COALESCE(SUM(amount_invoice_currency), 0) AS paid
            FROM provider_payments
            WHERE invoice_id = ? AND COALESCE(deleted,0) = 0
        ", array($invoiceId))->row()->paid;

        $inv = $this->db->get_where('provider_invoices', array('id' => $invoiceId))->row();
        if (!$inv) return;
        if ($inv->status === 'en_transito' && $paid < 0.01) return; // en tránsito no cambia por pagos en 0

        $status = ($inv->status === 'en_transito') ? 'en_transito' : 'open';
        if ($paid >= (float) $inv->total - 0.01) $status = 'paid';
        elseif ($paid > 0.01 && $status !== 'en_transito') $status = 'paid_partial';

        $this->db->where('id', $invoiceId)->update('provider_invoices', array(
            'paid' => $paid, 'status' => $status, 'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    // ════════════════════════════════════════════════════════════════
    // PAYMENTS (Pagos a Proveedor desde cajas/bancos de Ledxury)
    // ════════════════════════════════════════════════════════════════

    /**
     * Registra un pago. source_type 'caja'|'banco' + source_id descargan la
     * tesorería (cash_movement egreso + saldo) y el asiento acredita la
     * subcuenta contable de caja o banco.
     */
    public function createPayment(array $data): int
    {
        $invoice = $this->getInvoice((int) $data['invoice_id']);
        if (!$invoice) throw new Exception('Factura de proveedor no existe.');

        $sourceType = !empty($data['source_type']) ? $data['source_type'] : null;
        $sourceId   = !empty($data['source_id']) ? (int) $data['source_id'] : null;
        if ($sourceType && !in_array($sourceType, array('caja', 'banco'), true)) {
            throw new Exception('Origen de pago inválido.');
        }
        // Pagos desde tesorería de Ledxury siempre son en COP
        $payCurrency = ($sourceType) ? 'COP' : ($data['currency'] ?? $invoice->currency);
        $payRate     = (float) ($data['exchange_rate'] ?? 1);
        $payAmount   = (float) ($data['amount'] ?? 0);
        if ($payAmount <= 0) throw new Exception('Monto inválido.');

        if ($payCurrency === $invoice->currency) {
            $amountInInvoiceCurrency = $payAmount;
            $fxDiff = 0;
        } else {
            $invoiceRate = (float) $invoice->exchange_rate ?: 1;
            // pago COP → moneda factura: dividir por la tasa de la factura;
            // pago en otra moneda: pasar por COP primero.
            $payInBase = $this->toBase($payAmount, $payCurrency, $payRate);
            $amountInInvoiceCurrency = $payInBase / $invoiceRate;
            $fxDiff = 0; // diferencia en cambio se maneja al cierre, no por pago
        }

        $this->db->trans_start();

        $payCode = $data['pay_code'] ?? $this->nextPayCode();
        $row = array(
            'pay_code'                => $payCode,
            'invoice_id'              => (int) $data['invoice_id'],
            'provider_id'             => (int) $invoice->provider_id,
            'pay_date'                => $data['pay_date'] ?? date('Y-m-d'),
            'currency'                => $payCurrency,
            'exchange_rate'           => $payRate,
            'amount'                  => $payAmount,
            'amount_invoice_currency' => $amountInInvoiceCurrency,
            'payment_method'          => $data['payment_method'] ?? null,
            'source_type'             => $sourceType,
            'source_id'               => $sourceId,
            'reference'               => $data['reference'] ?? null,
            'fx_diff'                 => $fxDiff,
            'notes'                   => $data['notes'] ?? null,
            'created_by'              => $data['created_by'] ?? null,
            'created_at'              => date('Y-m-d H:i:s'),
            'deleted'                 => 0,
        );
        $this->db->insert('provider_payments', $row);
        $id = (int) $this->db->insert_id();

        // Tesorería: egreso + saldo
        $movId = null;
        if ($sourceType && $sourceId) {
            $this->db->insert('cash_movements', array(
                'sourceType'     => $sourceType,
                'sourceId'       => $sourceId,
                'movementType'   => 'egreso',
                'amount'         => $payAmount,
                'concept'        => 'Pago a ' . $invoice->provider_name . ' · factura ' . $invoice->inv_code . ' (' . $payCode . ')',
                'category'       => 'pago_proveedor',
                'documentNumber' => $payCode,
                'movementDate'   => ($data['pay_date'] ?? date('Y-m-d')) . ' ' . date('H:i:s'),
                'status'         => 'ejecutado',
                'referenceType'  => 'provider_payment',
                'referenceId'    => $id,
                'created_by'     => $data['created_by'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ));
            $movId = (int) $this->db->insert_id();
            $this->db->where('id', $id)->update('provider_payments', array('cash_movement_id' => $movId));

            if ($sourceType === 'banco') {
                $this->db->query("UPDATE bank_accounts SET currentBalance = currentBalance - ? WHERE idBankAccount = ?", array($payAmount, $sourceId));
            } else {
                $this->db->query("UPDATE cashboxes SET currentBalance = currentBalance - ? WHERE idCashbox = ?", array($payAmount, $sourceId));
            }
        }

        $this->recalcInvoice((int) $data['invoice_id']);

        // Asiento: DR Proveedores + aux / CR la subcuenta de la caja/banco usada
        // (cashboxes/bank_accounts.subaccountId), con fallback a la genérica.
        $creditAcc = null;
        if ($sourceType === 'banco' && $sourceId) {
            $r = $this->db->query("SELECT subaccountId FROM bank_accounts WHERE idBankAccount = ?", array($sourceId))->row();
            $creditAcc = $r && (int) $r->subaccountId > 0 ? (int) $r->subaccountId : null;
        } elseif ($sourceType === 'caja' && $sourceId) {
            $r = $this->db->query("SELECT subaccountId FROM cashboxes WHERE idCashbox = ?", array($sourceId))->row();
            $creditAcc = $r && (int) $r->subaccountId > 0 ? (int) $r->subaccountId : null;
        }
        if (!$creditAcc) {
            $creditAcc = ($sourceType === 'caja') ? acc_setting('account_cash') : acc_setting('account_bank');
        }
        acc_entry(array(
            'description'      => 'Pago ' . $payCode . ' a ' . $invoice->provider_name . ' · factura ' . $invoice->inv_code,
            'date'             => $row['pay_date'],
            'transaction_type' => 'provider_payment',
            'transaction_id'   => $id,
            'debit'            => acc_setting('account_payable'),
            'debit_aux'        => acc_provider_aux((int) $invoice->provider_id),
            'credit'           => $creditAcc,
            'amount'           => $this->toBase($payAmount, $payCurrency, $payRate),
            'user'             => $data['created_by'] ?? 'system',
        ));

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            throw new Exception('Error registrando el pago. Transacción revertida.');
        }
        return $id;
    }

    public function listPaymentsForInvoice(int $invoiceId): array
    {
        return $this->db->query("
            SELECT pp.*,
                   CASE WHEN pp.source_type = 'banco' THEN (SELECT ba.bankName FROM bank_accounts ba WHERE ba.idBankAccount = pp.source_id)
                        WHEN pp.source_type = 'caja'  THEN (SELECT cb.name FROM cashboxes cb WHERE cb.idCashbox = pp.source_id)
                        ELSE NULL END AS cash_account_name
            FROM provider_payments pp
            WHERE pp.invoice_id = ? AND COALESCE(pp.deleted,0) = 0
            ORDER BY pp.pay_date DESC, pp.id DESC
        ", array($invoiceId))->result();
    }

    public function getPayment(int $id)
    {
        return $this->db->query("
            SELECT pp.*, pi.inv_code, pi.currency AS invoice_currency, p.name AS provider_name
            FROM provider_payments pp
            INNER JOIN provider_invoices pi ON pi.id = pp.invoice_id
            LEFT JOIN providers p ON p.idProvider = pp.provider_id
            WHERE pp.id = ? AND COALESCE(pp.deleted,0) = 0
            LIMIT 1
        ", array($id))->row();
    }

    /** Soft-delete de un pago: revierte tesorería, asiento y saldo de factura. */
    public function deletePayment(int $id, ?string $by = null): void
    {
        $payment = $this->db->get_where('provider_payments', array('id' => $id))->row();
        if (!$payment) throw new Exception('Pago no existe.');
        if ((int) $payment->deleted === 1) return;

        $this->db->trans_start();

        if ($payment->cash_movement_id) {
            $mov = $this->db->get_where('cash_movements', array('idMovement' => (int) $payment->cash_movement_id))->row();
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

        $this->db->where('id', $id)->update('provider_payments', array(
            'deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $by,
        ));

        acc_void_entries($id, array('provider_payment'), 'anulado: pago a proveedor eliminado');
        $this->recalcInvoice((int) $payment->invoice_id);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            throw new Exception('Error al eliminar el pago. Transacción revertida.');
        }
    }

    public function listAllPayments(array $filters = array()): array
    {
        $where = array("COALESCE(pp.deleted,0) = 0");
        $bind  = array();
        if (!empty($filters['provider_id'])) { $where[] = "pp.provider_id = ?"; $bind[] = (int) $filters['provider_id']; }
        if (!empty($filters['date_from']))   { $where[] = "pp.pay_date >= ?";   $bind[] = $filters['date_from']; }
        if (!empty($filters['date_to']))     { $where[] = "pp.pay_date <= ?";   $bind[] = $filters['date_to']; }
        $whereSql = implode(' AND ', $where);
        return $this->db->query("
            SELECT pp.*, p.name AS provider_name, pi.inv_code, pi.currency AS invoice_currency,
                   CASE WHEN pp.source_type = 'banco' THEN (SELECT ba.bankName FROM bank_accounts ba WHERE ba.idBankAccount = pp.source_id)
                        WHEN pp.source_type = 'caja'  THEN (SELECT cb.name FROM cashboxes cb WHERE cb.idCashbox = pp.source_id)
                        ELSE NULL END AS cash_account_name
            FROM provider_payments pp
            JOIN providers p          ON p.idProvider = pp.provider_id
            JOIN provider_invoices pi ON pi.id = pp.invoice_id
            WHERE {$whereSql}
            ORDER BY pp.pay_date DESC, pp.id DESC
            LIMIT 500
        ", $bind)->result();
    }

    // ════════════════════════════════════════════════════════════════
    // CxP DASHBOARD (aging por proveedor + totales, en COP)
    // ════════════════════════════════════════════════════════════════

    public function agingByProvider(): array
    {
        return $this->db->query("
            SELECT
              p.idProvider AS provider_id, p.name AS provider_name,
              GROUP_CONCAT(DISTINCT pi.currency ORDER BY pi.currency SEPARATOR '/') AS provider_currency,
              COUNT(pi.id) AS num_invoices,
              SUM(pi.total - pi.paid) AS balance_native,
              SUM((pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate)) AS balance_base,
              SUM(CASE WHEN DATEDIFF(CURDATE(), pi.issue_date) <= 30
                       THEN (pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate) ELSE 0 END) AS b1,
              SUM(CASE WHEN DATEDIFF(CURDATE(), pi.issue_date) BETWEEN 31 AND 60
                       THEN (pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate) ELSE 0 END) AS b2,
              SUM(CASE WHEN DATEDIFF(CURDATE(), pi.issue_date) BETWEEN 61 AND 90
                       THEN (pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate) ELSE 0 END) AS b3,
              SUM(CASE WHEN DATEDIFF(CURDATE(), pi.issue_date) > 90
                       THEN (pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate) ELSE 0 END) AS b4
            FROM providers p
            JOIN provider_invoices pi ON pi.provider_id = p.idProvider
            WHERE pi.status IN ('open','paid_partial')
              AND COALESCE(pi.deleted,0) = 0 AND COALESCE(p.deleted,0) = 0
              AND (pi.total - pi.paid) > 0.01
            GROUP BY p.idProvider, p.name
            ORDER BY balance_base DESC
        ")->result();
    }

    public function cxpTotals(): array
    {
        $row = $this->db->query("
            SELECT
              COALESCE(SUM(pi.total - pi.paid), 0) AS total_native,
              COALESCE(SUM((pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate)), 0) AS total_base,
              COUNT(DISTINCT pi.id) AS num_invoices,
              COUNT(DISTINCT pi.provider_id) AS num_providers,
              COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), pi.issue_date) > 90
                       THEN (pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate) ELSE 0 END), 0) AS over_90d_base,
              COALESCE(SUM(CASE WHEN pi.due_date IS NOT NULL AND pi.due_date < CURDATE()
                       THEN (pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate) ELSE 0 END), 0) AS overdue_base
            FROM provider_invoices pi
            WHERE pi.status IN ('open','paid_partial')
              AND COALESCE(pi.deleted,0) = 0
              AND (pi.total - pi.paid) > 0.01
        ")->row_array();
        return $row ?: array();
    }

    /** Anticipos disponibles por proveedor (mapa provider_id => row). */
    public function advancesByProvider(): array
    {
        if (!$this->db->table_exists('provider_advances')) return array();
        $rows = $this->db->query("
            SELECT p.idProvider AS provider_id, p.name AS provider_name,
                   ROUND(SUM(a.amount_base - a.applied_amount), 2) AS available
            FROM provider_advances a
            JOIN providers p ON p.idProvider = a.provider_id
            WHERE a.status = 'open' AND COALESCE(a.deleted,0) = 0
              AND (a.amount_base - a.applied_amount) > 0.01
              AND COALESCE(p.deleted,0) = 0
            GROUP BY p.idProvider, p.name
        ")->result();
        $out = array();
        foreach ($rows as $r) $out[(int) $r->provider_id] = $r;
        return $out;
    }

    /** Mercancía en tránsito por proveedor (mapa provider_id => COP). */
    public function transitByProvider(): array
    {
        $rows = $this->db->query("
            SELECT pi.provider_id,
                   ROUND(SUM((pi.total - pi.paid) * IF(pi.currency='COP', 1, pi.exchange_rate)), 2) AS transit_base
            FROM provider_invoices pi
            WHERE pi.status = 'en_transito' AND COALESCE(pi.deleted,0) = 0
              AND (pi.total - pi.paid) > 0.01
            GROUP BY pi.provider_id
        ")->result();
        $out = array();
        foreach ($rows as $r) $out[(int) $r->provider_id] = (float) $r->transit_base;
        return $out;
    }

    public function paymentsThisMonth(): float
    {
        return (float) $this->db->query("
            SELECT COALESCE(SUM(amount * IF(currency='COP', 1, exchange_rate)), 0) AS amt
            FROM provider_payments
            WHERE COALESCE(deleted,0) = 0 AND DATE(pay_date) BETWEEN ? AND ?
        ", array(date('Y-m-01'), date('Y-m-t')))->row()->amt;
    }

    public function nextPayCode(): string
    {
        $year = date('Y');
        $last = $this->db->query("
            SELECT pay_code FROM provider_payments WHERE pay_code LIKE ? ORDER BY id DESC LIMIT 1
        ", array("PAY-{$year}-%"))->row();
        if (!$last) return sprintf("PAY-%s-0001", $year);
        $n = (int) substr($last->pay_code, strrpos($last->pay_code, '-') + 1);
        return sprintf("PAY-%s-%04d", $year, $n + 1);
    }
}
