<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accountingperiods_model extends CI_Model {

    /**
     * Get all periods with pagination
     */
    public function getPeriods($storeId = null, $page = 1, $limit = 20) {
        $this->db->select('accounting_periods.*, stores.name as storeName');
        $this->db->join('stores', 'stores.idStore = accounting_periods.storeId', 'left');
        $this->db->from('accounting_periods');
        $this->db->where('accounting_periods.deleted', 0);
        if ($storeId) {
            $this->db->where('accounting_periods.storeId', $storeId);
        }
        $this->db->order_by('accounting_periods.periodYear', 'DESC');
        $this->db->order_by('accounting_periods.periodMonth', 'DESC');
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    /**
     * Get total periods count
     */
    public function getTotalPeriods($storeId = null) {
        $this->db->from('accounting_periods');
        $this->db->where('deleted', 0);
        if ($storeId) {
            $this->db->where('storeId', $storeId);
        }
        return $this->db->count_all_results();
    }

    /**
     * Get a specific period by ID
     */
    public function getPeriod($id) {
        $this->db->select('accounting_periods.*, stores.name as storeName');
        $this->db->join('stores', 'stores.idStore = accounting_periods.storeId', 'left');
        $this->db->from('accounting_periods');
        $this->db->where('accounting_periods.id', $id);
        $this->db->where('accounting_periods.deleted', 0);
        return $this->db->get()->row();
    }

    /**
     * Get period by year and month
     */
    public function getPeriodByYearMonth($year, $month, $storeId = null, $periodType = 'monthly') {
        $this->db->from('accounting_periods');
        $this->db->where('periodYear', $year);
        $this->db->where('periodMonth', $month);
        $this->db->where('periodType', $periodType);
        $this->db->where('deleted', 0);
        if ($storeId) {
            $this->db->where('storeId', $storeId);
        } else {
            $this->db->where('storeId IS NULL');
        }
        return $this->db->get()->row();
    }

    /**
     * Check if a period is closed
     */
    public function isPeriodClosed($year, $month, $storeId = null) {
        $period = $this->getPeriodByYearMonth($year, $month, $storeId);
        return $period && $period->status === 'closed';
    }

    /**
     * Check if a date falls within a closed period
     */
    public function isDateInClosedPeriod($date, $storeId = null) {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        return $this->isPeriodClosed($year, $month, $storeId);
    }

    /**
     * Get open periods
     */
    public function getOpenPeriods($storeId = null) {
        $this->db->from('accounting_periods');
        $this->db->where('status', 'open');
        $this->db->where('deleted', 0);
        if ($storeId) {
            $this->db->where('storeId', $storeId);
        }
        $this->db->order_by('periodYear', 'DESC');
        $this->db->order_by('periodMonth', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get the last closed period
     */
    public function getLastClosedPeriod($storeId = null) {
        $this->db->from('accounting_periods');
        $this->db->where('status', 'closed');
        $this->db->where('deleted', 0);
        if ($storeId) {
            $this->db->where('storeId', $storeId);
        }
        $this->db->order_by('periodYear', 'DESC');
        $this->db->order_by('periodMonth', 'DESC');
        $this->db->limit(1);
        return $this->db->get()->row();
    }

    /**
     * Create or get a period
     */
    public function getOrCreatePeriod($year, $month, $storeId = null, $periodType = 'monthly') {
        $existing = $this->getPeriodByYearMonth($year, $month, $storeId, $periodType);
        if ($existing) {
            return $existing;
        }

        // Calculate start and end dates
        if ($periodType === 'yearly') {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
        } else {
            $startDate = date('Y-m-01', strtotime("$year-$month-01"));
            $endDate = date('Y-m-t', strtotime("$year-$month-01"));
        }

        $data = array(
            'storeId' => $storeId,
            'periodYear' => $year,
            'periodMonth' => $month,
            'periodType' => $periodType,
            'status' => 'open',
            'startDate' => $startDate,
            'endDate' => $endDate
        );

        $this->db->insert('accounting_periods', $data);
        $insertId = $this->db->insert_id();
        return $this->getPeriod($insertId);
    }

    /**
     * Save new period
     */
    public function save($data) {
        date_default_timezone_set("America/Bogota");
        return $this->db->insert('accounting_periods', $data);
    }

    /**
     * Update period
     */
    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $this->db->where('id', $id);
        return $this->db->update('accounting_periods', $data);
    }

    /**
     * Close a period
     */
    public function closePeriod($id, $closingEntryId, $totals, $userId) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'status' => 'closed',
            'closingEntryId' => $closingEntryId,
            'closedBy' => $userId,
            'closedAt' => date('Y-m-d H:i:s'),
            'totalIncome' => $totals['income'],
            'totalExpenses' => $totals['expenses'],
            'totalCosts' => $totals['costs'],
            'netIncome' => $totals['netIncome']
        );
        $this->db->where('id', $id);
        return $this->db->update('accounting_periods', $data);
    }

    /**
     * Reopen a period
     */
    public function reopenPeriod($id, $userId) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'status' => 'reopened',
            'reopenedBy' => $userId,
            'reopenedAt' => date('Y-m-d H:i:s')
        );
        $this->db->where('id', $id);
        return $this->db->update('accounting_periods', $data);
    }

    /**
     * Soft delete period
     */
    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('id', $id);
        return $this->db->update('accounting_periods', $data);
    }

    /**
     * Get periods for dropdown (year-month format)
     */
    public function getPeriodsForDropdown($storeId = null) {
        $this->db->select("id, CONCAT(periodYear, '-', LPAD(periodMonth, 2, '0')) as period, status");
        $this->db->from('accounting_periods');
        $this->db->where('deleted', 0);
        if ($storeId) {
            $this->db->where('storeId', $storeId);
        }
        $this->db->order_by('periodYear', 'DESC');
        $this->db->order_by('periodMonth', 'DESC');
        return $this->db->get()->result();
    }
}
