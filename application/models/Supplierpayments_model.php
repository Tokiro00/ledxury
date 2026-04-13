<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Supplier Payments Model
 * Manages payments to suppliers
 */
class Supplierpayments_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        date_default_timezone_set("America/Bogota");
    }

    /**
     * Get all payments with provider info
     */
    public function getPayments($page = 1, $limit = 50, $filters = array()) {
        $this->db->select('supplier_payments.*, providers.name as providerName, supplier_invoices.invoiceNumber');
        $this->db->join('providers', 'providers.idProvider = supplier_payments.providerId');
        $this->db->join('supplier_invoices', 'supplier_invoices.idSupplierInvoice = supplier_payments.supplierInvoiceId', 'left');
        $this->db->from('supplier_payments');
        $this->db->where('supplier_payments.deleted', 0);

        if (!empty($filters['providerId'])) {
            $this->db->where('supplier_payments.providerId', $filters['providerId']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('supplier_payments.status', $filters['status']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('supplier_payments.paymentDate >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('supplier_payments.paymentDate <=', $filters['to']);
        }

        $this->db->order_by('supplier_payments.paymentDate', 'DESC');
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);

        return $this->db->get()->result();
    }

    /**
     * Get total count for pagination
     */
    public function getTotal($filters = array()) {
        $this->db->from('supplier_payments');
        $this->db->where('supplier_payments.deleted', 0);

        if (!empty($filters['providerId'])) {
            $this->db->where('supplier_payments.providerId', $filters['providerId']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('supplier_payments.status', $filters['status']);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get single payment
     */
    public function getPayment($id) {
        $this->db->select('supplier_payments.*, providers.name as providerName, providers.idNum as providerIdNum, supplier_invoices.invoiceNumber, supplier_invoices.total as invoiceTotal');
        $this->db->join('providers', 'providers.idProvider = supplier_payments.providerId');
        $this->db->join('supplier_invoices', 'supplier_invoices.idSupplierInvoice = supplier_payments.supplierInvoiceId', 'left');
        $this->db->from('supplier_payments');
        $this->db->where('supplier_payments.idSupplierPayment', $id);
        $this->db->where('supplier_payments.deleted', 0);
        return $this->db->get()->row();
    }

    /**
     * Get payments by invoice
     */
    public function getPaymentsByInvoice($invoiceId) {
        $this->db->select('supplier_payments.*');
        $this->db->from('supplier_payments');
        $this->db->where('supplier_payments.supplierInvoiceId', $invoiceId);
        $this->db->where('supplier_payments.deleted', 0);
        $this->db->where('supplier_payments.status', 'ejecutado');
        $this->db->order_by('supplier_payments.paymentDate', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get payments by provider
     */
    public function getPaymentsByProvider($providerId, $from = null, $to = null) {
        $this->db->select('supplier_payments.*, supplier_invoices.invoiceNumber');
        $this->db->join('supplier_invoices', 'supplier_invoices.idSupplierInvoice = supplier_payments.supplierInvoiceId', 'left');
        $this->db->from('supplier_payments');
        $this->db->where('supplier_payments.providerId', $providerId);
        $this->db->where('supplier_payments.deleted', 0);
        $this->db->where('supplier_payments.status', 'ejecutado');

        if ($from) {
            $this->db->where('supplier_payments.paymentDate >=', $from);
        }
        if ($to) {
            $this->db->where('supplier_payments.paymentDate <=', $to);
        }

        $this->db->order_by('supplier_payments.paymentDate', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Save new payment
     */
    public function save($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('supplier_payments', $data);
    }

    /**
     * Update payment
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idSupplierPayment', $id);
        return $this->db->update('supplier_payments', $data);
    }

    /**
     * Soft delete / cancel payment
     */
    public function remove($id) {
        $data = array(
            'deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'status' => 'anulado'
        );
        return $this->update($id, $data);
    }

    /**
     * Get last inserted ID
     */
    public function lastID() {
        return $this->db->insert_id();
    }

    /**
     * Get total paid to a provider
     */
    public function getTotalPaidToProvider($providerId, $from = null, $to = null) {
        $this->db->select_sum('amount', 'total');
        $this->db->from('supplier_payments');
        $this->db->where('providerId', $providerId);
        $this->db->where('status', 'ejecutado');
        $this->db->where('deleted', 0);

        if ($from) {
            $this->db->where('paymentDate >=', $from);
        }
        if ($to) {
            $this->db->where('paymentDate <=', $to);
        }

        $result = $this->db->get()->row();
        return $result ? (float)$result->total : 0;
    }

    /**
     * Get total paid for an invoice
     */
    public function getTotalPaidForInvoice($invoiceId) {
        $this->db->select_sum('amount', 'total');
        $this->db->from('supplier_payments');
        $this->db->where('supplierInvoiceId', $invoiceId);
        $this->db->where('status', 'ejecutado');
        $this->db->where('deleted', 0);
        $result = $this->db->get()->row();
        return $result ? (float)$result->total : 0;
    }

    /**
     * Get payments summary by period
     */
    public function getPaymentsSummary($from, $to) {
        $this->db->select('SUM(amount) as total, COUNT(*) as count');
        $this->db->from('supplier_payments');
        $this->db->where('paymentDate >=', $from);
        $this->db->where('paymentDate <=', $to);
        $this->db->where('status', 'ejecutado');
        $this->db->where('deleted', 0);
        return $this->db->get()->row();
    }
}
