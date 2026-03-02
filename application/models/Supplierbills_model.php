<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Supplier Bills Model (Cuentas por Pagar)
 * Manages supplier invoices / accounts payable
 */
class Supplierbills_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        date_default_timezone_set("America/Bogota");
    }

    /**
     * Get all supplier invoices with provider info
     */
    public function getBills($page = 1, $limit = 50, $filters = array()) {
        $this->db->select('supplier_invoices.*, providers.name as providerName, providers.idNum as providerIdNum');
        $this->db->join('providers', 'providers.idProvider = supplier_invoices.providerId');
        $this->db->from('supplier_invoices');
        $this->db->where('supplier_invoices.deleted', 0);

        // Apply filters
        if (!empty($filters['providerId'])) {
            $this->db->where('supplier_invoices.providerId', $filters['providerId']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('supplier_invoices.status', $filters['status']);
        }
        if (!empty($filters['storeId'])) {
            $this->db->where('supplier_invoices.storeId', $filters['storeId']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('supplier_invoices.invoiceDate >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('supplier_invoices.invoiceDate <=', $filters['to']);
        }
        if (isset($filters['received']) && $filters['received'] !== '') {
            $this->db->where('supplier_invoices.received', $filters['received']);
        }

        $this->db->order_by('supplier_invoices.dueDate', 'ASC');
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);

        return $this->db->get()->result();
    }

    /**
     * Get total count for pagination
     */
    public function getTotal($filters = array()) {
        $this->db->from('supplier_invoices');
        $this->db->where('supplier_invoices.deleted', 0);

        if (!empty($filters['providerId'])) {
            $this->db->where('supplier_invoices.providerId', $filters['providerId']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('supplier_invoices.status', $filters['status']);
        }
        if (!empty($filters['storeId'])) {
            $this->db->where('supplier_invoices.storeId', $filters['storeId']);
        }
        if (isset($filters['received']) && $filters['received'] !== '') {
            $this->db->where('supplier_invoices.received', $filters['received']);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get single bill with provider info
     */
    public function getBill($id) {
        $this->db->select('supplier_invoices.*, providers.name as providerName, providers.idNum as providerIdNum, providers.phone as providerPhone, providers.email as providerEmail');
        $this->db->join('providers', 'providers.idProvider = supplier_invoices.providerId');
        $this->db->from('supplier_invoices');
        $this->db->where('supplier_invoices.idSupplierInvoice', $id);
        $this->db->where('supplier_invoices.deleted', 0);
        return $this->db->get()->row();
    }

    /**
     * Get bills by provider
     */
    public function getBillsByProvider($providerId, $status = null) {
        $this->db->select('supplier_invoices.*');
        $this->db->from('supplier_invoices');
        $this->db->where('supplier_invoices.providerId', $providerId);
        $this->db->where('supplier_invoices.deleted', 0);
        if ($status) {
            $this->db->where('supplier_invoices.status', $status);
        }
        $this->db->order_by('supplier_invoices.dueDate', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get pending bills (not fully paid)
     */
    public function getPendingBills($providerId = null) {
        $this->db->select('supplier_invoices.*, providers.name as providerName');
        $this->db->join('providers', 'providers.idProvider = supplier_invoices.providerId');
        $this->db->from('supplier_invoices');
        $this->db->where_in('supplier_invoices.status', array('pendiente', 'parcial', 'vencida'));
        $this->db->where('supplier_invoices.deleted', 0);
        if ($providerId) {
            $this->db->where('supplier_invoices.providerId', $providerId);
        }
        $this->db->order_by('supplier_invoices.dueDate', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get overdue bills
     */
    public function getOverdueBills() {
        $this->db->select('supplier_invoices.*, providers.name as providerName');
        $this->db->join('providers', 'providers.idProvider = supplier_invoices.providerId');
        $this->db->from('supplier_invoices');
        $this->db->where_in('supplier_invoices.status', array('pendiente', 'parcial'));
        $this->db->where('supplier_invoices.dueDate <', date('Y-m-d'));
        $this->db->where('supplier_invoices.deleted', 0);
        $this->db->order_by('supplier_invoices.dueDate', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Save new bill
     */
    public function save($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('supplier_invoices', $data);
    }

    /**
     * Update bill
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idSupplierInvoice', $id);
        return $this->db->update('supplier_invoices', $data);
    }

    /**
     * Soft delete
     */
    public function remove($id) {
        $data = array(
            'deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'status' => 'anulada'
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
     * Update paid amount and status
     */
    public function updatePaidAmount($id, $paymentAmount) {
        $bill = $this->getBill($id);
        if (!$bill) return false;

        $newPaidAmount = (float)$bill->paidAmount + (float)$paymentAmount;
        $newStatus = 'parcial';

        if ($newPaidAmount >= (float)$bill->total) {
            $newStatus = 'pagada';
            $newPaidAmount = (float)$bill->total;
        }

        return $this->update($id, array(
            'paidAmount' => $newPaidAmount,
            'status' => $newStatus
        ));
    }

    /**
     * Update overdue status for bills past due date
     */
    public function updateOverdueStatus() {
        $this->db->where_in('status', array('pendiente', 'parcial'));
        $this->db->where('dueDate <', date('Y-m-d'));
        $this->db->where('deleted', 0);
        return $this->db->update('supplier_invoices', array(
            'status' => 'vencida',
            'updated_at' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Get aging report (cartera por edades)
     */
    public function getAgingReport() {
        $today = date('Y-m-d');

        $result = array(
            'current' => 0,      // Not yet due
            'days_1_30' => 0,    // 1-30 days overdue
            'days_31_60' => 0,   // 31-60 days overdue
            'days_61_90' => 0,   // 61-90 days overdue
            'days_90_plus' => 0, // 90+ days overdue
            'total' => 0
        );

        $bills = $this->getPendingBills();

        foreach ($bills as $bill) {
            $dueDate = strtotime($bill->dueDate);
            $todayTs = strtotime($today);
            $daysOverdue = floor(($todayTs - $dueDate) / 86400);
            $balance = (float)$bill->balance;

            if ($daysOverdue <= 0) {
                $result['current'] += $balance;
            } elseif ($daysOverdue <= 30) {
                $result['days_1_30'] += $balance;
            } elseif ($daysOverdue <= 60) {
                $result['days_31_60'] += $balance;
            } elseif ($daysOverdue <= 90) {
                $result['days_61_90'] += $balance;
            } else {
                $result['days_90_plus'] += $balance;
            }

            $result['total'] += $balance;
        }

        return $result;
    }

    /**
     * Get total payable amount
     */
    public function getTotalPayable() {
        $this->db->select_sum('balance', 'total');
        $this->db->from('supplier_invoices');
        $this->db->where_in('status', array('pendiente', 'parcial', 'vencida'));
        $this->db->where('deleted', 0);
        $result = $this->db->get()->row();
        return $result ? (float)$result->total : 0;
    }

    /**
     * Get total payable by provider
     */
    public function getTotalByProvider($providerId) {
        $this->db->select_sum('balance', 'total');
        $this->db->from('supplier_invoices');
        $this->db->where('providerId', $providerId);
        $this->db->where_in('status', array('pendiente', 'parcial', 'vencida'));
        $this->db->where('deleted', 0);
        $result = $this->db->get()->row();
        return $result ? (float)$result->total : 0;
    }

    /**
     * Get top providers with pending balances
     */
    public function getTopProviderBalances($limit = 20) {
        $this->db->select('providers.idProvider, providers.name, providers.idNum, SUM(supplier_invoices.balance) as total_balance, COUNT(*) as bill_count, MAX(DATEDIFF(CURDATE(), supplier_invoices.dueDate)) as max_days_overdue');
        $this->db->from('supplier_invoices');
        $this->db->join('providers', 'providers.idProvider = supplier_invoices.providerId');
        $this->db->where_in('supplier_invoices.status', array('pendiente', 'parcial', 'vencida'));
        $this->db->where('supplier_invoices.deleted', 0);
        $this->db->group_by('providers.idProvider, providers.name, providers.idNum');
        $this->db->order_by('total_balance', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /**
     * Get bills by provider with date range (for statement timeline)
     */
    public function getBillsByProviderDates($providerId, $from, $to) {
        $this->db->select('supplier_invoices.*, stores.name as store_name');
        $this->db->from('supplier_invoices');
        $this->db->join('stores', 'stores.idStore = supplier_invoices.storeId', 'left');
        $this->db->where('supplier_invoices.providerId', $providerId);
        $this->db->where('supplier_invoices.deleted', 0);
        $this->db->where('supplier_invoices.invoiceDate >=', $from);
        $this->db->where('supplier_invoices.invoiceDate <=', $to);
        $this->db->order_by('supplier_invoices.invoiceDate', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get aging for a specific provider
     */
    public function getAgingByProvider($providerId) {
        $today = date('Y-m-d');

        $result = array(
            'current' => 0, 'count_current' => 0,
            'days_1_30' => 0, 'count_1_30' => 0,
            'days_31_60' => 0, 'count_31_60' => 0,
            'days_61_90' => 0, 'count_61_90' => 0,
            'days_90_plus' => 0, 'count_90_plus' => 0,
            'total' => 0
        );

        $bills = $this->getPendingBills($providerId);

        foreach ($bills as $bill) {
            $daysOverdue = floor((strtotime($today) - strtotime($bill->dueDate)) / 86400);
            $balance = (float)$bill->balance;

            if ($daysOverdue <= 0) {
                $result['current'] += $balance;
                $result['count_current']++;
            } elseif ($daysOverdue <= 30) {
                $result['days_1_30'] += $balance;
                $result['count_1_30']++;
            } elseif ($daysOverdue <= 60) {
                $result['days_31_60'] += $balance;
                $result['count_31_60']++;
            } elseif ($daysOverdue <= 90) {
                $result['days_61_90'] += $balance;
                $result['count_61_90']++;
            } else {
                $result['days_90_plus'] += $balance;
                $result['count_90_plus']++;
            }

            $result['total'] += $balance;
        }

        return $result;
    }

    /**
     * Marcar factura como recibida en bodega
     */
    public function markAsReceived($id, $userId) {
        return $this->update($id, array(
            'received' => 1,
            'received_at' => date('Y-m-d H:i:s'),
            'received_by' => $userId
        ));
    }

    /**
     * Get all provider balances (for provider list)
     */
    public function getAllProviderBalances() {
        $this->db->select('providerId, SUM(balance) as total_balance');
        $this->db->from('supplier_invoices');
        $this->db->where_in('status', array('pendiente', 'parcial', 'vencida'));
        $this->db->where('deleted', 0);
        $this->db->group_by('providerId');
        $result = $this->db->get()->result();

        $balances = array();
        foreach ($result as $row) {
            $balances[$row->providerId] = (float)$row->total_balance;
        }
        return $balances;
    }
}
