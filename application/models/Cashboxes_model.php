<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cashboxes_model extends CI_Model {

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    public function getCashboxes($storeId = null, $page = 1, $limit = 20) {
        $this->db->select('cashboxes.*, stores.name as store_name');
		$this->db->join('stores', 'stores.idStore = cashboxes.storeId');
        $this->db->from('cashboxes');
        if ($storeId) {
            $this->db->where('cashboxes.storeId', $storeId);
        }
        $this->db->where('cashboxes.deleted', 0);
        $this->db->order_by('cashboxes.created_at', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getCashbox($id) {
        $this->db->select('cashboxes.*, stores.name as store_name');
		$this->db->join('stores', 'stores.idStore = cashboxes.storeId');
        $this->db->from('cashboxes');
        $this->db->where('cashboxes.idCashbox', $id);
        $this->db->where('cashboxes.deleted', 0);
        return $this->db->get()->row();
    }

    public function getCashboxesByStore($storeId) {
        $this->db->select('cashboxes.*, stores.name as store_name');
		$this->db->join('stores', 'stores.idStore = cashboxes.storeId');
        $this->db->from('cashboxes');
        $this->db->where('cashboxes.storeId', $storeId);
        $this->db->where('cashboxes.deleted', 0);
        $this->db->order_by('cashboxes.name', 'asc');
        return $this->db->get()->result();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('cashboxes', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idCashbox', $id);
        return $this->db->update('cashboxes', $data);
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

    public function searchByWord($term, $storeId = null, $page = 1, $limit = 20) {
        $this->db->select('cashboxes.*');
        $this->db->from('cashboxes');
        $this->db->group_start();
        $this->db->like('cashboxes.name', $term);
        $this->db->or_like('cashboxes.code', $term);
        $this->db->group_end();
        if ($storeId) {
            $this->db->where('cashboxes.storeId', $storeId);
        }
        $this->db->where('cashboxes.deleted', 0);
        $this->db->order_by('cashboxes.created_at', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getTotal($storeId = null) {
        $this->db->from('cashboxes');
        if ($storeId) {
            $this->db->where('cashboxes.storeId', $storeId);
        }
        $this->db->where('cashboxes.deleted', 0);
        return $this->db->count_all_results();
    }

    public function getTotalSearch($term, $storeId = null) {
        $this->db->from('cashboxes');
        $this->db->group_start();
        $this->db->like('cashboxes.name', $term);
        $this->db->or_like('cashboxes.code', $term);
        $this->db->group_end();
        if ($storeId) {
            $this->db->where('cashboxes.storeId', $storeId);
        }
        $this->db->where('cashboxes.deleted', 0);
        return $this->db->count_all_results();
    }

    // ========================================================================
    // OPERACIONES DE CAJA
    // ========================================================================

    public function openCashbox($id, $userId, $initialBalance = 0) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'status' => 'abierta',
            'initialBalance' => $initialBalance,
            'currentBalance' => $initialBalance,
            'openedAt' => date('Y-m-d H:i:s'),
            'openedBy' => $userId,
            'responsibleUserId' => $userId,
            'closedAt' => null,
            'closedBy' => null,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('idCashbox', $id);
        return $this->db->update('cashboxes', $data);
    }

    public function closeCashbox($id, $userId) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'status' => 'cerrada',
            'closedAt' => date('Y-m-d H:i:s'),
            'closedBy' => $userId,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('idCashbox', $id);
        return $this->db->update('cashboxes', $data);
    }

    public function getActiveCashboxes($storeId = null) {
        $this->db->select('cashboxes.*');
        $this->db->from('cashboxes');
        $this->db->where('cashboxes.status', 'abierta');
        if ($storeId) {
            $this->db->where('cashboxes.storeId', $storeId);
        }
        $this->db->where('cashboxes.deleted', 0);
        return $this->db->get()->result();
    }

    public function getCashboxByUser($userId) {
        $this->db->select('cashboxes.*');
        $this->db->from('cashboxes');
        $this->db->where('cashboxes.responsibleUserId', $userId);
        $this->db->where('cashboxes.status', 'abierta');
        $this->db->where('cashboxes.deleted', 0);
        return $this->db->get()->row();
    }

    // ========================================================================
    // SALDOS
    // ========================================================================

    public function updateBalance($id, $amount, $operation) {
        date_default_timezone_set("America/Bogota");
        $cashbox = $this->getCashbox($id);
        if (!$cashbox) return false;

        $newBalance = ($operation === 'add')
            ? $cashbox->currentBalance + $amount
            : $cashbox->currentBalance - $amount;

        $data = array(
            'currentBalance' => $newBalance,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('idCashbox', $id);
        return $this->db->update('cashboxes', $data);
    }

    public function getCurrentBalance($id) {
        $this->db->select('currentBalance');
        $this->db->from('cashboxes');
        $this->db->where('idCashbox', $id);
        $this->db->where('deleted', 0);
        $row = $this->db->get()->row();
        return $row ? $row->currentBalance : 0;
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }

    public function codeExists($code, $excludeId = null) {
        $this->db->from('cashboxes');
        $this->db->where('code', $code);
        $this->db->where('deleted', 0);
        if ($excludeId) {
            $this->db->where('idCashbox !=', $excludeId);
        }
        return $this->db->count_all_results() > 0;
    }
}
