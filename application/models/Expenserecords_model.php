<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expenserecords_model extends CI_Model {

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    public function getExpenseRecords($page = 1, $limit = 20, $filters = array()) {
        $this->db->select('expense_records.*, expense_categories.name as category_name, expense_categories.code as category_code, providers.name as provider_name, stores.name as store_name');
        $this->db->from('expense_records');
        $this->db->join('expense_categories', 'expense_categories.id = expense_records.expense_category_id', 'left');
        $this->db->join('providers', 'providers.idProvider = expense_records.provider_id', 'left');
        $this->db->join('stores', 'stores.idStore = expense_records.store_id', 'left');
        $this->db->where('expense_records.deleted', 0);

        $this->_applyFilters($filters);

        $this->db->order_by('expense_records.expense_date', 'desc');
        $this->db->order_by('expense_records.id', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getExpenseRecord($id) {
        $this->db->select('expense_records.*, expense_categories.name as category_name, expense_categories.code as category_code, expense_categories.accounting_subaccount_id, providers.name as provider_name, providers.idNum as provider_idnum, stores.name as store_name');
        $this->db->from('expense_records');
        $this->db->join('expense_categories', 'expense_categories.id = expense_records.expense_category_id', 'left');
        $this->db->join('providers', 'providers.idProvider = expense_records.provider_id', 'left');
        $this->db->join('stores', 'stores.idStore = expense_records.store_id', 'left');
        $this->db->where('expense_records.id', $id);
        $this->db->where('expense_records.deleted', 0);
        return $this->db->get()->row();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('expense_records', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('expense_records', $data);
    }

    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted' => 1,
            'status' => 'anulado'
        );
        return $this->update($id, $data);
    }

    // ========================================================================
    // BÚSQUEDA Y FILTROS
    // ========================================================================

    private function _applyFilters($filters) {
        if (!empty($filters['category_id'])) {
            $this->db->where('expense_records.expense_category_id', $filters['category_id']);
        }
        if (!empty($filters['provider_id'])) {
            $this->db->where('expense_records.provider_id', $filters['provider_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('expense_records.status', $filters['status']);
        }
        if (!empty($filters['store_id'])) {
            $this->db->where('expense_records.store_id', $filters['store_id']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('expense_records.expense_date >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('expense_records.expense_date <=', $filters['to']);
        }
    }

    public function searchByWord($term, $page = 1, $limit = 20) {
        $this->db->select('expense_records.*, expense_categories.name as category_name, providers.name as provider_name, stores.name as store_name');
        $this->db->from('expense_records');
        $this->db->join('expense_categories', 'expense_categories.id = expense_records.expense_category_id', 'left');
        $this->db->join('providers', 'providers.idProvider = expense_records.provider_id', 'left');
        $this->db->join('stores', 'stores.idStore = expense_records.store_id', 'left');
        $this->db->group_start();
        $this->db->like('expense_records.description', $term);
        $this->db->or_like('expense_records.code', $term);
        $this->db->or_like('providers.name', $term);
        $this->db->or_like('expense_categories.name', $term);
        $this->db->group_end();
        $this->db->where('expense_records.deleted', 0);
        $this->db->order_by('expense_records.expense_date', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getTotal($filters = array()) {
        $this->db->from('expense_records');
        if (!empty($filters)) {
            $this->db->join('expense_categories', 'expense_categories.id = expense_records.expense_category_id', 'left');
            $this->db->join('providers', 'providers.idProvider = expense_records.provider_id', 'left');
            $this->_applyFilters($filters);
        }
        $this->db->where('expense_records.deleted', 0);
        return $this->db->count_all_results();
    }

    public function getTotalSearch($term) {
        $this->db->from('expense_records');
        $this->db->join('expense_categories', 'expense_categories.id = expense_records.expense_category_id', 'left');
        $this->db->join('providers', 'providers.idProvider = expense_records.provider_id', 'left');
        $this->db->group_start();
        $this->db->like('expense_records.description', $term);
        $this->db->or_like('expense_records.code', $term);
        $this->db->or_like('providers.name', $term);
        $this->db->or_like('expense_categories.name', $term);
        $this->db->group_end();
        $this->db->where('expense_records.deleted', 0);
        return $this->db->count_all_results();
    }

    // ========================================================================
    // CÓDIGO AUTOGENERADO
    // ========================================================================

    public function getNextCode() {
        // Buscar el mayor número usado en los códigos GASxxxx existentes
        $this->db->select('code');
        $this->db->from('expense_records');
        $this->db->like('code', 'GAS', 'after');
        $rows = $this->db->get()->result();

        $maxNum = 0;
        foreach ($rows as $row) {
            $num = (int) substr($row->code, 3);
            if ($num > $maxNum) $maxNum = $num;
        }

        $nextNum = $maxNum + 1;
        $code = 'GAS' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        return $code;
    }

    // ========================================================================
    // REPORTES Y MÉTRICAS
    // ========================================================================

    public function getMonthlyTotal($storeId = null) {
        $this->db->select_sum('amount');
        $this->db->from('expense_records');
        $this->db->where('status', 'pagado');
        $this->db->where('MONTH(expense_date)', date('n'));
        $this->db->where('YEAR(expense_date)', date('Y'));
        if ($storeId) {
            $this->db->where('store_id', $storeId);
        }
        $this->db->where('deleted', 0);
        $row = $this->db->get()->row();
        return $row ? (float)$row->amount : 0;
    }

    public function getPendingTotal($storeId = null) {
        $this->db->select_sum('amount');
        $this->db->from('expense_records');
        $this->db->where('status', 'pendiente');
        if ($storeId) {
            $this->db->where('store_id', $storeId);
        }
        $this->db->where('deleted', 0);
        $row = $this->db->get()->row();
        return $row ? (float)$row->amount : 0;
    }

    public function getRecentExpenses($limit = 5) {
        $this->db->select('expense_records.*, expense_categories.name as category_name, providers.name as provider_name');
        $this->db->from('expense_records');
        $this->db->join('expense_categories', 'expense_categories.id = expense_records.expense_category_id', 'left');
        $this->db->join('providers', 'providers.idProvider = expense_records.provider_id', 'left');
        $this->db->where('expense_records.deleted', 0);
        $this->db->order_by('expense_records.created_at', 'desc');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }
}
