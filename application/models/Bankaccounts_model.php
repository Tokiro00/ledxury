<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bankaccounts_model extends CI_Model {

    // ========================================================================
    // SALDO REAL (calculado desde movimientos, no del campo almacenado)
    // ========================================================================

    /**
     * Fragmento SQL: saldo real = initialBalance + ingresos - egresos +/-
     * transferencias, calculado desde cash_movements. Se usa como columna
     * calculada con alias currentBalance, que sobrescribe la columna
     * almacenada, así toda vista que lea ->currentBalance ve el saldo real.
     * Convención de signo: ingreso/apertura +, egreso/cierre -,
     * transferencia saliente -, entrante +, ajuste +.
     */
    private function realBalanceExpr() {
        $idRef = 'bank_accounts.idBankAccount';
        return "(bank_accounts.initialBalance + COALESCE((
            SELECT SUM(CASE
                WHEN cm.movementType IN ('ingreso','apertura') AND cm.sourceType='banco' AND cm.sourceId = $idRef THEN cm.amount
                WHEN cm.movementType IN ('egreso','cierre')    AND cm.sourceType='banco' AND cm.sourceId = $idRef THEN -cm.amount
                WHEN cm.movementType = 'transferencia'         AND cm.sourceType='banco' AND cm.sourceId = $idRef THEN -cm.amount
                WHEN cm.movementType = 'transferencia'         AND cm.destinationType='banco' AND cm.destinationId = $idRef THEN cm.amount
                WHEN cm.movementType = 'ajuste'                AND cm.sourceType='banco' AND cm.sourceId = $idRef THEN cm.amount
                ELSE 0 END)
            FROM cash_movements cm
            WHERE cm.deleted = 0 AND cm.status != 'anulado'
              AND ( (cm.sourceType='banco' AND cm.sourceId = $idRef)
                 OR (cm.destinationType='banco' AND cm.destinationId = $idRef AND cm.movementType='transferencia') )
        ), 0))";
    }

    private function balanceSelect() {
        return $this->realBalanceExpr() . ' AS currentBalance';
    }

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    public function getBankAccounts($storeId = null, $page = 1, $limit = 20) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId', 'left');
        if ($storeId) {
            $this->db->group_start();
            $this->db->where('bank_accounts.storeId', $storeId);
            $this->db->or_where('bank_accounts.storeId', 0);
            $this->db->group_end();
        }
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.created_at', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getBankAccount($id) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId', 'left');
        $this->db->where('bank_accounts.idBankAccount', $id);
        $this->db->where('bank_accounts.deleted', 0);
        return $this->db->get()->row();
    }

    public function getBankAccountsByStore($storeId) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId', 'left');
        $this->db->group_start();
        $this->db->where('bank_accounts.storeId', $storeId);
        $this->db->or_where('bank_accounts.storeId', 0);
        $this->db->group_end();
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.bankName', 'asc');
        return $this->db->get()->result();
    }

    public function getActiveBankAccounts($storeId = null) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId');
        $this->db->from('bank_accounts');
        $this->db->where('bank_accounts.status', 'activa');
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.bankName', 'asc');
        return $this->db->get()->result();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('bank_accounts', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idBankAccount', $id);
        return $this->db->update('bank_accounts', $data);
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
        $this->db->select('bank_accounts.*, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
        $this->db->group_start();
        $this->db->like('bank_accounts.bankName', $term);
        $this->db->or_like('bank_accounts.accountNumber', $term);
        $this->db->or_like('bank_accounts.ownerName', $term);
        $this->db->group_end();
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.created_at', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getTotal($storeId = null) {
        $this->db->from('bank_accounts');
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        return $this->db->count_all_results();
    }

    public function getTotalSearch($term, $storeId = null) {
        $this->db->from('bank_accounts');
        $this->db->group_start();
        $this->db->like('bank_accounts.bankName', $term);
        $this->db->or_like('bank_accounts.accountNumber', $term);
        $this->db->or_like('bank_accounts.ownerName', $term);
        $this->db->group_end();
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        return $this->db->count_all_results();
    }

    // ========================================================================
    // SALDOS
    // ========================================================================

    public function updateBalance($id, $amount, $operation) {
        date_default_timezone_set("America/Bogota");
        $account = $this->getBankAccount($id);
        if (!$account) return false;

        $newBalance = ($operation === 'add')
            ? $account->currentBalance + $amount
            : $account->currentBalance - $amount;

        $data = array(
            'currentBalance' => $newBalance,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('idBankAccount', $id);
        return $this->db->update('bank_accounts', $data);
    }

    public function getCurrentBalance($id) {
        // Saldo REAL calculado desde movimientos (no el campo almacenado).
        $this->db->select($this->realBalanceExpr() . ' AS bal');
        $this->db->from('bank_accounts');
        $this->db->where('idBankAccount', $id);
        $this->db->where('deleted', 0);
        $row = $this->db->get()->row();
        return $row ? (float)$row->bal : 0;
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }

    public function accountNumberExists($accountNumber, $excludeId = null) {
        $this->db->from('bank_accounts');
        $this->db->where('accountNumber', $accountNumber);
        if ($excludeId) {
            $this->db->where('idBankAccount !=', $excludeId);
        }
        return $this->db->count_all_results() > 0;
    }
}
