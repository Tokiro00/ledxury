<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expensecategories_model extends CI_Model {

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    public function getCategories($page = 1, $limit = 20) {
        $this->db->select('expense_categories.*, accounts_accounts.accountName as account_name, accounts_accounts.pucCode as account_puc, subaccounts.accountName as subaccount_name, subaccounts.pucCode as subaccount_puc');
        $this->db->from('expense_categories');
        $this->db->join('accounts_accounts', 'accounts_accounts.id = expense_categories.accounting_account_id', 'left');
        $this->db->join('subaccounts', 'subaccounts.id = expense_categories.accounting_subaccount_id', 'left');
        $this->db->where('expense_categories.deleted', 0);
        $this->db->order_by('expense_categories.code', 'asc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getCategory($id) {
        $this->db->select('expense_categories.*, accounts_accounts.accountName as account_name, accounts_accounts.pucCode as account_puc, subaccounts.accountName as subaccount_name, subaccounts.pucCode as subaccount_puc');
        $this->db->from('expense_categories');
        $this->db->join('accounts_accounts', 'accounts_accounts.id = expense_categories.accounting_account_id', 'left');
        $this->db->join('subaccounts', 'subaccounts.id = expense_categories.accounting_subaccount_id', 'left');
        $this->db->where('expense_categories.id', $id);
        $this->db->where('expense_categories.deleted', 0);
        return $this->db->get()->row();
    }

    public function getActiveCategories() {
        $this->db->select('expense_categories.*, subaccounts.accountName as subaccount_name, subaccounts.pucCode as subaccount_puc');
        $this->db->from('expense_categories');
        $this->db->join('subaccounts', 'subaccounts.id = expense_categories.accounting_subaccount_id', 'left');
        $this->db->where('expense_categories.is_active', 1);
        $this->db->where('expense_categories.deleted', 0);
        $this->db->order_by('expense_categories.name', 'asc');
        return $this->db->get()->result();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('expense_categories', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('expense_categories', $data);
    }

    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted' => 1
        );
        return $this->update($id, $data);
    }

    // ========================================================================
    // BÚSQUEDA
    // ========================================================================

    public function searchByWord($term, $page = 1, $limit = 20) {
        $this->db->select('expense_categories.*, accounts_accounts.accountName as account_name, subaccounts.accountName as subaccount_name');
        $this->db->from('expense_categories');
        $this->db->join('accounts_accounts', 'accounts_accounts.id = expense_categories.accounting_account_id', 'left');
        $this->db->join('subaccounts', 'subaccounts.id = expense_categories.accounting_subaccount_id', 'left');
        $this->db->group_start();
        $this->db->like('expense_categories.name', $term);
        $this->db->or_like('expense_categories.code', $term);
        $this->db->group_end();
        $this->db->where('expense_categories.deleted', 0);
        $this->db->order_by('expense_categories.code', 'asc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getTotal() {
        $this->db->from('expense_categories');
        $this->db->where('expense_categories.deleted', 0);
        return $this->db->count_all_results();
    }

    public function getTotalSearch($term) {
        $this->db->from('expense_categories');
        $this->db->group_start();
        $this->db->like('expense_categories.name', $term);
        $this->db->or_like('expense_categories.code', $term);
        $this->db->group_end();
        $this->db->where('expense_categories.deleted', 0);
        return $this->db->count_all_results();
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function codeExists($code, $excludeId = null) {
        $this->db->from('expense_categories');
        $this->db->where('code', $code);
        if ($excludeId) {
            $this->db->where('id !=', $excludeId);
        }
        return $this->db->count_all_results() > 0;
    }

    public function lastID() {
        return $this->db->insert_id();
    }
}
